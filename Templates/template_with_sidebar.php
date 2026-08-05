<?php
/**
 * template_with_sidebar.php -- SCAFFOLD pagina CON sidebar.
 * Copia questo file per creare una nuova pagina con sidebar di sezione.
 * La sidebar e' risolta da include_sidebar.php (dispatcher per-pagina).
 */
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/session_helper.php';
if (!function_exists('t')) { require_once __DIR__ . '/config/i18n.php'; }

$page_title = 'Page title';   // <-- titolo pagina
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel &mdash; <?php echo htmlspecialchars($page_title ?? 'Page', ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="robots" content="index, follow" />
<meta name="copyright" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
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
  <div id="page_title"><?php echo htmlspecialchars($page_title ?? 'Page title', ENT_QUOTES, 'UTF-8'); ?></div>
  <div class="cleaner"></div>
</div>

<div id="main"></div><div id="templatemo_content">

  <div class="post_box">
    <h2>Section title</h2>
    <p>Main content goes here. The card style comes from .post_box.</p>
    <a href="#" class="more">Call to action</a>
  </div>

</div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>

<div class="cleaner"></div>
<?php include 'footer.php'; ?>
</body>
</html>
