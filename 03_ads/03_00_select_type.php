<?php
// ============================================================
// 03_ads/03_00_select_type.php
// WIZARD di classificazione che PRECEDE il form annuncio gratuito.
//
// Flusso (flowchart, dir. 18):
//   Step 1  Tipo oggetto : Vehicle  |  Shelter / Container
//   Step 2  Macro        : Road     |  Special      (saltato se Shelter)
//   Step 3  Tipologia    : una voce dell'elenco della macro scelta
//   -> salva la classificazione in $_SESSION['ad_wizard'] e prosegue
//      con 03_insert_ad.php (resto dell'annuncio free).
//
// Pagina self-posting (un solo file). CSRF persistente per tutto il wizard.
// Nessuno stile nuovo: usa .step-bar/.step/.cat-grid gia' nel CSS (dir. 8).
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

// Punto 2 (17 lug 2026): l'ospite puo' ENTRARE nel wizard e compilare.
// L'account si chiede al momento di pubblicare (handler 02_01). Se e' loggato
// $user_id e' il suo id, altrimenti null: i controlli quota qui sotto girano
// solo per gli utenti, per l'ospite il gate scatta al publish.
$user_id = current_user_id(); // null = ospite

// Wizard UNIFICATO (rev. 7 lug): un solo flusso per free e premium.
// La scelta "listing" decide la tabella di destinazione (02_free_ads / 02_ads);
// gli step extra (scheda tecnica + documenti) restano in 02_ads (dir. 13).
if ($user_id !== null) {
  // --- Utente loggato: si applicano le quote come sempre ---
  $aow_check_free = UserTier::canInsertFreeAd($pdo, $user_id);
  $aow_check_prem = UserTier::canInsertPremiumAd($pdo, $user_id);
  $aow_can_prem   = (bool)$aow_check_prem['allowed'];
  $aow_sel = ((($_POST['listing'] ?? $_GET['listing'] ?? 'free') === 'prem') && $aow_can_prem) ? 'prem' : 'free';
  // Gate: bloccato solo se non puo' inserire NULLA (ne' free ne' premium)
  if (!$aow_check_free['allowed'] && !$aow_can_prem) {
    $_SESSION['error_message'] = $aow_check_free['reason'];
    header('Location: ' . BASE_URL . '/01_login/my_posts.php');
    exit;
  }
  $check = ($aow_sel === 'prem') ? $aow_check_prem : $aow_check_free;
  if (!$check['allowed']) { $aow_sel = $aow_can_prem ? 'prem' : 'free'; }
} else {
  // --- Ospite: nessuna quota da controllare ora. Puo' scegliere free o
  // premium; il gate quota scattera' al publish, quando avra' un account. ---
  $aow_can_prem = true;
  $aow_sel = ((($_POST['listing'] ?? $_GET['listing'] ?? 'free') === 'prem')) ? 'prem' : 'free';
}

// Avvia/rinnova il wizard CSRF persistente
csrf_generate_persistent();

