<?php require_once __DIR__ . '/config/csrf.php'; require_once __DIR__ . '/libs/antispam.php'; if (!function_exists('t')) require_once __DIR__ . '/config/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - <?php te('contact.title','Contact us'); ?></title>
<meta name="keywords" content="All on Wheel - Contact us" />
<meta name="description" content="Get in touch with All on Wheel about listings, supplier accounts, quotation requests or partnerships for motorsport paddock and special vehicles." />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<?php if (defined('BASE_URL')) echo '<link rel="canonical" href="' . htmlspecialchars(rtrim(BASE_URL, '/') . '/contact.php', ENT_QUOTES) . '" />'; ?>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />

<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php $page_has_own_h1 = true; include ('header.php'); ?>
</div> 

<div id="content_top">
<div id="page_title">Contact Us</div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div> 
<div id="main"></div><div id="templatemo_content">
<p><?php te('contact.intro1','Do you have other questions?'); ?></p>
<p><?php te('contact.intro2',''); ?></p>
<p>&nbsp;</p>
 		<h1><?php te('contact.form_title','Send us a message'); ?></h1>
 <div id="contact_form">
<form name="formmail" method="post" action="contact_submit.php">
 <?php echo csrf_generate(); ?>
 <input type="hidden" name="success" value="../contact-success.php">
 <input type="hidden" name="retry" value="../contact-retry.php">
 <?php echo aow_spam_fields(); ?>
 <div class="float_l">
<label for="author"><?php te('contact.name','Name'); ?>:</label>
<input type="text" id="author" name="author" class="required input_field" />
 </div>
 <div class="float_r">
<label for="email"><?php te('contact.email','Email'); ?>:</label>
<input type="email" id="email" name="email" class="validate-email required input_field" />
 </div>
	 <div>
		<div class="cleaner h10"></div>
<label for="object"><?php te('contact.object','Object'); ?>:</label>
<input type="text" id="object" name="object" class="required input_field" />
 </div>
 <div class="cleaner h20"></div>
 <label for="msg"><?php te('contact.message','Message'); ?>:</label>
 <textarea id="msg" name="msg" rows="0" cols="0" class="required"></textarea>
 <div class="cleaner h20">
<div class="cleaner h10"></div>
	 <table width="100%" border="0">
  <tbody>
    <tr><td colspan="3"><?php require_once __DIR__ . '/includes/form_consent.php'; echo aow_privacy_consent_field(); ?></td></tr>
    <tr>
      <td></td> <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td><button type="submit" name="submit" id="submit" value="<?php te('contact.send','Send'); ?>" class="more float_r"><?php te('contact.send','Send'); ?></button></td>
    </tr>
  </tbody>
</table>

 </div>
</form>
	 <div class="cleaner h20"></div>
</div> 
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>