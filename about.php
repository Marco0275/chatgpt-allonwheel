<?php if (!function_exists('t')) require_once __DIR__ . '/config/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php te('about.title','About us'); ?></title>
<meta name="keywords" content="All on Wheel - About us" />
<meta name="description" content="All on Wheel is the B2B marketplace for the motorsport paddock and special vehicles, connecting buyers with verified bodybuilders, sellers and rental operators across Europe." />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />

<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php $seo_canonical = 'about.php'; include __DIR__ . '/includes/seo_head.php'; ?>
</head>
<body>
<div id="templatemo_wrapper">
<div id="templatemo_header">
 <?php include ('header.php'); ?>
</div> 
<div id="content_top">
<div id="page_title"><?php te('about.title','About us'); ?></div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
	<h2><?php te('about.h_who','Who we are'); ?></h2>
<p><?php te('about.who1',''); ?></p>
<p><?php te('about.who2',''); ?></p>
<p>&nbsp;</p>
<h2><?php te('about.h_paddock','Built around the paddock'); ?></h2>
<p><?php te('about.paddock1',''); ?></p>
<p>&nbsp;</p>
<h2><?php te('about.h_b2b','Beyond the paddock'); ?></h2>
<p><?php te('about.b2b1',''); ?></p>
<p>&nbsp;</p>
<h2><?php te('about.h_platform','Marketplace and directory'); ?></h2>
<p><?php te('about.platform1',''); ?></p>
<p>&nbsp;</p>
<h2><?php te('about.h_why','Why All on Wheel'); ?></h2>
<ul class="templatemo_list">
 <li><?php te('about.why_li1',''); ?></li>
 <li><?php te('about.why_li2',''); ?></li>
 <li><?php te('about.why_li3',''); ?></li>
</ul>
<div class="cleaner"></div>
<blockquote>
 <p><?php te('about.cta',''); ?></p>
</blockquote>
		<div class="post_meta">
			<a href="index.php" class="more float_r"><?php te('about.back','Back'); ?></a>
	  </div>
 </div><!-- end templatemo_content -->

<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>
  <?php include __DIR__ . '/footer.php'; ?>

</div>
</body>
</html>