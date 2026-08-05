<?php
// 05_wanted/wanted_list.php — Elenco pubblico delle richieste "Wanted" attive.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/wanted_ads.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';

$id_user = require_user_logged_in();
if (!PlanPolicy::canWanted(UserTier::getTier($pdo, $id_user))) {
    $_SESSION['error_message'] = 'Access to Wanted Requests is a Premium and Gold feature. Upgrade your plan to unlock buyer leads.';
    header('Location: ../01_login/request_premium.php');
    exit;
}

$wanted = new WantedAds($pdo);
$macros = $pdo->query('SELECT slug, name FROM `product_macros` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
$macro_name = [];
foreach ($macros as $m) { $macro_name[$m['slug']] = $m['name']; }
$fmacro = (string)($_GET['macro'] ?? '');
if ($fmacro !== '' && !isset($macro_name[$fmacro])) { $fmacro = ''; }
$list = $wanted->listActive($fmacro !== '' ? $fmacro : null, null, 200);
$logged = current_user_id() !== null;
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Wanted requests</title>

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Wanted requests</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Buyers looking for vehicles</h2>
      <p>These are open "wanted" requests from buyers. If you can supply one, open it and respond.
         <?php if ($logged): ?><a class="more float_r" href="wanted_post.php">Post a wanted request</a><div class="cleaner"></div><?php endif; ?>
      </p>
      <p>
        <a href="wanted_list.php"<?php echo $fmacro === '' ? ' class="more"' : ''; ?>>All</a>
		  </br>
        <?php foreach ($macros as $m): ?>
	  
          | <a href="wanted_list.php?macro=<?php echo $e($m['slug']); ?>"<?php echo $fmacro === $m['slug'] ? ' class="more"' : ''; ?>><?php echo $e($m['name']); ?></a>
        <?php endforeach; ?>
      </p>
      <?php if ($list): ?>
        <?php foreach ($list as $row): ?>
          <div class="post_box">
            <h3><a href="wanted_view.php?id=<?php echo (int)$row['id']; ?>"><?php echo $e($row['title']); ?></a></h3>
            <p class="post_meta">
              Category: <strong><?php echo $e($macro_name[$row['macro']] ?? $row['macro']); ?></strong>
              <?php if (!empty($row['vehicle_type'])): ?> &middot; Type: <?php echo $e($row['vehicle_type']); ?><?php endif; ?>
              <?php if (!empty($row['budget'])): ?> &middot; Budget: &euro;<?php echo $e(number_format((float)$row['budget'], 0, '.', ',')); ?><?php endif; ?>
              <?php if (!empty($row['country_code'])): ?> &middot; <?php echo $e($row['country_code']); ?><?php endif; ?>
            </p>
            <p><?php echo $e(mb_substr((string)$row['description'], 0, 180)); ?><?php echo mb_strlen((string)$row['description']) > 180 ? '&hellip;' : ''; ?></p>
            <a class="more float_r" href="wanted_view.php?id=<?php echo (int)$row['id']; ?>">View &amp; respond</a>
            <div class="cleaner"></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p><em>No open requests<?php echo $fmacro !== '' ? ' in this category' : ''; ?> right now.</em></p>
      <?php endif; ?>
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