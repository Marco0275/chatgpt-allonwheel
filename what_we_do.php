<?php if (!function_exists('t')) require_once __DIR__ . '/config/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php te('wwd.title','What we do'); ?></title>
<meta name="keywords" content="All on Wheel - What we do" />
<meta name="description" content="A marketplace for paddock and special vehicles plus a directory of verified suppliers, tied together by a quotation engine: one request reaches every matching bodybuilder." />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />

<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />

<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />


<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php $seo_canonical = 'what_we_do.php'; include __DIR__ . '/includes/seo_head.php'; ?>
</head>

<body>

<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div> 
<div id="content_top">
<div id="page_title"><?php te('wwd.title','What we do'); ?></div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="main"></div><div id="no_sidebar">
 <h2><?php te('wwd.h_intro','What we do'); ?></h2>
 <p><?php te('wwd.intro1',''); ?></p>
 <p>&nbsp;</p>
 <h2><?php te('wwd.h_buyers','For buyers'); ?></h2>
 <p><?php te('wwd.buyers1',''); ?></p>
 <p>&nbsp;</p>
 <h2><?php te('wwd.h_suppliers','For builders and suppliers'); ?></h2>
 <p><?php te('wwd.suppliers1',''); ?></p>
 <p>&nbsp;</p>
 <h2><?php te('wwd.h_quote','The quotation engine'); ?></h2>
 <p><?php te('wwd.quote1',''); ?></p>
 <p>&nbsp;</p>
 <h2><?php te('wwd.h_cover','What we cover'); ?></h2>
 <ul class="templatemo_list">
 <li><?php te('wwd.cover_li1',''); ?></li>
 <li><?php te('wwd.cover_li2',''); ?></li>
 <li><?php te('wwd.cover_li3',''); ?></li>
 </ul>
 <div class="cleaner h20"></div>
 <blockquote>
 <p><?php te('wwd.cta',''); ?></p>
 </blockquote>
 <div class="post_meta"><a href="index.php" class="more float_r"><?php te('wwd.back','Back'); ?></span></a></div>
</div>
<!-- end of content -->
<!-- full width, no sidebar (dir.17 rev.3) -->
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>