<?php
/**
 * 06_10_register_company.php — Form registrazione azienda fornitrice
 *
 * MODIFICHE rispetto alla versione precedente:
 *  - Migrato a bootstrap + session_helper (require_user_logged_in)
 *  - Aggiunto campo logo (file upload) con enctype multipart/form-data
 *  - Logo processato da 06_11_save_company.php via UploadHelper
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';

$user_id = require_user_logged_in();

// Precompila i campi con i dati dell'utente loggato (se disponibili).
$aow_u = ['username' => '', 'email' => '', 'phone' => ''];
try {
  $aow_ust = $pdo->prepare('SELECT username, email, phone FROM `users` WHERE id_user = :u LIMIT 1');
  $aow_ust->execute([':u' => $user_id]);
  $aow_u = $aow_ust->fetch(PDO::FETCH_ASSOC) ?: $aow_u;
} catch (Throwable $e) { /* prefill best-effort */ }
$aow_pf = static fn(string $uk, string $pk): string => htmlspecialchars((string)($_POST[$pk] ?? ($aow_u[$uk] ?? '')), ENT_QUOTES, 'UTF-8');

// Se l'utente ha già un'azienda, redirect alla modifica
$cm = new CompanyManager($pdo);
$existing_id = $cm->userHasCompany($user_id);
if ($existing_id) {
  $_SESSION['success_message'] = 'You already have a registered company. You can modify it below.';
  header('Location: /06_company/06_20_modify_company.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Register Company</title>
<meta name="keywords" content="All on Wheel - Register Company" />
<meta name="description" content="Register your company on All on Wheel supplier directory" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include('../header.php'); ?>
  </div>
  <div id="content_top">
    <div id="page_title">Register your Company</div>
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
      <p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    </div>
    <?php endif; ?>

    <div class="post_box">
    <h2>Company details</h2>
    <p>Fill in your company information to be listed in our Supplier Directory.</p>
    <p><strong>(*) Required fields</strong></p>

    <div id="contact_form">
    <form method="post" action="06_11_save_company.php" enctype="multipart/form-data" id="register_company_form">
      <?php echo csrf_generate(); ?>

      <div>
        <label for="ragione_sociale"><strong>*</strong> Company name:</label>
        <input type="text" name="ragione_sociale" id="ragione_sociale" required maxlength="255" />
      </div>
      <div class="cleaner h10"></div>

      <div>
        <label for="partita_iva"><strong>*</strong> VAT Number:</label>
        <input type="text" name="partita_iva" id="partita_iva" required maxlength="20" />
      </div>
      <div class="cleaner h10"></div>

      <div>
        <label for="codice_fiscale">Tax Code:</label>
        <input type="text" name="codice_fiscale" id="codice_fiscale" maxlength="20" />
      </div>
      <div class="cleaner h10"></div>

      <div>
        <label for="indirizzo"><strong>*</strong> Address:</label>
        <input type="text" name="indirizzo" id="indirizzo" required maxlength="255" />
      </div>
      <div class="cleaner h10"></div>

      <table width="100%" border="0">
        <tr>
        <td>
          <label for="cap"><strong>*</strong> Postal code:</label>
          <input type="text" name="cap" id="cap" required maxlength="10" class="w80" />
        </td>
        <td>
          <label for="citta"><strong>*</strong> City:</label>
          <input type="text" name="citta" id="citta" required maxlength="100" />
        </td>
        <td>
          <label for="provincia"><strong>*</strong> Province:</label>
          <input type="text" name="provincia" id="provincia" required maxlength="5" class="w60" />
        </td>
        </tr>
      </table>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="nazione">Country:</label>
        <input type="text" name="nazione" id="nazione" value="Italia" maxlength="100" />
      </div>
      <div class="cleaner h10"></div>

      <table width="100%" border="0">
        <tr>
        <td>
          <label for="telefono">Phone:</label>
          <input type="tel" name="telefono" id="telefono" maxlength="30" value="<?php echo $aow_pf('phone','telefono'); ?>" />
        </td>
        <td>
          <label for="cellulare">Mobile:</label>
          <input type="text" name="cellulare" id="cellulare" maxlength="30" />
        </td>
        <td>
          <label for="fax">Fax:</label>
          <input type="text" name="fax" id="fax" maxlength="30" />
        </td>
        </tr>
      </table>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="email_company"><strong>*</strong> Company e-mail:</label>
        <input type="email" name="email" id="email_company" required maxlength="255" value="<?php echo $aow_pf('email','email'); ?>" />
      </div>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="pec">PEC:</label>
        <input type="email" name="pec" id="pec" maxlength="255" />
      </div>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="sito_web">Website:</label>
        <input type="url" name="sito_web" id="sito_web" maxlength="255" placeholder="https://" />
      </div>
      <div class="cleaner h20"></div>

      <label for="descrizione"><strong>Description:</strong></label>
      <textarea id="descrizione" name="descrizione" rows="5" cols="50"></textarea>
      <div class="cleaner h20"></div>
      <label for="descrizione_it"><strong>Description (Italian):</strong></label>
      <textarea id="descrizione_it" name="descrizione_it" rows="5" cols="50"></textarea>
      <div class="cleaner h20"></div>
      <label><input type="checkbox" name="wants_pm_list" value="1" /> Receive the list of project managers &amp; consultants</label>
      <div class="cleaner h20"></div>

      <h3>Company logo</h3>
      <p>Upload your company logo (JPG, PNG or GIF — max 5 MB).</p>
      <div>
        <label for="logo">Logo:</label>
        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif" />
      </div>
      <div class="cleaner h20"></div>

      <h3>Contact person</h3>
      <table width="100%" border="0">
        <tr>
        <td>
          <label for="referente_nome">First name:</label>
          <input type="text" name="referente_nome" id="referente_nome" maxlength="100" value="<?php echo $aow_pf('username','referente_nome'); ?>" />
        </td>
        <td>
          <label for="referente_cognome">Last name:</label>
          <input type="text" name="referente_cognome" id="referente_cognome" maxlength="100" />
        </td>
        </tr>
        <tr>
        <td>
          <label for="referente_ruolo">Role:</label>
          <input type="text" name="referente_ruolo" id="referente_ruolo" maxlength="100" />
        </td>
        <td>
          <label for="referente_email">E-mail:</label>
          <input type="email" name="referente_email" id="referente_email" maxlength="255" />
        </td>
        </tr>
        <tr>
        <td>
          <label for="referente_telefono">Phone:</label>
          <input type="text" name="referente_telefono" id="referente_telefono" maxlength="30" />
        </td>
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
          <th>Service</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::$services as $key => $label): ?>
        <tr class="row_sep">
          <td align="center"><input type="checkbox" name="service[<?php echo $key; ?>]" value="1" /></td>
          <td><?php echo htmlspecialchars(tsvc($key, $label)); ?></td>
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
          <th>Type</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::productsRoad($pdo) as $key => $label): ?>
        <tr class="row_sep">
          <td align="center"><input type="checkbox" name="product[<?php echo $key; ?>]" value="1" /></td>
          <td><?php echo htmlspecialchars(tcat($key, $label)); ?></td>
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
          <th>Category</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (CompanyManager::productsSpecial($pdo) as $key => $label): ?>
        <tr class="row_sep">
          <td align="center"><input type="checkbox" name="product_special[<?php echo $key; ?>]" value="1" /></td>
          <td><?php echo htmlspecialchars(tcat($key, $label)); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <h3>Rental of special vehicles</h3>
      <label class="rent_optin"><input type="checkbox" name="offers_rental" value="1" /> This company also offers <strong>rental</strong> of special vehicles</label>
      <p class="rent_notice"><strong>Please note:</strong> if you enable rental and publish rental listings, you will receive by e-mail the rental requests submitted by users whose selected vehicle types match your published listings. Requests are delivered according to your plan (free / premium / gold).</p>

      <div class="cleaner h20"></div>

      <h3>Note</h3>
      <p>Any additional information about your services, vehicle types or rental offer:</p>
      <textarea id="general_note" name="general_note" rows="4" cols="50" maxlength="2000"></textarea>

      <div class="cleaner h20"></div>
      <button type="submit" name="submit" id="submit" value="Register" class="more float_r">Register</button>
    </form>
    </div>
    </div>

  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <div>
    <?php include('../footer.php'); ?>
  </div>
</div>
</body>
</html>
