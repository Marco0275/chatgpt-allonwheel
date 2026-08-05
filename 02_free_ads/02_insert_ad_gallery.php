<?php
// ============================================================
// 02_free_ads/02_insert_ad_gallery.php
//
// FIX preview gallery dopo upload:
//  - Path immagini ROOT-RELATIVE (/upload_image/...) senza BASE_URL.
//  Il prefisso BASE_URL='https://www.allonwheel.com' rompeva la
//  visualizzazione su qualsiasi dominio diverso da production.
//  - Cache-buster ?v=id_images sui src delle thumb (forza il browser a
//  rifetchare dopo l'upload, evita il caso "vedo la pagina cached").
//  - Aggiunto debug: se la query gallery torna 0 righe lo dice esplicitamente
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

$id_ads = $_SESSION['id_ads'] ?? $_GET['id_ads'] ?? null;
if (!$id_ads) {
  header('Location: 02_error_insert_ad.php');
  exit;
}
$id_ads = (int)$id_ads;
$_SESSION['id_ads'] = $id_ads;

// [GUARD wizard unificato] free -> 02_free_ads (3 foto) / premium -> 03_ads (20 foto).
// Definizione idempotente e a prova di merge: se una var manca o e' vuota, viene ricalcolata.
if (!isset($aow_lt) || ($aow_lt !== 'free' && $aow_lt !== 'prem')) {
    $aow_lt = ((($_POST['lt'] ?? $_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
}
$_SESSION['aow_listing'] = $aow_lt;
$aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '02_free_ads';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';
$aow_ptier = UserTier::getTier($pdo, $id_user);
$aow_plim  = PlanPolicy::photoLimit($aow_ptier);      // 0=Free, 10=Premium, -1=Gold/Admin
$aow_max   = ($aow_plim < 0) ? 9999 : (int)$aow_plim; // -1 => illimitato

// Ownership check
$stmt = $pdo->prepare(
  'SELECT id_ads FROM `' . $aow_tbl . '` WHERE id_ads = :id_ads AND id_user = :id_user LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
if (!$stmt->fetch()) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// Recupera immagini gallery
$stmt_img = $pdo->prepare(
  'SELECT * FROM `' . $aow_tbl . '_gallery` WHERE id_ads = :id_ads ORDER BY id_images DESC'
);
$stmt_img->execute([':id_ads' => $id_ads]);
$immagini = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);

csrf_generate_persistent();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Add a gallery to your ad</title>
<meta name="robots" content="noindex, nofollow" />
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
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
<div id="templatemo_header"><?php include __DIR__ . '/../header.php'; ?></div>
<div id="content_top">
  <div id="page_title">Upload gallery</div>
  <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
    <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
    <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
  </div>
  <div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">

  <?php if ($success): ?><p class="done"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
  <?php if ($error): ?><p class="error-msg"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <p><strong>Step 4 of <?php echo $aow_lt === 'prem' ? 6 : 4; ?> &middot; Photo gallery</strong> &mdash; <em><?php echo $aow_max > 0 ? 'Up to ' . (int)$aow_max . ' photos.' : 'Photo gallery is available on Premium and Gold plans.'; ?> Optional: you can add photos later from <a href="../01_login/my_posts.php">My posts</a>.</em></p>
  <p><strong>By adding a gallery, you'll have more chances to be contacted by customers.</strong></p>
  <p>Below you'll find all the images you've uploaded; you can delete any you don't want or continue uploading.</p>

  <div id="contact_form">
    <form action="02_01_upload_gallery.php" method="POST" enctype="multipart/form-data">
      <?php if ($aow_lt === 'prem'): ?><input type="hidden" name="lt" value="prem" /><?php endif; ?>
    <?php echo csrf_generate_persistent(); ?>
    <input type="hidden" name="id_ads" value="<?php echo (int)$id_ads; ?>">
    <p><input type="file" name="image" accept="image/*" required></p>
		          <button type="submit" name="submit" id="submit" value="Upload image" class="more float_l">Upload image</button></label>
    <div class="cleaner"></div>
    </form>
  </div>
<div class="cleaner h20"></div>
  <h3>Gallery preview</h3>

  <div class="gallery">
    <?php if (count($immagini) > 0): ?>
    <?php foreach ($immagini as $img):
      $thumb = trim((string)($img['image_thumbnail'] ?? ''));
      $orig  = trim((string)($img['image_original']  ?? ''));
      $cb  = (int)($img['id_images'] ?? 0); // cache-buster
      // Path ROOT-RELATIVE: funziona su qualsiasi dominio
      $thumb_url = '/upload_image/' . $aow_tbl . '/thumbnail/' . rawurlencode($thumb) . '?v=' . $cb;
      $orig_url  = '/upload_image/' . $aow_tbl . '/original/'  . rawurlencode($orig)  . '?v=' . $cb;
    ?>
    <div class="image-box thumb_wrap">
      <ul class="gallery">
        <li>
        <a class="pirobox" href="<?php echo htmlspecialchars($orig_url); ?>" title="Image #<?php echo $cb; ?>">
          <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="Image"
           width="220" height="150" border="0" loading="lazy" decoding="async" />
        </a>
        </li>
      </ul>
      <form action="02_02_delete_image_gallery.php" method="POST" >
        <?php if ($aow_lt === 'prem'): ?><input type="hidden" name="lt" value="prem" /><?php endif; ?>
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="image_id"    value="<?php echo (int)$img['id_images']; ?>">
        <input type="hidden" name="image_original"  value="<?php echo htmlspecialchars($img['image_original']); ?>">
        <input type="hidden" name="image_thumbnail" value="<?php echo htmlspecialchars($img['image_thumbnail']); ?>">
        <div class="cleaner h10"></div>
        <small><?php echo htmlspecialchars($img['image_thumbnail']); ?></small>
        <button type="submit" class="btn_del">🗑️ Delete</button>
        <div class="cleaner h20"></div>
      </form>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="cleaner h10"></div>
    <p><em>No images uploaded for this ad yet. Upload your first image using the form above.</em></p>
    <?php endif; ?>

<div class="cleaner h20"></div>
	  </div>
	
	<?php if ($aow_lt === 'prem'): ?>
<a class="more float_r" href="../03_ads/03_insert_tech_details.php">Continue to technical details</a>
<?php else: ?>
<a class="more float_r" href="02_preview_ad.php?id_ads=<?php echo (int)$id_ads; ?>">Continue</a>
<?php endif; ?>
</div>
<div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

<div class="cleaner"></div>

<?php include __DIR__ . '/../footer.php'; ?>

</div> <!-- chiusura #templatemo_wrapper -->

</body>
</html>