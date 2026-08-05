<?php
// 07_rent/07_10_rent_post.php -- Pubblica un annuncio di NOLEGGIO (solo veicoli speciali).
// Basato sugli annunci free (02_free_ads): stesse variabili di inserimento.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$id_user = require_user_logged_in();
$special = VehicleTaxonomy::typesForCategory('special', $pdo);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Publish a rental listing</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Publish a rental listing</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Publish a rental listing</h2>
      <p>List a special vehicle you offer <strong>for rent</strong>. Users requesting that type will reach you by e-mail (free / premium / gold).</p>
      <?php if (!empty($_SESSION['error_message'])): ?><ul><li><strong><?php echo $e($_SESSION['error_message']); unset($_SESSION['error_message']); ?></strong></li></ul><?php endif; ?>
      <form method="post" action="07_11_rent_save.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
        <p><label>Title:<br><input type="text" name="title" size="50" maxlength="200" required /></label></p>
        <p><label>Subtitle (optional):<br><input type="text" name="subtitle" size="50" maxlength="200" /></label></p>
        <p><label>Kind:<br><select name="item_kind"><option value="vehicle">Vehicle</option><option value="shelter_container">Shelter / container</option></select></label></p>
        <p><label>Special vehicle type:<br><select name="vehicle_type" required><option value="">-- choose --</option>
          <?php foreach ($special as $t): ?><option value="<?php echo $e($t['slug']); ?>"><?php echo $e($t['name']); ?></option><?php endforeach; ?>
        </select></label></p>
        <p><label>Daily rate &euro; (per day):<br><input type="text" name="list_price" size="12" value="0" /></label></p>
        <p><label>Condition:<br><select name="conditions"><option>As good as new</option><option>New</option><option>Used</option><option>Poor</option><option>Project</option></select></label></p>
        <p><label>Contact phone (optional):<br><input type="text" name="phone" size="24" maxlength="30" /></label></p>
        <p><label>Description:<br><textarea name="description" rows="6" cols="60" required></textarea></label></p>
        <p><label>Photo (optional):<br><input type="file" name="rent_image" accept="image/jpeg,image/png,image/gif" /></label></p>
        <div class="post_meta"><button type="submit" class="more float_r">Publish listing</button></div>
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
