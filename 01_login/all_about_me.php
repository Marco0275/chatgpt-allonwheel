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
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - My Profile</title>
<meta name="title"   content="All on Wheel Ltd - My Profile" />
<meta name="description" content="All on Wheel Ltd - My Profile" />
<meta name="keywords"  content="All on Wheel Ltd - My Profile" />
<meta name="robots"  content="index, follow" />
<meta name="language"  content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author"  content="All on Wheel Ltd" />

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />

<!-- CORRETTO: era file:///C|/Users/marco/Desktop/js/piroBox.1_2.js (percorso locale Windows) -->
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
    <div id="page_title">All about me</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>
 <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
    <h2>My details</h2>
    <ul class="gallery m0">
      <li>
        <a class="pirobox" title="My Profile">
			<img src="<?php echo htmlspecialchars($profile_preview, ENT_QUOTES, 'UTF-8'); ?>" width="200" height="150" alt="" loading="lazy" decoding="async" />
        </a>
      </li>
    </ul>
    <p><em><?php echo $session_username; ?></em></p>
    <p>If you're thinking about renting out your vehicle, you can add to our pages to find buyers.
     Your free ad will help you rent out your vehicle while you're not using it. If you want
     to sell it, there is another page which can help with that too. Renting or Selling when
     it's not in use is a great option for making money instead of paying for storage costs.</p>
    <div class="post_meta"><a href="../01_login/mydetails.php" class="more">Continue</a></div>
	</div>
	  <div class="cleaner h20"></div>
    <div class="post_box">
      <h2>Your data &amp; privacy</h2>
    <p>Under the GDPR you can download a copy of your personal data or permanently delete your account.</p>
		<ul class="gallery m0">
        <li>
		</li>
	  </ul>
		<div><a href="../01_login/export_data.php" class="more float_r">My data</a></div>
		<div class="cleaner"></div>
    <div class="post_meta">  
     <div><a href="../01_login/delete_account.php" class="more float_l">Delete</a></div>
      <div class="cleaner"></div>
    </div>
  </div>
 </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <?php include('../footer.php'); ?>

</div><!-- end templatemo_wrapper -->
</body>
</html>
