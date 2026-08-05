<?php
/**
 * 06_14_company_gallery.php — Gestione gallery immagini azienda
 *
 * FIX rispetto alla versione precedente:
 *  - Upload sostituito con UploadHelper (validazione MIME, EXIF strip,
 *    filename randomizzato, whitelist directory).
 *  - Ora salva ENTRAMBI: file originale in /upload_image/06_company/original/
 *    e thumbnail ridimensionata in /upload_image/06_company/thumbnail/.
 *    Prima salvava solo la thumbnail (crop) perdendo l'originale.
 *  - Migrato a session_helper: require_user_logged_in() al posto del
 *    controllo manuale su $_SESSION['user_id'].
 *  - Percorsi immagini nel rendering aggiornati:
 *    link pirobox → original/, <img> thumbnail → thumbnail/.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

// ---- Parametri ----
const GALLERY_THUMB_W   = 220;
const GALLERY_THUMB_H   = 150;
const MAX_UPLOAD_BYTES  = 10 * 1024 * 1024; // 10 MB
const GALLERY_IMG_LIMIT = 30; // max immagini per azienda

// ---- Auth ----
$user_id = require_user_logged_in();

$cm = new CompanyManager($pdo);
$company = $cm->getCompanyByUserId($user_id);
if (!$company) {
    $_SESSION['error_message'] = 'No company found.';
    header('Location: /06_company/06_10_register_company.php');
    exit;
}
$company_id = (int)$company['id'];

// ----------------------------------------------------------------------------
// Handler POST: upload o eliminazione
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // ---- Eliminazione immagine ----
    if (isset($_POST['delete_image_id'])) {
        $img_id = (int)$_POST['delete_image_id'];
        if ($cm->deleteGalleryImage($img_id, $user_id)) {
            $_SESSION['success_message'] = 'Image deleted.';
        } else {
            $_SESSION['error_message'] = 'Error deleting image.';
        }
        header('Location: /06_company/06_14_company_gallery.php');
        exit;
    }

    // ---- Upload nuova immagine ----
    if (isset($_FILES['gallery_image'])) {
        if ($_FILES['gallery_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error_message'] = 'Please select an image to upload.';
            header('Location: /06_company/06_14_company_gallery.php');
            exit;
        }

        // Controllo limite gallery
        $current_count = $cm->countGalleryImages($company_id);
        if ($current_count >= GALLERY_IMG_LIMIT) {
            $_SESSION['error_message'] = 'Gallery limit reached (' . GALLERY_IMG_LIMIT . ' images max).';
            header('Location: /06_company/06_14_company_gallery.php');
            exit;
        }

        // Upload tramite UploadHelper:
        //  - Originale → /upload_image/06_company/original/
        //  - Thumbnail  → /upload_image/06_company/thumbnail/
        $result = UploadHelper::handleImageUpload($_FILES['gallery_image'], [
            'target_dir_original'  => '/upload_image/06_company/original/',
            'target_dir_thumbnail' => '/upload_image/06_company/thumbnail/',
            'thumb_width'          => GALLERY_THUMB_W,
            'thumb_height'         => GALLERY_THUMB_H,
            'thumb_crop'           => true,
            'max_size_bytes'       => MAX_UPLOAD_BYTES,
            'filename_prefix'      => 'company_' . $company_id,
        ]);

        if (!$result['ok']) {
            $_SESSION['error_message'] = $result['error'];
            header('Location: /06_company/06_14_company_gallery.php');
            exit;
        }

        $didascalia = mb_substr(trim($_POST['didascalia'] ?? ''), 0, 255);
        $cm->insertGalleryImage($company_id, $user_id, $result['filename'], $didascalia);
        $_SESSION['success_message'] = 'Image uploaded successfully.';
        header('Location: /06_company/06_14_company_gallery.php');
        exit;
    }
}

// ----------------------------------------------------------------------------
// Rendering pagina
// ----------------------------------------------------------------------------
$images = $cm->getGalleryImages($company_id);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Company Gallery</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.ico" />
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
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top">
    <div id="page_title">Gallery — <?php echo htmlspecialchars($company['ragione_sociale']); ?></div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">

    <?php if (isset($_SESSION['success_message'])): ?>
    <p class="flash flash_ok"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
    <p class="flash flash_err"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <div class="post_box">
    <h3>Upload new image</h3>
    <p class="muted_small">
      Allowed formats: JPG, PNG, GIF. Max <?php echo (int)(MAX_UPLOAD_BYTES / 1024 / 1024); ?>&nbsp;MB.
      Thumbnails are automatically resized to <?php echo GALLERY_THUMB_W; ?>×<?php echo GALLERY_THUMB_H; ?> px;
      originals are kept at full resolution.
    </p>
    <div id="contact_form">
      <form method="post" action="06_14_company_gallery.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <div class="float_l">
        <label for="gallery_image">Image file (JPG, PNG, GIF):</label>
        <input type="file" name="gallery_image" id="gallery_image" accept="image/jpeg,image/png,image/gif" required />
        </div>
        <div class="cleaner h10"></div>
        <div class="float_l">
        <label for="didascalia">Caption:</label>
        <input type="text" name="didascalia" id="didascalia" maxlength="255" />
        </div>
        <div class="cleaner h10"></div>
        <button type="submit" value="Upload" class="more float_r">Upload</button>
        <div class="cleaner"></div>
      </form>
    </div>
    </div>

    <?php if (!empty($images)): ?>
    <h3><?php echo count($images); ?> image(s) in gallery</h3>
    <ul class="gallery">
      <?php foreach ($images as $img): ?>
        <li class="gal_thumb">
        <!-- Link pirobox → immagine originale; <img> → thumbnail -->
        <a class="pirobox_gall" href="/upload_image/06_company/original/<?php echo htmlspecialchars($img['immagine']); ?>" title="<?php echo htmlspecialchars($img['didascalia'] ?? ''); ?>">
          <img src="/upload_image/06_company/thumbnail/<?php echo htmlspecialchars($img['immagine']); ?>" alt="<?php echo htmlspecialchars($img['didascalia'] ?? ''); ?>" width="120" height="90" border="0" loading="lazy" decoding="async" />
        </a>
        <br />
        <form method="post" action="06_14_company_gallery.php" class="inline_form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
          <input type="hidden" name="delete_image_id" value="<?php echo (int)$img['id']; ?>" />
          <button type="submit" class="btn_del">Delete</button>
        </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>No images in gallery yet.</p>
    <?php endif; ?>

    <div class="cleaner h20"></div>
    <a href="06_02_view_company.php?id=<?php echo $company_id; ?>" class="more float_l">Back</a>
    <div class="cleaner"></div>

  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <div><?php include('../footer.php'); ?></div>
</div>
</body>
</html>
