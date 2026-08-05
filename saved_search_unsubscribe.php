<?php
// M4: disiscrizione one-click dagli alert (token dall'email). Nessun login richiesto.
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
if (!function_exists('t')) require_once __DIR__ . '/config/i18n.php';

$token = preg_match('/^[a-f0-9]{32}$/', $_GET['token'] ?? '') ? $_GET['token'] : '';
$done = false;
if ($token !== '') {
    try {
        $st = $pdo->prepare('UPDATE saved_searches SET active = 0 WHERE token = :t');
        $st->execute([':t' => $token]);
        $done = $st->rowCount() >= 0; // idempotente: anche gia' disattivata = ok
    } catch (Throwable $e) { $done = false; }
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Email alerts</title>
<meta name="robots" content="noindex, nofollow" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>
  <div id="content_top">
    <div id="page_title"><?php te('ss.unsub_title', 'Email alerts'); ?></div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <?php if ($done && $token !== ''): ?>
      <h2><?php te('ss.unsub_ok', 'You have been unsubscribed'); ?></h2>
      <p><?php te('ss.unsub_ok_p', 'This saved search will no longer send you email alerts. You can create a new one anytime from the marketplace.'); ?></p>
      <?php else: ?>
      <h2><?php te('ss.unsub_ko', 'Link not valid'); ?></h2>
      <p><?php te('ss.unsub_ko_p', 'This unsubscribe link is not valid or has expired.'); ?></p>
      <?php endif; ?>
      <p><a class="more" href="browse.php"><?php te('ss.unsub_back', 'Back to the marketplace'); ?></a></p>
    </div>
  </div>
  <div class="cleaner"></div>
  <?php include 'footer.php'; ?>
</div>
</body>
</html>
