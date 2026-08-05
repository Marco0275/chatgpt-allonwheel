<?php
/**
 * 06_20_modify_company.php — Modifica profilo azienda
 *
 * MODIFICHE rispetto alla versione precedente:
 *  - Migrato a bootstrap + session_helper (require_user_logged_in)
 *  - Aggiunto upload logo con UploadHelper: preview logo attuale +
 *    possibilità di sostituirlo; vecchio file rimosso dal disco al salvataggio
 *  - Form con enctype multipart/form-data
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

$user_id = require_user_logged_in();

$cm = new CompanyManager($pdo);
$company = $cm->getCompanyByUserId($user_id);

if (!$company) {
  $_SESSION['error_message'] = 'No company found. Please register first.';
  header('Location: /06_company/06_10_register_company.php');
  exit;
}

// Handler POST: salvataggio modifiche
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $new_logo = $company['logo']; // default: mantieni logo esistente

  // Gestione upload nuovo logo (opzionale)
  if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $logo_result = UploadHelper::handleImageUpload($_FILES['logo'], [
      'target_dir_original'  => '/upload_image/06_company/original/',
      'target_dir_thumbnail' => '/upload_image/06_company/thumbnail/',
      'thumb_width'          => 220,
      'thumb_height'         => 150,
      'thumb_crop'           => true,
      'max_size_bytes'       => 5 * 1024 * 1024,
      'filename_prefix'      => 'logo_' . $user_id,
    ]);

    if (!$logo_result['ok']) {
      $_SESSION['error_message'] = 'Logo upload failed: ' . $logo_result['error'];
      header('Location: /06_company/06_20_modify_company.php');
      exit;
    }

    // Rimuovi il vecchio logo dal disco (original + thumbnail)
    if (!empty($company['logo'])) {
      $base_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/';
      foreach (['original', 'thumbnail'] as $sub) {
        $old_path = $base_dir . $sub . '/' . $company['logo'];
        if (is_file($old_path)) {
          @unlink($old_path);
        }
      }
    }

    $new_logo = $logo_result['filename'];
  }

  $data = [
    'user_id'             => $user_id,
    'ragione_sociale'     => trim($_POST['ragione_sociale'] ?? ''),
    'partita_iva'         => trim($_POST['partita_iva'] ?? ''),
    'codice_fiscale'      => trim($_POST['codice_fiscale'] ?? ''),
    'indirizzo'           => trim($_POST['indirizzo'] ?? ''),
    'cap'                 => trim($_POST['cap'] ?? ''),
    'citta'               => trim($_POST['citta'] ?? ''),
    'provincia'           => trim($_POST['provincia'] ?? ''),
    'nazione'             => trim($_POST['nazione'] ?? 'Italia'),
    'telefono'            => trim($_POST['telefono'] ?? ''),
    'cellulare'           => trim($_POST['cellulare'] ?? ''),
    'fax'                 => trim($_POST['fax'] ?? ''),
    'email'               => trim($_POST['email'] ?? ''),
    'pec'                 => trim($_POST['pec'] ?? ''),
    'sito_web'            => trim($_POST['sito_web'] ?? ''),
    'descrizione'         => trim($_POST['descrizione'] ?? ''),
    'logo'                => $new_logo,
    'referente_nome'      => trim($_POST['referente_nome'] ?? ''),
    'referente_cognome'   => trim($_POST['referente_cognome'] ?? ''),
    'referente_ruolo'     => trim($_POST['referente_ruolo'] ?? ''),
    'referente_email'     => trim($_POST['referente_email'] ?? ''),
    'referente_telefono'  => trim($_POST['referente_telefono'] ?? ''),
  ];

  if (empty($data['ragione_sociale']) || empty($data['partita_iva']) || empty($data['email'])) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: /06_company/06_20_modify_company.php');
    exit;
  }

  if ($cm->updateCompany($company['id'], $data)) {
    $cm->saveCompanyPrefs($company['id'], trim($_POST['descrizione_it'] ?? ''), !empty($_POST['wants_pm_list']));
    // Aggiorna anche tipologie veicolo e servizi accessori
    $products_data = [];
    foreach (CompanyManager::productsRoad($pdo, array_keys($cm->getProducts($company['id']))) as $key => $label) {
      if (isset($_POST['product'][$key])) {
        $products_data[] = [
          'product_key'             => $key,
          'note'                    => trim($_POST['product_note'][$key] ?? ''),
          'certificazioni_prodotto' => 0,
          'campioni_gratuiti'       => 0,
          'assistenza_posa'         => 0,
          'progettazione_supporto'  => 0,
          'schede_tecniche'         => 0,
        ];
      }
    }
    $services_data = [];
    foreach (CompanyManager::$services as $key => $label) {
      if (isset($_POST['service'][$key])) {
        $services_data[] = [
          'service_key' => $key,
          'note'        => trim($_POST['service_note'][$key] ?? ''),
        ];
      }
    }
    $cm->saveProducts($company['id'], $products_data);
    $cm->saveServices($company['id'], $services_data);
    // Categorie speciali -> 06_company_products_special
    $special_data = [];
    foreach (CompanyManager::productsSpecial($pdo, array_keys($cm->getProductsSpecial($company['id']))) as $key => $label) {
      if (isset($_POST['product_special'][$key])) {
        $special_data[] = [
          'product_key' => $key,
          'note'        => trim($_POST['product_special_note'][$key] ?? ''),
        ];
      }
    }
    $cm->saveProductsSpecial($company['id'], $special_data);

    $_SESSION['success_message'] = 'Company updated successfully.';
    header('Location: /06_company/06_02_view_company.php?id=' . $company['id']);
  } else {
    $_SESSION['error_message'] = 'Error updating company. Please try again.';
    header('Location: /06_company/06_20_modify_company.php');
  }
  exit;
}

$c = $company; // shortcut

// Carica products e services esistenti per pre-check checkbox
$current_products = $cm->getProducts($company['id']);
$current_services = $cm->getServices($company['id']);
$current_special  = $cm->getProductsSpecial($company['id']);
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Modify Company</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.ico" />
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
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top">
    <div id="page_title">Modify Company</div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="post_box">
      <p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    </div>
    <?php endif; ?>

    <div class="post_box">
    <div id="contact_form">
    <form method="post" action="06_20_modify_company.php" enctype="multipart/form-data">
      <?php echo csrf_generate(); ?>

      <div class="float_l">
        <label for="ragione_sociale"><strong>*</strong> Company name:</label>
        <input type="text" name="ragione_sociale" id="ragione_sociale" required maxlength="255" value="<?php echo htmlspecialchars($c['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="partita_iva"><strong>*</strong> VAT Number:</label>
        <input type="text" name="partita_iva" id="partita_iva" required maxlength="20" value="<?php echo htmlspecialchars($c['partita_iva'], ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="codice_fiscale">Tax Code:</label>
        <input type="text" name="codice_fiscale" id="codice_fiscale" maxlength="20" value="<?php echo htmlspecialchars($c['codice_fiscale'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="indirizzo"><strong>*</strong> Address:</label>
        <input type="text" name="indirizzo" id="indirizzo" required maxlength="255" value="<?php echo htmlspecialchars($c['indirizzo'], ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <table width="100%" border="0"><tr>
        <td><label for="cap"><strong>*</strong> Postal code:</label><input type="text" name="cap" id="cap" required maxlength="10" value="<?php echo htmlspecialchars($c['cap'], ENT_QUOTES, 'UTF-8'); ?>" class="w80" /></td>
        <td><label for="citta"><strong>*</strong> City:</label><input type="text" name="citta" id="citta" required maxlength="100" value="<?php echo htmlspecialchars($c['citta'], ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><label for="provincia"><strong>*</strong> Province:</label><input type="text" name="provincia" id="provincia" required maxlength="5" value="<?php echo htmlspecialchars($c['provincia'], ENT_QUOTES, 'UTF-8'); ?>" class="w60" /></td>
      </tr></table>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="nazione">Country:</label>
        <input type="text" name="nazione" id="nazione" maxlength="100" value="<?php echo htmlspecialchars($c['nazione'] ?? 'Italia', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <table width="100%" border="0"><tr>
        <td><label for="telefono">Phone:</label><input type="text" name="telefono" id="telefono" maxlength="30" value="<?php echo htmlspecialchars($c['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><label for="cellulare">Mobile:</label><input type="text" name="cellulare" id="cellulare" maxlength="30" value="<?php echo htmlspecialchars($c['cellulare'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><label for="fax">Fax:</label><input type="text" name="fax" id="fax" maxlength="30" value="<?php echo htmlspecialchars($c['fax'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
      </tr></table>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="email_company"><strong>*</strong> Company e-mail:</label>
        <input type="email" name="email" id="email_company" required maxlength="255" value="<?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="pec">PEC:</label>
        <input type="email" name="pec" id="pec" maxlength="255" value="<?php echo htmlspecialchars($c['pec'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="sito_web">Website:</label>
        <input type="url" name="sito_web" id="sito_web" maxlength="255" value="<?php echo htmlspecialchars($c['sito_web'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h20"></div>
      <label for="descrizione"><strong>Description:</strong></label>
      <textarea id="descrizione" name="descrizione" rows="5" cols="50"><?php echo htmlspecialchars($c['descrizione'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      <div class="cleaner h20"></div>
      <label for="descrizione_it"><strong>Description (Italian):</strong></label>
      <textarea id="descrizione_it" name="descrizione_it" rows="5" cols="50"><?php echo htmlspecialchars($c['descrizione_it'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      <div class="cleaner h20"></div>
      <label><input type="checkbox" name="wants_pm_list" value="1"<?php echo !empty($c['wants_pm_list']) ? ' checked' : ''; ?> /> Receive the list of project managers &amp; consultants</label>
      <div class="cleaner h20"></div>

      <h3>Company logo</h3>
      <?php if (!empty($c['logo'])): ?>
      <p>
        <strong>Current logo:</strong><br />
        <a class="pirobox" href="/upload_image/06_company/original/<?php echo htmlspecialchars($c['logo'], ENT_QUOTES, 'UTF-8'); ?>" title="Company logo">
          <img src="/upload_image/06_company/thumbnail/<?php echo htmlspecialchars($c['logo'], ENT_QUOTES, 'UTF-8'); ?>"
               alt="Current logo" class="img_preview" loading="lazy" decoding="async" />
        </a>
      </p>
      <p>Upload a new file below to replace it, or leave blank to keep the current logo.</p>
      <?php else: ?>
      <p>No logo uploaded yet. Upload one below (JPG, PNG or GIF — max 5 MB).</p>
      <?php endif; ?>
      <div>
        <label for="logo">Logo:</label>
        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif" />
      </div>
      <div class="cleaner h20"></div>

      <h3>Contact person</h3>
      <table width="100%" border="0">
        <tr>
        <td><label for="referente_nome">First name:</label><input type="text" name="referente_nome" id="referente_nome" maxlength="100" value="<?php echo htmlspecialchars($c['referente_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><label for="referente_cognome">Last name:</label><input type="text" name="referente_cognome" id="referente_cognome" maxlength="100" value="<?php echo htmlspecialchars($c['referente_cognome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        </tr>
        <tr>
        <td><label for="referente_ruolo">Role:</label><input type="text" name="referente_ruolo" id="referente_ruolo" maxlength="100" value="<?php echo htmlspecialchars($c['referente_ruolo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><label for="referente_email">E-mail:</label><input type="email" name="referente_email" id="referente_email" maxlength="255" value="<?php echo htmlspecialchars($c['referente_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        </tr>
        <tr>
        <td><label for="referente_telefono">Phone:</label><input type="text" name="referente_telefono" id="referente_telefono" maxlength="30" value="<?php echo htmlspecialchars($c['referente_telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td>&nbsp;</td>
        </tr>
      </table>
      <div class="cleaner h20"></div>

      <h3>Accessory services</h3>
      <p>Select the services your company provides:</p>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
        <thead>
        <tr class="thead_row">
          <th width="5%">&nbsp;</th>
          <th width="40%">Service</th>
          <th>Note (optional)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::$services as $key => $label):
          $checked_srv = isset($current_services[$key]);
          $note_srv    = (string)($current_services[$key]['note'] ?? '');
        ?>
        <tr class="row_sep">
          <td align="center">
            <input type="checkbox" name="service[<?php echo $key; ?>]" value="1"
                   <?php echo $checked_srv ? 'checked="checked"' : ''; ?> />
          </td>
          <td><?php echo htmlspecialchars($label); ?></td>
          <td>
            <input type="text" name="service_note[<?php echo $key; ?>]"
                   value="<?php echo htmlspecialchars($note_srv); ?>"
                   maxlength="255" class="w95" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cleaner h20"></div>

      <h3>Vehicle body types</h3>
      <p>Select the special vehicle types your company builds or supplies:</p>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
        <thead>
        <tr class="thead_row">
          <th width="5%">&nbsp;</th>
          <th width="40%">Type</th>
          <th>Note (optional)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::productsRoad($pdo, array_keys($current_products)) as $key => $label):
          $checked_prd = isset($current_products[$key]);
          $note_prd    = (string)($current_products[$key]['note'] ?? '');
        ?>
        <tr class="row_sep">
          <td align="center">
            <input type="checkbox" name="product[<?php echo $key; ?>]" value="1"
                   <?php echo $checked_prd ? 'checked="checked"' : ''; ?> />
          </td>
          <td><?php echo htmlspecialchars($label); ?></td>
          <td>
            <input type="text" name="product_note[<?php echo $key; ?>]"
                   value="<?php echo htmlspecialchars($note_prd); ?>"
                   maxlength="255" class="w95" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cleaner h20"></div>

      <h3>Special categories</h3>
      <p>Select the special categories your company builds or supplies:</p>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
        <thead>
        <tr class="thead_row">
          <th width="5%">&nbsp;</th>
          <th width="40%">Category</th>
          <th>Note (optional)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::productsSpecial($pdo, array_keys($current_special)) as $key => $label):
          $checked_sp = isset($current_special[$key]);
          $note_sp    = (string)($current_special[$key]['note'] ?? '');
        ?>
        <tr class="row_sep">
          <td align="center">
            <input type="checkbox" name="product_special[<?php echo $key; ?>]" value="1"
                   <?php echo $checked_sp ? 'checked="checked"' : ''; ?> />
          </td>
          <td><?php echo htmlspecialchars($label); ?></td>
          <td>
            <input type="text" name="product_special_note[<?php echo $key; ?>]"
                   value="<?php echo htmlspecialchars($note_sp); ?>"
                   maxlength="255" class="w95" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cleaner h20"></div>
      <button type="submit" name="submit" id="submit" value="Save" class="more float_r">Save</button>
      <a href="06_02_view_company.php?id=<?php echo (int)$c['id']; ?>" class="more float_l">Cancel</a>
    </form>
    </div>
    </div>

  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <div><?php include('../footer.php'); ?></div>
</div>
</body>
</html>
