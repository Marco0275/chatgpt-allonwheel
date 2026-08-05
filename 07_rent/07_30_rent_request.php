<?php
// 07_rent/07_30_rent_request.php -- Form richiesta NOLEGGIO (base: wanted_post.php).
// L'utente indica con CHECKBOX i tipi di veicolo speciale che vuole noleggiare.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/antispam.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$id_user = require_user_logged_in();
$special = VehicleTaxonomy::typesForCategory('special', $pdo);
$preset = trim($_GET['vt'] ?? '');
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Request a rental</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Request a rental</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Request a special vehicle for rent</h2>
      <p>Select the special vehicle types you want to rent. Companies with matching rental listings will be notified and can respond (free / premium / gold).</p>
      <?php if (!empty($_SESSION['error_message'])): ?><ul><li><strong><?php echo $e($_SESSION['error_message']); unset($_SESSION['error_message']); ?></strong></li></ul><?php endif; ?>
      <form method="post" action="07_31_rent_request_save.php">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
        <?php echo aow_spam_fields(); ?>
        <p><strong>Vehicle types:</strong></p>
        <div class="rent_types">
          <?php foreach ($special as $t): ?>
          <label style="display:block"><input type="checkbox" name="vt[]" value="<?php echo $e($t['slug']); ?>" <?php echo ($preset === $t['slug']) ? 'checked' : ''; ?> /> <?php echo $e($t['name']); ?></label>
          <?php endforeach; ?>
        </div>
        <p><label>Budget &euro; per day (optional):<br><input type="text" name="budget" size="12" /></label></p>
        <p><label>Country (2-letter, optional):<br><input type="text" name="country_code" size="4" maxlength="2" /></label></p>
        <p><label>From (optional):<br><input type="date" name="rent_from" /></label> &nbsp; <label>To (optional):<br><input type="date" name="rent_to" /></label></p>
        <p><label>Description:<br><textarea name="description" rows="6" cols="60" required></textarea></label></p>
        <?php require_once __DIR__ . '/../includes/form_consent.php'; echo aow_privacy_consent_field(); ?>
        <div class="post_meta"><button type="submit" class="more float_r">Send rental request</button></div>
        <div class="cleaner"></div>
      </form>
    </div>
  </div><!-- content -->
  <div id="templatemo_sidebar"><?php include __DIR__ . '/../include_sidebar.php'; ?></div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
