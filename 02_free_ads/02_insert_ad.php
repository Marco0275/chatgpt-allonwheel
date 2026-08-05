<?php
// ============================================================
// 02_free_ads/02_insert_ad.php
// Form di inserimento nuovo annuncio gratuito.
//

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

// Punto 2 (17 lug 2026): l'ospite compila, l'account si chiede al publish.
$user_id = current_user_id(); // null = ospite
$aow_lt = ((($_SESSION['ad_wizard']['module'] ?? '02')) === '03') ? 'prem' : 'free';

// Prefill dei campi. Da loggato: identita' dall'anagrafica + quota.
// Da ospite: campi vuoti, nessuna quota (il gate scatta al publish).
$author = ''; $email = ''; $phone = '';
$used = 0; $limit = 0;
$aow_draft_data = []; // eventuali valori da ripristinare da una bozza

if ($user_id !== null) {
  // Tier check preventivo: se ha gia' raggiunto il limite, non mostrare il form
  $check = ($aow_lt === 'prem')
    ? UserTier::canInsertPremiumAd($pdo, $user_id)
    : UserTier::canInsertFreeAd($pdo, $user_id);
  if (!$check['allowed']) {
    $_SESSION['error_message'] = $check['reason'];
    header('Location: ' . BASE_URL . '/01_login/my_posts.php');
    exit;
  }

  // Recupera identita' canonica da DB (non dal POST/sessione)
  $stmt = $pdo->prepare('SELECT username, email, phone FROM users WHERE id_user = :id LIMIT 1');
  $stmt->execute([':id' => $user_id]);
  $user_row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user_row) {
    $_SESSION['error_message'] = 'User session invalid. Please log in again.';
    logout_user();
    header('Location: ' . BASE_URL . '/01_login/newlogin.php');
    exit;
  }

  $author = (string)$user_row['username'];
  $email  = (string)$user_row['email'];
  $phone  = (string)$user_row['phone'];
  $used  = (int)$check['used'];
  $limit = (int)$check['limit'];

  // TRAVASO (prefill): se l'utente ha appena rivendicato una bozza compilata
  // da ospite (claim al login), il form riparte da li'. Non si pubblica in
  // automatico: l'utente rivede e conferma. La bozza si cancella dopo l'INSERT
  // riuscito (handler 02_01).
  try {
    require_once __DIR__ . '/../libs/ad_draft.class.php';
    $aow_drafts = AdDraft::forUser($pdo, (int)$user_id);
    if (!empty($aow_drafts)) {
      $aow_draft_data = $aow_drafts[0]['payload'] ?? [];
      $_SESSION['ad_wizard']['draft_id'] = (int)$aow_drafts[0]['id']; // per il delete dopo INSERT
    }
  } catch (Throwable $e) {
    error_log('[Allonwheel] insert_ad prefill bozza: ' . $e->getMessage());
  }
}

// Avvia wizard CSRF persistente (sara' valido per upload_advertising,
// upload_ad_image, upload_gallery — tutti gli step del wizard)
// ------------------------------------------------------------
// Classificazione obbligatoria (wizard 00_select_type): se assente
// o di modulo diverso, torna allo step di scelta categoria (flowchart).
// ------------------------------------------------------------
$wiz = $_SESSION['ad_wizard'] ?? null;
if (!is_array($wiz) || !in_array(($wiz['module'] ?? ''), ['02', '03'], true)) {
  header('Location: 02_00_select_type.php');
  exit;
}
$wiz_kind  = (string)($wiz['item_kind'] ?? VehicleTaxonomy::KIND_VEHICLE);
$wiz_macro = (string)($wiz['macro_category'] ?? VehicleTaxonomy::MACRO_SPECIAL);
$wiz_type  = (string)($wiz['vehicle_type'] ?? VehicleTaxonomy::SHELTER_SLUG);
$wiz_kind_label = ($wiz_kind === VehicleTaxonomy::KIND_SHELTER) ? 'Shelter / Container' : 'Vehicle';
$wiz_type_label = VehicleTaxonomy::label($wiz_type, $pdo);

