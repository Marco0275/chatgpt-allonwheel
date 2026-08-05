<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Contact</title>
<meta name="keywords" content="All on Wheel - Contact" />
<meta name="description" content="All on Wheel - Contact" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

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
<div id="page_title">Request an offer</div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div> 
<div id="main"></div><div id="templatemo_content">
 <table width="566" border="0" align="center">
<tr>
 <td width="138"><img src="../images/right-email.png" alt="" width="200" height="193" loading="lazy" decoding="async" /></td>
 <td width="266">
   <h2><?php te('rfq.ok_title', 'Request sent!'); ?></h2>
   <p><strong><?php te('rfq.ok_next', 'What happens next'); ?>:</strong></p>
   <ol>
   <li><?php te('rfq.ok_s1', 'Your request has been forwarded to the specialist suppliers of this family.'); ?></li>
   <li><?php te('rfq.ok_s2', 'They will reply directly to your e-mail, typically within a few business days.'); ?></li>
   <li><?php te('rfq.ok_s3', 'Compare the offers and reply to the ones you like - there is no obligation.'); ?></li>
   </ol>
   <p><a class="more" href="<?php echo $base_url; ?>browse.php"><?php te('rfq.ok_browse', 'Keep browsing the marketplace'); ?></a></p>
 </td>
</tr>
<?php if (!empty($_SESSION['rfq_tech'])): ?>
<tr><td colspan="2" align="center"><a class="more" href="04_rfq_pdf.php">Download your technical request (PDF)</a></td></tr>
<?php endif; ?>
 </table>
	</div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>