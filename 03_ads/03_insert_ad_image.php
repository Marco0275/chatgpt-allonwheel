<?php
// ============================================================
// 03_ads/03_insert_ad_image.php
// Form di upload immagine principale (step 2 del wizard insert).
//

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

// Recupero id_ads da sessione o GET
$id_ads = $_SESSION['id_ads'] ?? $_GET['id_ads'] ?? null;
if (!$id_ads) {
  $_SESSION['error_message'] = 'Session expired. Please start over.';
  header('Location: ' . BASE_URL . '/03_ads/03_insert_ad.php');
  exit;
}
$id_ads = (int)$id_ads;
$_SESSION['id_ads'] = $id_ads;

// [GUARD wizard unificato] free -> 03_ads (3 foto) / premium -> 03_ads (20 foto).
// Definizione idempotente e a prova di merge: se una var manca o e' vuota, viene ricalcolata.
if (!isset($aow_lt) || ($aow_lt !== 'free' && $aow_lt !== 'prem')) {
    $aow_lt = ((($_POST['lt'] ?? $_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
}
$_SESSION['aow_listing'] = $aow_lt;
$aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '03_ads';
$aow_max = ($aow_lt === 'prem') ? 20 : 3;
// Wizard unificato: free o premium (tabella/cartelle di conseguenza)
$aow_lt  = ((($_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
$_SESSION['aow_listing'] = $aow_lt;

// Ownership check
$stmt = $pdo->prepare(
  'SELECT image_original, image_thumbnail FROM `' . $aow_tbl . '`
  WHERE id_ads = :id_ads AND id_user = :id_user LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ad) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// Inizializzo variabili (era undefined nel file originale)
$message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$thumb = trim((string)($ad['image_thumbnail'] ?? ''));
if ($thumb !== '' && $thumb !== 'no_image.jpg') {
  $imagePreview = $asset_base . '/upload_image/' . $aow_tbl . '/thumbnail/' . $thumb;
} else {
  $imagePreview = '../images/no_image.jpg';
}
$orig = trim((string)($ad['image_original'] ?? ''));
$imageFull = ($orig !== '' && $orig !== 'no_image.jpg')
  ? $asset_base . '/upload_image/' . $aow_tbl . '/original/' . $orig
  : $imagePreview;

csrf_generate_persistent();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Upload main image of your advertising</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Upload image</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
        <p><strong>Step 3 of <?php echo $aow_lt === 'prem' ? 6 : 4; ?> &middot; Main photo</strong> &mdash; <em>Optional: you can add photos later from <a href="../01_login/my_posts.php">My posts</a>.</em></p>
    <h3>Main image of your advertising</h3>

    <div id="contact_form">

    <?php if ($message): ?>
      <p class="error-msg"><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <?php if ($imagePreview): ?>
      <p>Preview image:</p>
      <div class="gallery_box">
        <ul class="gallery">
        <li>
          <a class="pirobox" href="<?php echo htmlspecialchars($imageFull); ?>" title="Image preview">
            <img src="<?php echo htmlspecialchars($imagePreview); ?>" alt="Image preview"
             width="220" height="150" border="0" loading="lazy" decoding="async" />
          </a>
        </li>
        </ul>
        <div class="cleaner"></div>
      </div>
    <?php endif; ?>

    <form action="03_01_upload_ad_image.php" method="post" enctype="multipart/form-data">
      <?php if ($aow_lt === 'prem'): ?><input type="hidden" name="lt" value="prem" /><?php endif; ?>
      <?php echo csrf_generate_persistent(); ?>
      <p>Select image (jpg, jpeg, png, gif, max 5 MB):</p>
      <p><input type="file" name="ad_image" id="ad_image" accept=".jpg,.jpeg,.png,.gif" required /></p>
      <p>
        <button type="submit" name="submit" id="submit" value="Upload image" class="more float_r">Upload image</button>
      </p>
    </form>
    <a class="more float_r" href="03_insert_ad_gallery.php">Continue</a>
    <div class="cleaner"></div>
    </div>
    <div class="cleaner"></div>
  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>
</div>
</body>
</html>