// ------------------------------------------------------------
// Logica di avanzamento step
// ------------------------------------------------------------
$step  = 1;           // step da MOSTRARE
$error = '';
$kind  = VehicleTaxonomy::KIND_VEHICLE;
$macro = VehicleTaxonomy::MACRO_ROAD;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify_persistent();
  $from = (int)($_POST['wizard_step'] ?? 0);

  if ($from === 1) {
    // Arrivo dallo step 1 (scelta tipo oggetto)
    $kind = ($_POST['item_kind'] ?? '') === VehicleTaxonomy::KIND_SHELTER
      ? VehicleTaxonomy::KIND_SHELTER
      : VehicleTaxonomy::KIND_VEHICLE;

    if ($kind === VehicleTaxonomy::KIND_SHELTER) {
      // Shelter/Container e' sempre Special e usa un tipo dedicato
      // (VehicleTaxonomy::SHELTER_SLUG): non passa dallo step 2 (road/special)
      // ne' dallo step 3 (scelta tipo), perche' il tipo e' gia' determinato.
      // FIX 2026-07-24: prima si rimandava a 03_insert_ad.php SENZA salvare
      // $_SESSION['ad_wizard']; quella pagina, non trovando la classificazione,
      // rimbalzava di nuovo qui -> lo Shelter non era mai inseribile e la
      // scelta free/premium andava persa. Ora la classificazione viene
      // persistita, esattamente come nel ramo Vehicle.
      $_SESSION['ad_wizard'] = [
        'module'         => ($aow_sel === 'prem' ? '03' : '02'),
        'item_kind'      => VehicleTaxonomy::KIND_SHELTER,
        'macro_category' => VehicleTaxonomy::MACRO_SPECIAL,
        'vehicle_type'   => VehicleTaxonomy::SHELTER_SLUG,
      ];
      header('Location: 03_insert_ad.php');
      exit;
    }
    $step = 2; // veicolo -> scegli macro
  } elseif ($from === 2) {
    $kind  = VehicleTaxonomy::KIND_VEHICLE;
    $macro = ($_POST['macro_category'] ?? '') === VehicleTaxonomy::MACRO_SPECIAL
      ? VehicleTaxonomy::MACRO_SPECIAL
      : VehicleTaxonomy::MACRO_ROAD;
    $step = 3; // scegli tipologia
  } elseif ($from === 3) {
    $kind  = VehicleTaxonomy::KIND_VEHICLE;
    $macro = ($_POST['macro_category'] ?? '') === VehicleTaxonomy::MACRO_SPECIAL
      ? VehicleTaxonomy::MACRO_SPECIAL
      : VehicleTaxonomy::MACRO_ROAD;
    $vtype = trim($_POST['vehicle_type'] ?? '');

    if (!VehicleTaxonomy::isValidType($vtype, $macro, $pdo)) {
      $error = 'Please choose a vehicle type from the list.';
      $step  = 3; // ri-mostra step 3
    } else {
      $_SESSION['ad_wizard'] = [
        'module'         => ($aow_sel === 'prem' ? '03' : '02'),
        'item_kind'      => VehicleTaxonomy::KIND_VEHICLE,
        'macro_category' => $macro,
        'vehicle_type'   => $vtype,
      ];
      header('Location: 03_insert_ad.php');
      exit;
    }
  }
}

// Elenco tipologie per lo step 3 (data-driven dal DB, dir. 14)
// Nuova tassonomia (24 lug 2026): le tipologie NON dipendono piu' da una
// colonna macro dentro un'unica tabella, ma da DUE repository distinti.
//   Road    -> vehicle_types (lista del codice della strada)
//   Special -> special_types (lista curata dall'admin)
//   Shelter -> ancora special_types: e' un allestimento speciale su container
// La scelta della tabella la fa VehicleTaxonomy, non questo file.
$aow_cat = ($kind === VehicleTaxonomy::KIND_SHELTER)
    ? VehicleTaxonomy::CAT_SHELTER
    : (($macro === VehicleTaxonomy::MACRO_ROAD) ? VehicleTaxonomy::CAT_ROAD : VehicleTaxonomy::CAT_SPECIAL);
$types = ($step === 3) ? VehicleTaxonomy::typesForCategory($aow_cat, $pdo) : [];

