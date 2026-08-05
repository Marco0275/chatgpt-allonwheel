<?php
// 07_rent/07_20_rent_list.php -- Vetrina annunci di noleggio (veicoli speciali).
// dir. 21: NESSUN filtro in pagina (come browse.php). Il filtro per tipo vive
// nella sidebar 'Special vehicles', dirottata qui via $aow_special_search_action.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/rent.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$special       = VehicleTaxonomy::typesForCategory('special', $pdo);
$special_slugs = array_column($special, 'slug');
$active_vtype  = trim($_GET['vtype'] ?? '');
if (!in_array($active_vtype, $special_slugs, true)) { $active_vtype = ''; }

$rent = new RentAds($pdo);
$ads  = $rent->listActive($active_vtype !== '' ? $active_vtype : null);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// La sidebar 'Special vehicles' filtra QUESTA pagina (niente form in-page).
$aow_special_search_action = '07_rent/07_20_rent_list.php';
$active_label = $active_vtype !== '' ? VehicleTaxonomy::label($active_vtype, $pdo) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Vehicle rental</title>
<meta name="robots" content="index, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Vehicle rental</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Special vehicles for rent<?php if ($active_label !== ''): ?> &mdash; <?php echo $e($active_label); ?><?php endif; ?></h2>
      <?php if (empty($ads)): ?>
        <p>No rental listings yet.
			<?php if ($is_logged_in): ?>
            <a href="07_10_rent_post.php">Post the first one!</a>
          <?php else: ?>
            <a href="../01_login/newregister.php">Register</a> to post the first listing or request.
          <?php endif; ?>
        </p>

      <?php else: ?>
      <div class="listing_grid">
        <?php foreach ($ads as $a): ?>
        <div class="listing_card">
          <a href="07_21_rent_view.php?id=<?php echo (int)$a['id_ads']; ?>">
            <img src="../upload_image/07_rent/thumbnail/<?php echo $e($a['image_thumbnail']); ?>" alt="<?php echo $e($a['title']); ?>" loading="lazy" />
            <h3><?php echo $e($a['title']); ?></h3>
          </a>
          <span class="badges"><span class="badge badge_type">Rentals</span></span>
          <p><?php echo $e(VehicleTaxonomy::label((string)$a['vehicle_type'], $pdo)); ?></p>
          <p><strong><?php echo number_format((float)$a['list_price'], 2); ?> &euro;/day</strong></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div><!-- content -->
  <div id="templatemo_sidebar"><?php include __DIR__ . '/../include_sidebar.php'; ?></div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