csrf_generate_persistent();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Insert <?php echo $aow_lt === 'prem' ? 'premium' : 'free'; ?> ad</title>
<meta name="keywords" content="Insert free ad" />
<meta name="description" content="Insert your free advertising" />
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
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
    <div id="page_title">Insert your ad</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="post_box">
      <p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="post_box">
    <h2><?php echo $aow_lt === 'prem' ? 'Premium' : 'Free'; ?> ad form</h2>
    <p><strong>Step 2 of <?php echo $aow_lt === 'prem' ? 6 : 4; ?> &middot; Describe your vehicle</strong></p>
    <p>You are using <strong><?php echo $used; ?></strong> of your <strong><?php echo $limit; ?></strong> free ad slots.</p>
    <div id="contact_form">
    <?php
    // Prefill difensivo dei campi dalla bozza (vuoto se non c'e').
    $aow_dv = function (string $k) use ($aow_draft_data) {
      return htmlspecialchars((string)($aow_draft_data[$k] ?? ''), ENT_QUOTES, 'UTF-8');
    };
    // Ospite: il submit salva la bozza e porta a registrazione, non pubblica.
    $aow_is_guest = ($user_id === null);
    $aow_form_action = $aow_is_guest ? '02_save_draft.php' : '02_01_upload_advertising.php';
    ?>
    <form action="<?php echo $aow_form_action; ?>" method="post" >
      <?php echo csrf_generate_persistent(); ?>
      <div class="form_row">
        <label for="title"><strong>Title:</strong></label>
        <input type="text" name="title" id="title" class="input_field" required maxlength="200" value="<?php echo $aow_dv('title'); ?>" />
      </div>

      <div class="form_row">
        <label for="subtitle"><br>
        Subtitle:</label>
        <input type="text" name="subtitle" id="subtitle" class="input_field" maxlength="200" value="<?php echo $aow_dv('subtitle'); ?>" />
      </div>

      <div class="form_row">
        <label for="list_price"><br>
        List price (€):</label>
        <input type="number" min="1" name="list_price" id="list_price" class="input_field" value="<?php echo $aow_dv('list_price'); ?>"/>
      </div>

      <!-- Nessuna misura per gli annunci premium -->

      <div class="form_row">
        <label for="type"><br>
        Type:</label>
        <select name="type" id="type" class="input_field">
        <option value="New on sell"<?php echo $aow_dv('type')==='New'?' selected':''; ?>>New</option>
        <option value="Used on sell"<?php echo $aow_dv('type')==='Used'?' selected':''; ?>>Used</option>
        <option value="Project"<?php echo $aow_dv('type')==='Project'?' selected':''; ?>>Project</option>
        </select>
      </div>

      <div class="form_row">
        <label for="conditions"><br>
        Condition:</label>
        <select name="conditions" id="conditions" class="input_field">
        <option value="New"<?php echo $aow_dv('conditions')==='New'?' selected':''; ?>>New</option>
        <option value="As good as new"<?php echo $aow_dv('conditions')==='As good as new'?' selected':''; ?>>As good as new</option>
        <option value="Used"<?php echo $aow_dv('conditions')==='Used'?' selected':''; ?>>Used</option>
        <option value="Poor"<?php echo $aow_dv('conditions')==='Poor'?' selected':''; ?>>Poor</option>
        <option value="Project"<?php echo $aow_dv('conditions')==='Project'?' selected':''; ?>>Project</option>
        </select>
      </div>

      <div class="form_row">
        <label><br>
        Category:</label>
        <p>
        <strong><?php echo htmlspecialchars($wiz_kind_label, ENT_QUOTES, 'UTF-8'); ?></strong>
        <strong><?php echo htmlspecialchars(ucfirst($wiz_macro), ENT_QUOTES, 'UTF-8'); ?></strong>
        <strong><?php echo htmlspecialchars($wiz_type_label, ENT_QUOTES, 'UTF-8'); ?></strong>
        &nbsp; </p>
        <p><a href="02_00_select_type.php" class="more">Change</a>
        </p>
        <!-- Classificazione strutturata (flowchart) trasportata al salvataggio -->
        <input type="hidden" name="item_kind"      value="<?php echo htmlspecialchars($wiz_kind,  ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="macro_category" value="<?php echo htmlspecialchars($wiz_macro, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="vehicle_type"   value="<?php echo htmlspecialchars($wiz_type,  ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="form_row">
        <p>
<label for="description"><strong>Description:</strong></label><br/>
        <textarea name="description" id="description" class="required" rows="6" required><?php echo $aow_dv('description'); ?></textarea>
        </p>
        <p>&nbsp;</p>
      </div>

      <div class="form_row">
        <p>
        <button type="submit" name="submit" value="Continue" class="more float_r"><?php echo $aow_is_guest ? 'Register to publish' : 'Continue'; ?></button>				
      <p><em>You will be able to upload the main image in the next step.</em></p>
        <p><a href="../01_login/my_posts.php" class="more float_l">Cancel</a></p>
      </div>
      <p class="required-note">&nbsp;</p>
      &nbsp;
      </p>
    </form>
    </div>
    </div>
  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include __DIR__ . '/../footer.php'; ?>
</div>
</body>
</html>
