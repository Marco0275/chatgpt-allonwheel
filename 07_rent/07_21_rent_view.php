<?php
// 07_rent/07_21_rent_view.php -- Dettaglio annuncio di noleggio.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/rent.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$id = (int)($_GET['id'] ?? 0);
$rent = new RentAds($pdo);
$ad = $rent->getListing($id);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$ok = $ad && $ad['status'] === 'approved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Rental listing</title>
<meta name="robots" content="index, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Rental listing</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <?php if (!$ok): ?>
        <h2>Listing not found</h2><p><a class="more" href="07_20_rent_list.php">Back to rentals</a></p>
      <?php else: ?>
        <h2><?php echo $e($ad['title']); ?></h2>
        <?php if (!empty($ad['subtitle'])): ?><p><em><?php echo $e($ad['subtitle']); ?></em></p><?php endif; ?>
        <img src="../upload_image/07_rent/original/<?php echo $e($ad['image_original']); ?>" alt="<?php echo $e($ad['title']); ?>" style="max-width:100%;height:auto" loading="lazy" decoding="async" />
        <p><strong>Type:</strong> <?php echo $e(VehicleTaxonomy::label((string)$ad['vehicle_type'], $pdo)); ?></p>
        <p><strong>Daily rate:</strong> <?php echo number_format((float)$ad['list_price'], 2); ?> &euro;/day</p>
        <p><strong>Condition:</strong> <?php echo $e($ad['conditions']); ?></p>
        <p><?php echo nl2br($e($ad['description'])); ?></p>
        <?php if (!empty($ad['phone'])): ?><p><strong>Phone:</strong> <?php echo $e($ad['phone']); ?></p><?php endif; ?>
        <p><a class="more btn_accent" href="07_30_rent_request.php?vt=<?php echo $e($ad['vehicle_type']); ?>">Request this type for rent</a></p>
      <?php endif; ?>
    </div>
  </div><!-- content -->
  <div id="templatemo_sidebar"><?php include __DIR__ . '/../include_sidebar.php'; ?></div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
