<?php if (!function_exists('t')) require_once __DIR__ . '/config/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php te('cond.title','Conditions and rules'); ?></title>
<meta name="keywords" content="All on Wheel - Conditions and rules" />
<meta name="description" content="Conditions and rules for the All on Wheel marketplace: how listings, quotation requests, supplier accounts and premium ads work for buyers and sellers." />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<?php if (defined('BASE_URL')) echo '<link rel="canonical" href="' . htmlspecialchars(rtrim(BASE_URL, '/') . '/Conditions.php', ENT_QUOTES) . '" />'; ?>
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
 <?php include ('header.php'); ?>
</div> 
<div id="content_top">
<div id="page_title"><?php te('cond.title','Conditions and rules'); ?></div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
 <div>
<div>
 <h3> <?php te('cond.h_rules','General rules for this site'); ?></h3>
</div>
<div>
 <div>
<div>
 <ul class="templatemo_list">
<li class="templatemo_list"><?php echo t('cond.rule1',''); ?></li>
<li class="templatemo_list"><?php echo t('cond.rule2',''); ?></li>
<li class="templatemo_list"><?php echo t('cond.rule3',''); ?></li>
<li class="templatemo_list"><?php echo t('cond.rule4',''); ?></li>
<li class="templatemo_list"><?php echo t('cond.rule5',''); ?></li>
<li class="templatemo_list"><?php echo t('cond.rule6',''); ?></li>
 </ul>
 <p class="cond-legal-links"><?php te('cond.legal_intro','See also our legal information:'); ?> <a href="legal.php"><?php te('cond.link_legal','Legal &amp; seller information'); ?></a> &middot; <a href="privacy.php"><?php te('cond.link_privacy','Privacy policy'); ?></a> &middot; <a href="cookie-policy.php"><?php te('cond.link_cookie','Cookie policy'); ?></a>.</p>
 <p>&nbsp;</p>
 <p>&nbsp;</p>
 <div class="post_meta"><a href="index.php" class="more float_r"><?php te('cond.back','Back'); ?></span></a></div>
 <p>&nbsp;</p>
</div>
 </div>
</div>
 </div>
</div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>