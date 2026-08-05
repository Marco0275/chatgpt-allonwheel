<?php
// 05_wanted/wanted_post.php — Inserimento richiesta inversa "Wanted" (login richiesto).
// Alla pubblicazione notifica i venditori con annunci approvati nella stessa macro.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/antispam.php';
require_once __DIR__ . '/../includes/form_consent.php';
require_once __DIR__ . '/../libs/wanted_ads.class.php';

$id_user = require_user_logged_in();
$wanted  = new WantedAds($pdo);

$macros = $pdo->query('SELECT slug, name FROM `product_macros` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
// (l'elenco piatto delle tipologie non serve piu': il selettore gerarchico
//  carica da solo la lista giusta per la categoria scelta)
$macro_slugs = array_column($macros, 'slug');

$errors = [];
$val = ['title' => '', 'category' => '', 'macro' => '', 'vehicle_type' => '', 'budget' => '', 'country_code' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (aow_is_spam()) { $_SESSION['error_message'] = 'Spam detected.'; header('Location: wanted_post.php'); exit; }
    if (!aow_privacy_consent_ok()) { $_SESSION['error_message'] = 'Please accept the privacy policy.'; header('Location: wanted_post.php'); exit; }
    aow_log_form_consent($pdo, 'wanted');
    foreach ($val as $k => $_) { $val[$k] = trim((string)($_POST[$k] ?? '')); }
    $val['country_code'] = strtoupper($val['country_code']);

    if ($val['title'] === '' || mb_strlen($val['title']) > 255) $errors[] = 'Title is required (max 255 chars).';
    // La categoria (road/special/shelter) e' la scelta dell'utente; la
    // famiglia commerciale si DERIVA da categoria + tipologia, come fa il
    // wizard di inserimento, invece di essere scelta a mano.
    if (!in_array($val['category'], ['road', 'special', 'shelter'], true)) {
        $errors[] = 'Please choose a category.';
    } else {
        require_once __DIR__ . '/../libs/product_macro.class.php';
        require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
        $aow_kind = ($val['category'] === 'shelter')
            ? VehicleTaxonomy::KIND_SHELTER : VehicleTaxonomy::KIND_VEHICLE;
        if ($val['category'] === 'shelter') { $val['vehicle_type'] = VehicleTaxonomy::SHELTER_SLUG; }
        $val['macro'] = (string)(ProductMacro::forSelection($aow_kind, (string)$val['vehicle_type']) ?? '');
        if ($val['macro'] === '') {
            // Nessuna famiglia specifica: si usa custom-projects, come per gli
            // annunci che non ricadono in una famiglia dedicata.
            $val['macro'] = ProductMacro::CUSTOM;
        }
    }
    // La tipologia si valida contro la tabella della categoria scelta
    // (road -> vehicle_types, special/shelter -> special_types) e non piu'
    // contro un elenco unico: con la nuova tassonomia una voce speciale non
    // sta piu' in vehicle_types, e verrebbe rifiutata a torto.
    if ($val['vehicle_type'] !== '' && in_array($val['category'], ['road','special','shelter'], true)
        && !VehicleTaxonomy::isValidForCategory($val['vehicle_type'], $val['category'], $pdo)) {
        $errors[] = 'Invalid vehicle type for the chosen category.';
    }
    if ($val['description'] === '')                             $errors[] = 'Description is required.';
    if ($val['country_code'] !== '' && !preg_match('/^[A-Z]{2}$/', $val['country_code'])) $errors[] = 'Country must be a 2-letter code (e.g. IT).';
    $budget = null;
    if ($val['budget'] !== '') {
        $budget = (float)str_replace([',', ' '], ['.', ''], $val['budget']);
        if ($budget < 0) $errors[] = 'Budget must be a positive number.';
    }

    if (!$errors) {
        $new_id = $wanted->create($id_user, $val['title'], $val['macro'], ($val['vehicle_type'] ?: null),
                                  $budget, ($val['country_code'] ?: null), $val['description']);
        try {
            $wanted->notifySellers(['id' => $new_id, 'title' => $val['title'], 'macro' => $val['macro'], 'vehicle_type' => ($val['vehicle_type'] ?: null), 'id_user' => $id_user]);
        } catch (Throwable $ex) {
            error_log('[Allonwheel] wanted notifySellers: ' . $ex->getMessage());
        }
        $_SESSION['aow_wanted_notice'] = 'Your request has been posted. Matching sellers were notified.';
        header('Location: ' . BASE_URL . '/05_wanted/wanted_view.php?id=' . $new_id);
        exit;
    }
}

csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Post a wanted request</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Post a wanted request</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Tell sellers what you are looking for</h2>
      <p>Post a "wanted" request: sellers offering that type of vehicle will be notified and can respond to you.</p>
      <?php if ($errors): ?>
        <ul><?php foreach ($errors as $er): ?><li><strong><?php echo $e($er); ?></strong></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <form method="post" action="wanted_post.php">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
        <?php echo aow_spam_fields(); ?>
        <p><label>Title:<br><input type="text" name="title" size="50" maxlength="255" value="<?php echo $e($val['title']); ?>" /></label></p>
        <?php // Gerarchia identica al wizard di inserimento annunci: prima la
              // categoria (Road / Special / Shelter), poi la tipologia, filtrata
              // dalla categoria scelta. Il significato delle tre categorie e'
              // documentato in shared/category_hierarchy.php.
              // La famiglia (product_macro) NON si sceglie a mano: viene derivata
              // dalla coppia categoria+tipologia, come nell'inserimento.
              $aow_ch_name_cat  = 'category';
              $aow_ch_name_type = 'vehicle_type';
              $aow_ch_cat       = $val['category'] ?? '';
              $aow_ch_type      = $val['vehicle_type'] ?? '';
              $aow_ch_required  = false;
              include __DIR__ . '/../shared/category_hierarchy.php';
        ?>
        <p><label>Budget &euro; (optional):<br><input type="text" name="budget" size="20" value="<?php echo $e($val['budget']); ?>" /></label></p>
        <p><label>Country (2-letter, optional):<br><input type="text" name="country_code" size="4" maxlength="2" value="<?php echo $e($val['country_code']); ?>" /></label></p>
        <p><label>Description:<br><textarea name="description" rows="6" cols="60"><?php echo $e($val['description']); ?></textarea></label></p>
        <?php require_once __DIR__ . '/../includes/form_consent.php'; echo aow_privacy_consent_field(); ?>
        <div class="post_meta">
			<button type="submit" value="Post request" class="more float_r">Post request</button>
		  </div>
        <div class="cleaner"></div>
      </form>
    </div>
  </div><!-- end templatemo_content -->

  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div><!-- end templatemo_sidebar -->

  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
