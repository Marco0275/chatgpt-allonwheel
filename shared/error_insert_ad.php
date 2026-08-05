<?php
// ============================================================
// shared/error_insert_ad.php — Pagina errore inserimento annuncio
//
// USO da thin-wrapper:
// $module = [
//   'page_title'  => 'Free ad — error',
//   'retry_url' => '02_insert_ad.php',
//   'list_url'  => '02_view_ads.php',
// ];
// require __DIR__ . '/../shared/error_insert_ad.php';
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/session_helper.php';

$page_title = $module['page_title'] ?? 'Error';
$retry_url  = $module['retry_url']  ?? '#';
$list_url = $module['list_url'] ?? '#';

$error_message = $_SESSION['error_message']
  ?? 'There was a problem saving your ad or you\'ve reached your post limit. Please check your details and try again.';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($page_title); ?></title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
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
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title"><?php echo htmlspecialchars($page_title); ?></div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <div class="post_box">
    <h2>Something went wrong</h2>
    <p class="error-msg"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="post_meta">
      <a href="<?php echo htmlspecialchars($retry_url); ?>" class="more">Try again</a>
      &nbsp;
      <a href="<?php echo htmlspecialchars($list_url); ?>" class="more">Back</a>
      &nbsp;
      <a href="../01_login/my_posts.php" class="more">My posts</a>
      <div class="cleaner"></div>
    </div>
    </div>

  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
