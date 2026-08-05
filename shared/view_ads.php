<?php
// ============================================================
// shared/view_ads.php — Lista annunci unificata per 02_free_ads e 03_ads
//
// REV PHASE 5b:
//  - URL immagini ora costruiti con BASE_URL (assoluto). Risolve il bug
//  "le immagini non caricano" che si presentava se il sito veniva
//  servito da una sotto-cartella o se i path root-relative erano
//  soggetti a riscrittura mod_rewrite.
//  - Rimossi tutti gli inline-script: caricamento delegato a site_init.js.
//  Risolve "piroBox non funziona" — l'invocazione $().piroBox(...)
//  dipendeva da timing di $(document).ready che era inaffidabile in
//  pagine con molti DOM nodes.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

if (!isset($module) || !is_array($module)) {
  http_response_code(500);
  error_log('[Allonwheel] shared/view_ads.php chiamato senza $module');
  exit('Internal configuration error.');
}

$ALLOWED_TABLES = ['02_free_ads', '03_ads'];
if (!isset($module['table']) || !in_array($module['table'], $ALLOWED_TABLES, true)) {
  http_response_code(500);
  exit('Internal configuration error.');
}

$table   = $module['table'];
$upload_path = $module['upload_path'] ?? ('/upload_image/' . $table . '/');
$detail_url  = $module['detail_url']  ?? '#';
$page_title  = $module['page_title']  ?? 'Listings';

// PATCH: BASE_URL prefisso per asset statici
$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Ricerca testuale opzionale (dir.1.4): title / description LIKE
$q = trim($_GET['q'] ?? '');
if ($q === 'Search') { $q = ''; }   // valore placeholder del campo
$where  = '';
$params = [];
if ($q !== '') {
  $where  = ' WHERE title LIKE ? OR description LIKE ?';
  $like   = '%' . $q . '%';
  $params = [$like, $like];
}

$sql = sprintf(
  'SELECT x.*, u.user_tier AS owner_tier FROM (
   SELECT id_ads, title, subtitle, list_price, type, conditions,
    image_original, image_thumbnail, description,
    author, created_at, id_user
   FROM `%s`%s
   ) x
   LEFT JOIN `users` u ON u.id_user = x.id_user
  ORDER BY CASE u.user_tier WHEN \'gold\' THEN 0 WHEN \'premium\' THEN 1 ELSE 2 END, x.created_at DESC, x.id_ads DESC',
  $table,
  $where
);

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log('[Allonwheel] shared/view_ads query error: ' . $e->getMessage());
  $ads = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords" content="<?php echo htmlspecialchars($page_title); ?>" />
<meta name="description" content="<?php echo htmlspecialchars($page_title); ?>" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
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
    <div id="page_title"><?php echo htmlspecialchars($page_title); ?></div>
    <div id="search_box">
    <form action="" method="get">
      <input type="text" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <?php if ($q !== ''): ?>
    <p><em>Search results for: <strong><?php echo htmlspecialchars($q); ?></strong>
    &mdash; <a href="">Show all</a></em></p>
    <?php endif; ?>

    <?php if (empty($ads)): ?>
    <div class="post_box">
      <h2>No listings found</h2>
      <p>There are no ads to display<?php echo $q !== '' ? ' matching your search' : ' in this category'; ?>.</p>
    </div>
    <?php else: ?>
    <?php foreach ($ads as $ad):
      $thumb = trim((string)($ad['image_thumbnail'] ?? ''));
      $orig  = trim((string)($ad['image_original']  ?? ''));

      // PATCH: percorso assoluto via BASE_URL (no_image fallback locale)
      if ($thumb !== '' && $thumb !== 'no_image.jpg') {
        $thumb_url = $asset_base . $upload_path . 'thumbnail/' . $thumb;
      } else {
        $thumb_url = '../images/no_image.jpg';
      }
      if ($orig !== '' && $orig !== 'no_image.jpg') {
        $orig_url = $asset_base . $upload_path . 'original/' . $orig;
      } else {
        $orig_url = $thumb_url;
      }
    ?>
      <div class="post_box">
        <h2><?php echo htmlspecialchars($ad['title']); ?></h2>
        <?php require_once __DIR__ . '/../libs/plan_policy.class.php'; $vb = PlanPolicy::badge((string)($ad['owner_tier'] ?? '')); ?>
        <?php if ($vb === 'Featured'): ?><span class="badge badge_featured">Featured</span><?php elseif ($vb === 'Premium'): ?><span class="badge badge_premium">Premium</span><?php endif; ?>
        <ul class="gallery">
        <li>
          <a class="pirobox"
           href="<?php echo htmlspecialchars($orig_url); ?>"
           title="<?php echo htmlspecialchars($ad['title']); ?>">
            <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($thumb_url); ?>"
             alt="<?php echo htmlspecialchars($ad['title']); ?>"
             width="220" height="150" border="0" />
          </a>
        </li>
        </ul>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($ad['type']); ?></p>
        <p><strong>Price:</strong> <?php echo number_format((float)$ad['list_price'], 2); ?> €</p>
        <p><strong>Condition:</strong> <?php echo htmlspecialchars($ad['conditions']); ?></p>
        <p align="justify">
        <?php
        $desc = (string)($ad['description'] ?? '');
        $short = mb_strlen($desc) > 200 ? mb_substr($desc, 0, 200) . '…' : $desc;
        echo nl2br(htmlspecialchars($short));
        ?>
        </p>
        <div class="post_meta">
        <span class="cat">Posted by: <strong><?php echo htmlspecialchars($ad['author']); ?></strong></span>
        |
        <span class="cat">Published: <strong><?php echo htmlspecialchars($ad['created_at']); ?></strong></span>
        <a href="<?php echo htmlspecialchars($detail_url); ?>?id_ads=<?php echo (int)$ad['id_ads']; ?>"
         class="more float_r">Read more</a>
        <div class="cleaner"></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include __DIR__ . '/../footer.php'; ?>
</div>
</body>
</html>
