<?php
// ============================================================
// shared/gallery.php — Galleria foto unificata (02 e 03)
// REV PHASE 5b: BASE_URL + site_init.js + onerror fallback
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

if (!isset($module) || !is_array($module)) {
  http_response_code(500);
  exit('Internal configuration error.');
}

$ALLOWED_MAIN = ['02_free_ads', '03_ads'];
$ALLOWED_GAL  = ['02_free_ads_gallery', '03_ads_gallery'];
if (
  !in_array($module['table_main']  ?? '', $ALLOWED_MAIN, true) ||
  !in_array($module['table_gallery'] ?? '', $ALLOWED_GAL,  true)
) {
  http_response_code(500);
  exit('Internal configuration error.');
}

$table_main  = $module['table_main'];
$table_gallery = $module['table_gallery'];
$upload_path = $module['upload_path'] ?? ('/upload_image/' . $table_main . '/');
$detail_url  = $module['detail_url']  ?? '#';
$page_title  = $module['page_title']  ?? 'Gallery';
$asset_base  = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$id_ads = isset($_GET['id_ads']) ? (int)$_GET['id_ads'] : 0;
if ($id_ads <= 0) {
  header('Location: ' . $detail_url);
  exit;
}

$stmt = $pdo->prepare(sprintf('SELECT id_ads, title FROM `%s` WHERE id_ads = :id LIMIT 1', $table_main));
$stmt->execute([':id' => $id_ads]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ad) {
  header('Location: ' . $detail_url);
  exit;
}

$stmt = $pdo->prepare(sprintf(
  'SELECT id_images, image_original, image_thumbnail
   FROM `%s`
  WHERE id_ads = :id
  ORDER BY id_images ASC',
  $table_gallery
));
$stmt->execute([':id' => $id_ads]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($page_title); ?></title>
<meta name="robots" content="index, follow" />
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
    <div id="page_title"> Gallery</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <div class="post_box">
    <h2><?php echo htmlspecialchars($ad['title']); ?></h2>

    <?php if (empty($images)): ?>
      <p><em>No additional images for this ad.</em></p>
    <?php else: ?>
      <ul class="gallery">
        <?php foreach ($images as $img):
        $thumb = trim((string)($img['image_thumbnail'] ?? ''));
        $orig  = trim((string)($img['image_original']  ?? ''));
        if ($thumb === '' || $thumb === 'no_image.jpg') continue;
        $thumb_url = $asset_base . $upload_path . 'thumbnail/' . $thumb;
        $orig_url  = ($orig !== '' && $orig !== 'no_image.jpg')
          ? $asset_base . $upload_path . 'original/' . $orig
          : $thumb_url;
        ?>
        <li>
        <a class="pirobox_gall"
         href="<?php echo htmlspecialchars($orig_url); ?>"
         title="<?php echo htmlspecialchars($ad['title']); ?>">
          <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($thumb_url); ?>"
           alt="<?php echo htmlspecialchars($ad['title']); ?>"
           width="220" height="150" border="0" />
        </a>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="cleaner h20"></div>

    <a class="more float_r" href="<?php echo htmlspecialchars($detail_url); ?>?id_ads=<?php echo (int)$id_ads; ?>">
      Back</a>
    <div class="cleaner"></div>
    </div>

  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
