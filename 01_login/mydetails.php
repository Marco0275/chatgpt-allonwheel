<?php
session_start();
if (isset($_SESSION['session_id'])) {
  $session_username = htmlspecialchars($_SESSION['session_username']);
  $session_email = htmlspecialchars($_SESSION['session_email']);
  $session_id = htmlspecialchars($_SESSION['session_id']);
	$session_id_user = $_SESSION['session_id_user'];
	$session_phone = htmlspecialchars($_SESSION['session_phone']);
} else {
  header('Location: /01_login/login.php');
  exit();
}

// DB + CSRF (database.php carica anche bootstrap/BASE_URL e crea $pdo)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';

// Immagine del profilo corrente (per anteprima e per il cambio)
$current_profile_image = '';
try {
  $stmt = $pdo->prepare('SELECT profile_image FROM users WHERE id_user = :id LIMIT 1');
  $stmt->execute([':id' => (int)$session_id_user]);
  $current_profile_image = (string)($stmt->fetchColumn() ?: '');
} catch (PDOException $e) {
  $current_profile_image = '';
}
$profile_preview = $current_profile_image !== ''
  ? '/upload_image/profile/original/' . rawurlencode($current_profile_image)
  : '../images/no_image.jpg';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - My details</title>
<meta name="keywords" content="All on Wheel - My details" />
<meta name="description" content="All on Wheel - My details" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" /><link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
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
    <div id="page_title">My Details</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <p>Add your ad and remember to fill in all fields; the more information you provide, the higher the chance of finding interested buyers.</p>
    <p><strong>(*) Please note:</strong> these details will be shown in your post!</p>
    <div id="contact_form">
			<?php if (!empty($_SESSION['modify_message'])): ?>
  <p class="aow-ok-text"><strong><?= htmlspecialchars($_SESSION['modify_message']) ?></strong></p>
  <?php unset($_SESSION['modify_message']); ?>
<?php endif; ?>
    <form method="post" action="modify_user_details.php" id="submit_advertising" enctype="multipart/form-data">
      <?php echo csrf_generate(); ?>
      <table width="100%" border="0">
      <tbody>
      <tr>
        <td width="20%" rowspan="7"><img src="<?php echo htmlspecialchars($profile_preview, ENT_QUOTES, 'UTF-8'); ?>" width="200" height="150" alt="" loading="lazy" decoding="async" /></td>
        <td width="23%"><strong>Username:</strong></td>
        <td width="57%"><span class="float_l">
        <input type="text" id="Author_display" value="<?php echo $session_username; ?>" readonly="readonly" />
        </span></td>
      </tr>
      <tr>
        <td><strong>E-mail:</strong></td>
        <td><span class="float_l">
        <input type="email" id="email_display" value="<?php echo $session_email; ?>" readonly="readonly" />
        </span></td>
      </tr>
      <tr>
        <td><strong>Phone:</strong></td>
        <td align="right"><span class="float_l">
        <input name="phone" type="text" required="required" id="phone" value="<?= $session_phone; ?>" />
        </span>(with international code)</td>
      </tr>
      <tr>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td colspan="2"><p>&nbsp;</p>
          <p>You can update phone number (shown in your posts) and profile image.</p></td>
      </tr>
      <tr>
        <td colspan="2"><br />Change profile image (optional, JPG/PNG/GIF)<br />
        <br /><input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" /></td>
      </tr>
      </tbody>
      </table>
      <p><br />
      </p>
      <div class="cleaner h20"></div>
		<table width="100%" border="0">
  <tbody>
    <tr>
      <td></td> <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td><input type="submit" class="submit_btn float_r" name="submit" id="submit" value="Modify" /></td>
    </tr>
  </tbody>
</table>
    </form>
		
    </div>
  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <div>
    <?php include('../footer.php'); ?>
  </div>
</div>
</body>
</html>
