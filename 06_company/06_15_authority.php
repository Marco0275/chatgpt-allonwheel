<?php
/**
 * 06_15_authority.php — Credenziali / Authority azienda (solo proprietario).
 * Carica certificati ISO (9001/14001/45001) + associazioni, referenze, area servita.
 * Aggiorna SOLO le colonne authority via PDO (non tocca CompanyManager insert/update).
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/upload_security.class.php';

$user_id = require_user_logged_in();

$cm = new CompanyManager($pdo);
$company = $cm->getCompanyByUserId($user_id);
if (!$company) {
  $_SESSION['error_message'] = 'No company found. Please register first.';
  header('Location: /06_company/06_10_register_company.php');
  exit;
}
$company_id = (int)$company['id'];

// Cartella certificati (Marco deve crearla: dir.15 — il codice non crea cartelle)
$certs_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/certs/';

$ISO = [
  'cert_iso9001'  => 'ISO 9001',
  'cert_iso14001' => 'ISO 14001',
  'cert_iso45001' => 'ISO 45001',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  // Valori testuali
  $associazioni = trim($_POST['associazioni'] ?? '');
  $referenze    = trim($_POST['referenze'] ?? '');
  $area_servita = trim($_POST['area_servita'] ?? '');

  // Certificati: mantieni esistenti se non ne arriva uno nuovo
  $cert_vals = [];
  $just_uploaded = [];
  foreach ($ISO as $col => $label) {
    $cert_vals[$col] = (string)($company[$col] ?? '');
    if (isset($_FILES[$col]) && $_FILES[$col]['error'] !== UPLOAD_ERR_NO_FILE) {
      $res = UploadSecurity::storeDocument($_FILES[$col], $certs_dir);
      if (!$res['ok']) {
        $_SESSION['error_message'] = $label . ': ' . $res['error'];
        header('Location: /06_company/06_15_authority.php');
        exit;
      }
      $just_uploaded[$col] = $res['stored_name'];
      $cert_vals[$col] = $res['stored_name'];
    }
  }

  try {
    $st = $pdo->prepare(
      "UPDATE `06_company` SET
        `cert_iso9001` = :c9, `cert_iso14001` = :c14, `cert_iso45001` = :c45,
        `associazioni` = :assoc, `referenze` = :ref, `area_servita` = :area
       WHERE `id` = :id"
    );
    $st->execute([
      ':c9'    => $cert_vals['cert_iso9001']  !== '' ? $cert_vals['cert_iso9001']  : null,
      ':c14'   => $cert_vals['cert_iso14001'] !== '' ? $cert_vals['cert_iso14001'] : null,
      ':c45'   => $cert_vals['cert_iso45001'] !== '' ? $cert_vals['cert_iso45001'] : null,
      ':assoc' => $associazioni !== '' ? $associazioni : null,
      ':ref'   => $referenze    !== '' ? $referenze    : null,
      ':area'  => $area_servita !== '' ? $area_servita : null,
      ':id'    => $company_id,
    ]);

    // Rimuovi i vecchi file certificato sostituiti (housekeeping, dir.15: unlink dei propri file consentito)
    foreach ($just_uploaded as $col => $newname) {
      $old = (string)($company[$col] ?? '');
      if ($old !== '' && $old !== $newname) {
        $f = $certs_dir . $old;
        if (is_file($f)) { @unlink($f); }
      }
    }

    $_SESSION['success_message'] = 'Credentials updated.';
  } catch (Throwable $e) {
    $_SESSION['error_message'] = 'Could not save credentials.';
  }
  header('Location: /06_company/06_02_view_company.php?id=' . $company_id);
  exit;
}

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$certs_url  = $asset_base . '/upload_image/06_company/certs/';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Company Credentials</title>
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
    <div id="page_title">Company Credentials</div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p></div>
    <?php endif; ?>

    <div class="post_box">
    <div id="contact_form">
    <form method="post" action="06_15_authority.php" enctype="multipart/form-data">
      <?php echo csrf_generate(); ?>

      <p><strong>Certifications</strong> (PDF/JPG/PNG/WEBP, max 15&nbsp;MB each):</p>
      <?php foreach ($ISO as $col => $label): $cur = trim((string)($company[$col] ?? '')); ?>
      <div class="float_l">
        <label for="<?php echo $col; ?>"><?php echo $label; ?>:</label>
        <input type="file" name="<?php echo $col; ?>" id="<?php echo $col; ?>" />
        <?php if ($cur !== ''): ?>
          &nbsp;<a href="<?php echo htmlspecialchars($certs_url . rawurlencode($cur)); ?>" target="_blank" rel="noopener">current file</a>
        <?php endif; ?>
      </div>
      <div class="cleaner h10"></div>
      <?php endforeach; ?>

      <div class="cleaner h10"></div>
      <div class="float_l">
        <label for="associazioni">Memberships / Associations:</label>
        <input type="text" name="associazioni" id="associazioni" maxlength="500" value="<?php echo htmlspecialchars((string)($company['associazioni'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="area_servita">Area served:</label>
        <input type="text" name="area_servita" id="area_servita" maxlength="100" value="<?php echo htmlspecialchars((string)($company['area_servita'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <label for="referenze">References / Clients:</label>
        <textarea name="referenze" id="referenze" rows="4" cols="40"><?php echo htmlspecialchars((string)($company['referenze'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="cleaner h20"></div>

      <button type="submit" class="more">Save credentials</button>
      <a href="06_02_view_company.php?id=<?php echo $company_id; ?>" class="more float_l">Cancel</a>
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