// Helper per le classi della barra di avanzamento
$cls = function (int $n) use ($step): string {
  if ($n <  $step) return 'step done';
  if ($n === $step) return 'step active';
  return 'step';
};
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Classify premium ad</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Insert your ad &mdash; category</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <div class="post_box">
    <h2>What are you advertising?</h2>
    <p><strong>Step 1 &middot; Choose what you are selling</strong></p>

    <div class="step-bar">
      <div class="<?php echo $cls(1); ?>">1. Vehicle or Shelter</div>
      <div class="<?php echo $cls(2); ?>">2. Road or Special</div>
      <div class="<?php echo $cls(3); ?>">3. Vehicle type</div>
    </div>

    <?php if ($error !== ''): ?>
      <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div id="contact_form">

    <?php if ($step === 1): ?>
      <!-- STEP 1: tipo oggetto -->
      <form action="03_00_select_type.php" method="post">
        <?php if ($aow_sel === 'prem'): ?><input type="hidden" name="listing" value="prem" /><?php endif; ?>
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="wizard_step" value="1" />
        <div class="form_row">
          <label><strong>Choose the kind of item:</strong><br>
            <br>
          </label>
        </div>
        <div class="cat-grid">
          <label><input type="radio" name="item_kind" value="vehicle" checked /> Vehicle</label>
          <label><input type="radio" name="item_kind" value="shelter_container" /> Shelter / Container</label>
        </div>
        <p><em>Shelter / Container items are classified as <strong>Special</strong>.</em></p>
        <?php if ($aow_can_prem): ?>
        <div class="form_row">
			<!-- inizio codice sostitutivo -->
			<?php $aow_sel = 'prem';?>
			<!-- fine codice sostitutivo -->
        </div>
        <?php endif; ?>
        <div class="form_row">
          <button type="submit" value="Next" class="more float_r">Next</button>
          <a href="../01_login/my_posts.php" class="more float_l">Cancel</a>
        </div>
      </form>

    <?php elseif ($step === 2): ?>
      <!-- STEP 2: macro categoria -->
      <form action="03_00_select_type.php" method="post">
        <?php if ($aow_sel === 'prem'): ?><input type="hidden" name="listing" value="prem" /><?php endif; ?>
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="wizard_step" value="2" />
        <div class="form_row">
          <label><strong>            Vehicle macro-category:</strong><br>
            <br>
          </label>
        </div>
        <div class="cat-grid">
          <label><input type="radio" name="macro_category" value="road" checked /> Road</label>
			<div>All vehicles in our roads.</div><br>
          <label><input type="radio" name="macro_category" value="special" /> Special</label>
			<div>Other "On demand".</div><br>
        </div>
		  
		  <div class="cleaner h20"></div> 
        <div class="form_row">
          <p>
            <button type="submit" value="Next" class="more float_r">Next</button>
			<a href="03_00_select_type.php" class="more float_l">Back</a>
			</p>
        </div>
      </form>

    <?php else: ?>
      <!-- STEP 3: tipologia specifica -->
      <form action="03_00_select_type.php" method="post">
        <?php if ($aow_sel === 'prem'): ?><input type="hidden" name="listing" value="prem" /><?php endif; ?>
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="wizard_step" value="3" />
        <input type="hidden" name="macro_category" value="<?php echo htmlspecialchars($macro, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php // Senza questo, uno shelter arrivato allo step 3 verrebbe
              // salvato come veicolo: item_kind deve viaggiare col form. ?>
        <input type="hidden" name="item_kind" value="<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="form_row">
          <strong><?php echo ucfirst($macro); ?></strong> => choose the type (If you can't find it in the list below, please go back and select ROAD):<br>
            <br>
          
        </div>
        <?php if (empty($types)): ?>
          <p class="error-msg">No vehicle types are available for this category yet.</p>
          <div class="form_row">
            <a href="03_00_select_type.php" class="more float_l">Back</a>
          </div>
        <?php else: ?>
          <div class="form_row">
            <p>
              <select name="vehicle_type" id="vehicle_type" class="input_field" required>
                <option value="">-- select --</option>
<?php foreach ($types as $key => $item): ?>
  <?php 
    // Se $item è un array (es. riga del DB), estraiamo lo slug/id e l'etichetta
    if (is_array($item)) {
        $val   = $item['slug'] ?? $item['code'] ?? $item['id'] ?? $key;
        $label = $item['label'] ?? $item['name'] ?? $item['title'] ?? reset($item);
    } else {
        $val   = $key;
        $label = $item;
    }
  ?>
  <option value="<?php echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'); ?>
  </option>
                <?php endforeach; ?>
              </select>
            </p>
            <p>&nbsp;</p>
          </div>
          <div class="form_row">
            <button type="submit" value="Next" class="more float_r">Next</button>
            <button type="submit" formnovalidate data-aow-wizard-back="1" class="more float_l" style="cursor: pointer;">Back</button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>

    </div><!-- /contact_form -->
    </div><!-- /post_box -->

  </div>

  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
