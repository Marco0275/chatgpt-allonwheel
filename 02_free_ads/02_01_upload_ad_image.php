<?php
// ============================================================
// 02_free_ads/02_01_upload_ad_image.php
// Upload dell'immagine principale dell'annuncio free.
//

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: 02_error_insert_ad.php');
  exit;
}

// CSRF persistente: il token resta valido per l'intero wizard
csrf_verify_persistent();

// [GUARD wizard unificato] free -> 02_free_ads (3 foto) / premium -> 03_ads (20 foto).
// Definizione idempotente e a prova di merge: se una var manca o e' vuota, viene ricalcolata.
if (!isset($aow_lt) || ($aow_lt !== 'free' && $aow_lt !== 'prem')) {
    $aow_lt = ((($_POST['lt'] ?? $_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
}
$_SESSION['aow_listing'] = $aow_lt;
$aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '02_free_ads';
$aow_max = ($aow_lt === 'prem') ? 20 : 3;

// Recupera id_ads dalla sessione (impostato in 02_01_upload_advertising.php)
$id_ads = isset($_SESSION['id_ads']) ? (int)$_SESSION['id_ads'] : 0;
// Wizard unificato (rev. 7 lug): free -> 02_free_ads, premium -> 03_ads
$aow_lt  = ((($_POST['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
if ($id_ads <= 0) {
  $_SESSION['error_message'] = 'Session expired. Please start over.';
  header('Location: ' . BASE_URL . '/02_free_ads/02_insert_ad.php');
  exit;
}

// OWNERSHIP CHECK: l'annuncio deve appartenere all'utente loggato
$stmt = $pdo->prepare(
  'SELECT id_ads, image_original, image_thumbnail
   FROM `' . $aow_tbl . '`
  WHERE id_ads = :id_ads AND id_user = :id_user
  LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// Verifica che ci sia un file
if (empty($_FILES['ad_image']) || $_FILES['ad_image']['error'] === UPLOAD_ERR_NO_FILE) {
  $_SESSION['error_message'] = 'Please select an image to upload.';
  header('Location: 02_insert_ad_image.php');
  exit;
}

// Upload via helper sicuro
$result = UploadHelper::handleImageUpload($_FILES['ad_image'], [
  'target_dir_original'  => '/upload_image/' . $aow_tbl . '/original/',
  'target_dir_thumbnail' => '/upload_image/' . $aow_tbl . '/thumbnail/',
  'thumb_width'    => 220,
  'thumb_height'   => 150,
  'thumb_crop'     => false,
  'max_size_bytes'   => 5 * 1024 * 1024, // 5 MB
  'filename_prefix'  => 'ad_' . $id_ads,
]);

if (!$result['ok']) {
  $_SESSION['error_message'] = $result['error'];
  header('Location: 02_insert_ad_image.php');
  exit;
}

// Cleanup vecchi file (se l'utente sta sostituendo l'immagine)
$upload_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/' . $aow_tbl . '/';
foreach (['original', 'thumbnail'] as $sub) {
  $old = $ad['image_' . ($sub === 'original' ? 'original' : 'thumbnail')];
  if ($old && $old !== 'no_image.jpg' && $old !== $result['filename']) {
    $candidate = realpath($upload_root . $sub . '/' . basename($old));
    $base  = realpath($upload_root . $sub);
    if ($candidate && $base && strpos($candidate, $base . DIRECTORY_SEPARATOR) === 0) {
    @unlink($candidate);
    }
  }
}

// Update DB
try {
  $pdo->prepare(
    'UPDATE `' . $aow_tbl . '`
    SET image_original = :orig, image_thumbnail = :thumb
    WHERE id_ads = :id_ads AND id_user = :id_user'
  )->execute([
    ':orig'  => $result['filename'],
    ':thumb' => $result['filename'],
    ':id_ads'  => $id_ads,
    ':id_user' => $id_user,
  ]);
} catch (PDOException $e) {
  error_log('[Allonwheel] Update image free ad error: ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error while saving image.';
  header('Location: 02_insert_ad_image.php');
  exit;
}

header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
exit;
