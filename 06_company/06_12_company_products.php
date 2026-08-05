<?php
/**
 * 06_12_company_products.php (Phase 5f)
 * Gestione tipologie di allestimento + servizi accessori dell'azienda.
 *
 * Layout coerente con la matrice "Allestitori speciali" (immagine ref):
 *  - Header read-only con dati anagrafici (già inseriti in 06_10_register_company)
 *  - Sezione "Servizi accessori" (6 checkbox)
 *  - Sezione "Tipologie di allestimento" (27 checkbox)
 *  - Campo Note libero
 *
 * NON richiede dati già presenti in altri form.
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';

$user_id = require_user_logged_in();

$cm = new CompanyManager($pdo);
$company = $cm->getCompanyByUserId($user_id);
if (!$company) {
  $_SESSION['error_message'] = 'No company found. Register first.';
  header('Location: /06_company/06_10_register_company.php');
  exit;
}

$company_id = (int)$company['id'];

// Handler POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  // Tipologie di allestimento → tabella 06_company_products
  // (gli attributi extra del vecchio dominio non sono più richiesti
  //  dall'immagine "Allestitori speciali"; restano colonne in DB ma
  //  vengono salvate sempre a 0)
  $products_data = [];
  foreach (CompanyManager::productsRoad($pdo, array_keys($cm->getProducts($company_id))) as $key => $label) {
    if (isset($_POST['product'][$key])) {
    $products_data[] = [
      'product_key'      => $key,
      'note'       => trim($_POST['product_note'][$key] ?? ''),
      'certificazioni_prodotto'  => 0,
      'campioni_gratuiti'    => 0,
      'assistenza_posa'    => 0,
      'progettazione_supporto' => 0,
      'schede_tecniche'    => 0,
    ];
    }
  }

  // Servizi accessori → tabella 06_company_services
  $services_data = [];
  foreach (CompanyManager::$services as $key => $label) {
    if (isset($_POST['service'][$key])) {
    $services_data[] = [
      'service_key' => $key,
      'note'    => trim($_POST['service_note'][$key] ?? ''),
    ];
    }
  }

  // Note generale (salvata su descrizione azienda se non vuota — opzionale)
  $note_generale = trim($_POST['note_generale'] ?? '');
  if (isset($_POST['note_generale'])) {
    $stmt = $pdo->prepare(
    'UPDATE `06_company` SET descrizione = :note WHERE id = :id AND user_id = :user_id'
    );
    try {
    $stmt->execute([':note' => $note_generale, ':id' => $company_id, ':user_id' => $user_id]);
    } catch (PDOException $e) {
    error_log('[Allonwheel] descrizione column update failed: ' . $e->getMessage());
    }
  }

  $cm->saveProducts($company_id, $products_data);
  $cm->saveServices($company_id, $services_data);

  $_SESSION['success_message'] = 'Vehicle types and accessory services updated.';
  header('Location: /06_company/06_02_view_company.php?id=' . $company_id);
  exit;
}

// Carica dati esistenti per pre-check delle checkbox
$current_products = $cm->getProducts($company_id);
$current_services = $cm->getServices($company_id);
$current_note   = (string)($company['descrizione'] ?? '');

csrf_generate();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Vehicle types &amp; services</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
<div id="templatemo_header"><?php include __DIR__ . '/../header.php'; ?></div>
<div id="content_top">
  <div id="page_title">Vehicle types &amp; services</div>
  <div class="cleaner"></div>
</div>

<div id="main"></div><div id="templatemo_content">

  <!-- Header anagrafico (READ-ONLY, dati già inseriti in 06_10_register_company) -->
  <div class="post_box">
    <h2><?php echo htmlspecialchars($company['ragione_sociale']); ?></h2>
    <p>
    <?php echo nl2br(htmlspecialchars($company['indirizzo'] ?? '')); ?><br />
    <?php echo htmlspecialchars($company['citta'] ?? ''); ?>
    <?php if (!empty($company['provincia'])): ?> (<?php echo htmlspecialchars($company['provincia']); ?>)<?php endif; ?>
    <?php if (!empty($company['telefono'])): ?>
      <br />Phone: <?php echo htmlspecialchars($company['telefono']); ?>
    <?php endif; ?>
    <?php if (!empty($company['sito_web'])): ?>
      <br /><a href="<?php echo htmlspecialchars($company['sito_web']); ?>" target="_blank" rel="noopener">
        <?php echo htmlspecialchars($company['sito_web']); ?>
      </a>
    <?php endif; ?>
    <?php if (!empty($company['email'])): ?>
      <br /><?php echo htmlspecialchars($company['email']); ?>
    <?php endif; ?>
    </p>
    <p><em>To edit company details: <a href="06_20_modify_company.php">Edit company details</a></em></p>
  </div>

  <div id="contact_form">

    <form method="post" action="06_12_company_products.php">
    <?php echo csrf_generate(); ?>

    <!-- Section 1: Accessory services -->
    <div class="post_box">
      <h3>Accessory services</h3>
      <p>Select the services your company provides:</p>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
        <thead>
        <tr class="thead_row">
          <th width="5%">&nbsp;</th>
          <th width="35%" align="left">Service</th>
          <th align="left">Note (optional)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::$services as $key => $label):
          $checked = isset($current_services[$key]);
          $note  = (string)($current_services[$key]['note'] ?? '');
        ?>
        <tr class="row_sep">
          <td align="center">
            <input type="checkbox" name="service[<?php echo $key; ?>]" value="1"
             <?php echo $checked ? 'checked="checked"' : ''; ?> />
          </td>
          <td><label for="srv_<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></label></td>
          <td>
            <input type="text" id="srv_<?php echo $key; ?>"
             name="service_note[<?php echo $key; ?>]"
             value="<?php echo htmlspecialchars($note); ?>"
             maxlength="255"
             class="w95" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Section 2: Vehicle body types -->
    <div class="post_box">
      <h3>Vehicle body types</h3>
      <p>Select the special vehicle types your company builds:</p>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
        <thead>
        <tr class="thead_row">
          <th width="5%">&nbsp;</th>
          <th width="35%" align="left">Type</th>
          <th align="left">Note (optional)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::productsRoad($pdo, array_keys($current_products)) as $key => $label):
          $checked = isset($current_products[$key]);
          $note  = (string)($current_products[$key]['note'] ?? '');
        ?>
        <tr class="row_sep">
          <td align="center">
            <input type="checkbox" name="product[<?php echo $key; ?>]" value="1"
             <?php echo $checked ? 'checked="checked"' : ''; ?> />
          </td>
          <td><label for="prod_<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></label></td>
          <td>
            <input type="text" id="prod_<?php echo $key; ?>"
             name="product_note[<?php echo $key; ?>]"
             value="<?php echo htmlspecialchars($note); ?>"
             maxlength="255"
             class="w95" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Section 3: General notes -->
    <div class="post_box">
      <h3>Business description</h3>
      <p>Free notes about your company (will be visible on the public profile):</p>
      <textarea name="note_generale" rows="6" class="w100" maxlength="3000"><?php echo htmlspecialchars((string)($current_note ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <div class="post_box">
      <button type="submit" name="submit" id="submit" value="Save" class="more float_r">Save</button>
      <a href="06_02_view_company.php?id=<?php echo $company_id; ?>" class="submit_btn float_l">Cancel</a>
      <div class="cleaner"></div>
    </div>
    </form>
  </div>

</div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<div><?php include __DIR__ . '/../footer.php'; ?></div>
</div>
</body>
</html>
