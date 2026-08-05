<?php
if (session_status() == PHP_SESSION_NONE) {
 session_start();
}

require_once __DIR__ . '/../config/database.php';

// Recupera id_ads da GET, POST o SESSION
$id_ads = null;
if (isset($_GET['id_ads'])) {
 $id_ads = (int)$_GET['id_ads'];
} elseif (isset($_POST['id_ads'])) {
 $id_ads = (int)$_POST['id_ads'];
} elseif (isset($_SESSION['id_ads'])) {
 $id_ads = (int)$_SESSION['id_ads'];
} else {
 header('Location: 02_view_ads.php'); exit;
}

// Query per ottenere i dati dell'annuncio
try {
 $query = "SELECT * FROM `02_free_ads` WHERE id_ads = :id_ads";
 $stmt = $pdo->prepare($query);
 $stmt->bindParam(':id_ads', $id_ads, PDO::PARAM_INT);
 $stmt->execute();
 $row_richiama_ads = $stmt->fetch(PDO::FETCH_ASSOC);

 if (!$row_richiama_ads) {
  header('Location: 02_view_ads.php'); exit;
 }
} catch (PDOException $e) {
 error_log('[Allonwheel] DB error: ' . $e->getMessage());
 header('Location: 02_view_ads.php'); exit;
}

function displayStatusIcon($value) {
 return $value > 0 ? '<img src="/images/OK.gif" width="15" height="17" alt="Ok" loading="lazy" decoding="async" />' : '<img src="/images/KO.gif" width="15" height="17" alt="Ko" loading="lazy" decoding="async" />';
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Preview post</title>
<meta name="keywords" content="All on Wheel - Preview post" />
<meta name="description" content="All on Wheel - Preview post" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link rel="icon" href="../images/favicon.ico" />

<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
 <div id="templatemo_header">
 <?php include ('../header.php'); ?>
 </div>
 <div id="content_top">
 <div id="page_title">Preview of your ad.</div>
 <div id="search_box">
  <form action="<?php echo $base_url; ?>browse.php" method="get">
 <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
 <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
  </form>
 </div>
 <div class="cleaner"></div>
 </div>
 <div id="main"></div><div id="templatemo_content">
	 <h1><?php $_SESSION['session_title'] = $row_richiama_ads['title']; echo htmlspecialchars($row_richiama_ads['title']); ?></h1>
 <h6><em><?php echo htmlspecialchars($row_richiama_ads['subtitle']); ?></em></h6>
 <div id="contact_form">
  <div class="gallery_box">
	<ul class="gallery">
	<li>
	  <a class="pirobox" href="/upload_image/02_free_ads/original/<?= htmlspecialchars($row_richiama_ads['image_original']) ?>" 
		 title="<?= htmlspecialchars($row_richiama_ads['title']) ?>">
		<img src="/upload_image/02_free_ads/thumbnail/<?= htmlspecialchars($row_richiama_ads['image_thumbnail']) ?>" 
			 alt="<?= htmlspecialchars($row_richiama_ads['title']) ?>" width="220" height="150" border="0" loading="lazy" decoding="async" />
	  </a>
	</li>
	</ul>
  </div>
 </div>
 <!-- FIX: rimossi <td></td> fuori dal contesto tabella -->
 <div class="cleaner h10"></div>
  <div class="float_l">
  Author: <strong><?php echo htmlspecialchars($row_richiama_ads['author']); ?></strong>
  </div>
  <div class="cleaner h10"></div>
  <div class="float_l">
  Title: <strong><?php echo htmlspecialchars($row_richiama_ads['title']); ?></strong>
  </div>
  <div class="cleaner h10"></div>
  <div class="float_l">
  List price: <?php echo htmlspecialchars($row_richiama_ads['list_price']); ?>
  </div>
  <div class="cleaner h20"></div>
  <p>
  <label for="description"><strong>Description:</strong></label>
  </p>
  <p><?php echo nl2br(htmlspecialchars($row_richiama_ads['description'])); ?></p>
  <div class="cleaner h20"></div>
	 
<div><strong style="text-align: right">Modify:</strong>
   <a class="more float_r" href="02_modify_insert_ad.php?id_ads=<?php echo $id_ads; ?>">Advertising</a>
 </div>
	     <div class="cleaner h20"></div>
	<div class="cleaner h20"></div>
	 <div class="cleaner h20"></div>
<div><strong style="text-align: right">Preview:</strong>
    <div class="cleaner h20"></div>
	<div class="cleaner h20"></div>
	<a class="more float_r" href="02_view_ads.php">Confirm</a></div>
 </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
 <div class="cleaner"></div>
 <div>
 <?php include ('../footer.php'); ?>
 </div>
</body>
</html>
