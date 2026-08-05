<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Already registered</title>
<meta name="keywords" content="All on Wheel Ltd - Already registered" />
<meta name="description" content="All on Wheel Ltd - Already registered" />
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
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div> 
<div id="content_top">
<div id="page_title">Login</div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div> 
<div id="main"></div><div id="templatemo_content">
<h2>You're already registered</h2>
		<p>Please, fill with your e-mail and password to login.</p>
		<div id="contact_form">
		<?php require_once __DIR__ . '/../config/csrf.php'; ?>
		<form method="post" action="login.php">
		<?php echo csrf_generate(); ?>
<div class="float_l">
			<input type="text" id="email" placeholder="email" name="email">
			</div>
			<p>&nbsp;</p>
			<div class="float_l">
			<input type="password" id="password" placeholder="Password" name="password">
			</div>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<p><em>All fields required.</em></p>
			<div class="cleaner h20"></div>
		<input type="submit" class="submit_btn float_r" name="login" id="submit" value="Login" />
		</form>
		</div>
<div class="cleaner"></div>
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
</div> <!-- end of sidebar -->
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>