<?php
// 05_wanted/wanted_manage.php — Gestione delle proprie richieste "Wanted".
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/wanted_ads.class.php';

$id_user = require_user_logged_in();
$wanted  = new WantedAds($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $wid = (int)($_POST['wid'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'close')  { $wanted->setStatus($wid, $id_user, 'closed'); }
    if ($action === 'reopen') { $wanted->setStatus($wid, $id_user, 'active'); }
    if ($action === 'delete') { $wanted->deleteOwned($wid, $id_user); }
    header('Location: wanted_manage.php'); exit;
}

$rows = $wanted->listByUser($id_user);
$macros = $pdo->query('SELECT slug, name FROM `product_macros`')->fetchAll(PDO::FETCH_KEY_PAIR);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - My wanted requests</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">My wanted requests</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>My wanted requests</h2>
      <p><a class="more float_r" href="wanted_post.php">Post a new request</a></p>
      <div class="cleaner"></div>
      <?php if ($rows): ?>
        <?php foreach ($rows as $r): ?>
          <div class="post_box">
            <h3><a href="wanted_view.php?id=<?php echo (int)$r['id']; ?>"><?php echo $e($r['title']); ?></a>
                &mdash; <em><?php echo $e($r['status']); ?></em></h3>
            <p class="post_meta">Category: <?php echo $e($macros[$r['macro']] ?? $r['macro']); ?>
               &middot; <?php echo $e($r['created_at']); ?></p>
            <form method="post" action="wanted_manage.php" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
              <input type="hidden" name="wid" value="<?php echo (int)$r['id']; ?>" />
              <?php if ($r['status'] === 'active'): ?>
                <input type="hidden" name="action" value="close" /><button type="submit" value="Close" class="more">Close</button>
              <?php else: ?>
                <input type="hidden" name="action" value="reopen" /><button type="submit" value="Reopen" class="more">Reopen</button>
              <?php endif; ?>
            </form>
            <form method="post" action="wanted_manage.php" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
              <input type="hidden" name="wid" value="<?php echo (int)$r['id']; ?>" />
              <input type="hidden" name="action" value="delete" />
              <button type="submit" value="Delete" class="more">Delete</button>
            </form>
            <div class="cleaner"></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p><em>You have no wanted requests yet.</em></p>
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