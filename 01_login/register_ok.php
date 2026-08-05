<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Registration successful</title>
<meta name="keywords" content="All on Wheel - Registration successful" />
<meta name="description" content="All on Wheel - Registration successful" />
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
<div id="page_title">Registration successful</div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div> 
<div id="main"></div><div id="templatemo_content">
 <table width="100%" border="0" align="center">
<tr>
 <td width="138"><img src="../images/my_profile/profile_ok.jpg" alt="" width="220" height="150" loading="lazy" decoding="async" /></td>
 <td width="266"><p>Registration successful! We've sent you a confirmation email. Please check your inbox.</p>
 <p>&nbsp;</p>
 <a href="../01_login/newlogin.php" class="more float_r">Login</a></td>
</tr>
 </table>
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div> <!-- end of sidebar -->
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>