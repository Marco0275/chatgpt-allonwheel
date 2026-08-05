<?php
// ============================================================
// login_error.php — Pagina di errore generica per accesso negato
// Mostrata da dashboard.php quando session_id non è impostato
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Login required</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
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
  <?php include('../header.php'); ?>
</div>

<div id="content_top">
<div id="page_title">Login required</div>
<div class="cleaner"></div>
</div>

<div id="main"></div><div id="templatemo_content">
  <table width="566" border="0" align="center">
    <tr>
    <td width="138">
      <img src="../images/my_profile/profile_ko.jpg" alt="" width="220" height="150" loading="lazy" decoding="async" />
    </td>
    <td width="266">
      <p>You must be logged in to access this page.</p>
      <p>&nbsp;</p>
      <p><a href="../01_login/newlogin.php" class="more float_r">Login</a></p>
      <p><a href="../index.php" class="more float_l">Back </a></p>
    </td>
    </tr>
  </table>
</div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<?php include('../footer.php'); ?>
</div>
</body>
</html>
