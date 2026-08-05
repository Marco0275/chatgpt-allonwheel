<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Login to your account</title>
<meta name="keywords" content="All on Wheel Ltd - Login to your account" />
<meta name="description" content="All on Wheel Ltd - Login to your account" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
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
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div>
<div id="content_top">
<div id="page_title">Login to your account</div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
<h2>Fill with your details</h2>
		<p>Complete the following details to login to your account; then publish news, photos and much more...</p>
		<div id="contact_form">
		<?php
    // FIX: carica CSRF helper e genera token
    require_once __DIR__ . '/../config/csrf.php';
    ?>
		<form method="post" action="login.php">
    <?php echo csrf_generate(); ?>
<div class="float_l"></div>
<div class="float_l"></div>
			<table width="50%" border="0">
			  <tbody>
			  <tr>
			  <td width="50%" rowspan="3" valign="top"><img src="../images/my_profile/profile.jpg" width="220" height="150" alt="" loading="lazy" decoding="async" /></td>
			  <td width="25%" align="right" valign="middle">Username:&nbsp;</td>
			  <td width="25%" valign="middle"><span class="float_l">
			    <input type="text" id="email" placeholder="email" name="email" size="30" />
			  </span></td>
		    </tr>
			  <tr>
			  <td align="right" valign="middle">Password:&nbsp;</td>
			  <td valign="middle"><span class="float_l">
			    <input type="password" id="password" placeholder="Password" name="password" size="30" />
			  </span></td>
		    </tr>
			  <tr>
			  <td colspan="2" align="right" valign="top"><p><em>All fields required.</em></p>
			    <p>&nbsp;</p>
			    <p><a href="newregister.php" class="more">New account</a></p></td>
		    </tr>
		  </tbody>
		  </table>
			<div class="cleaner h20"></div>
			<table width="100%" border="0">
  <tbody>
    <tr>
      <td width="20%"></td>
<td width="60%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td width="20%"><input type="submit" class="submit_btn float_r" name="login" id="submit" value="Login" /></td>
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