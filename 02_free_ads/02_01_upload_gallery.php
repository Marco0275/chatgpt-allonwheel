<?php
// ============================================================
// 02_free_ads/02_01_upload_gallery.php
// Upload immagine gallery per annuncio free.
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

csrf_verify_persistent();

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

$id_ads = isset($_SESSION['id_ads']) ? (int)$_SESSION['id_ads'] : 0;
if ($id_ads <= 0) {
  header('Location: ' . BASE_URL . '/02_free_ads/02_insert_ad.php');
  exit;
}
// Wizard unificato: free -> 02_free_ads (3 foto), premium -> 03_ads (20 foto).
// Va calcolato PRIMA di ogni uso (ownership check + limite + path).

// Ownership check
$stmt = $pdo->prepare(
  'SELECT id_ads FROM `' . $aow_tbl . '`
  WHERE id_ads = :id_ads AND id_user = :id_user
  LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
if (!$stmt->fetch()) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// Field name 'image' (allineato al form 02_insert_ad_gallery.php)
if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
  $_SESSION['error_message'] = 'Please select an image.';
  header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
  exit;
}

// Limite max immagini gallery (anti-abuse)
$count = (int)$pdo->query(
  'SELECT COUNT(*) FROM `' . $aow_tbl . '_gallery` WHERE id_ads = ' . (int)$id_ads
)->fetchColumn();
if ($count >= $aow_max) {
  $_SESSION['error_message'] = 'Gallery limit reached (' . (int)$aow_max . ' images max).';
  header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
  exit;
}

$result = UploadHelper::handleImageUpload($_FILES['image'], [
  'target_dir_original'  => '/upload_image/' . $aow_tbl . '/original/',
  'target_dir_thumbnail' => '/upload_image/' . $aow_tbl . '/thumbnail/',
  'thumb_width'    => 220,
  'thumb_height'   => 150,
  'thumb_crop'     => false,
  'max_size_bytes'   => 5 * 1024 * 1024,
  'filename_prefix'  => 'gal_' . $id_ads,
]);

if (!$result['ok']) {
  $_SESSION['error_message'] = $result['error'];
  header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
  exit;
}

try {
  $pdo->prepare(
    'INSERT INTO `' . $aow_tbl . '_gallery` (id_ads, image_original, image_thumbnail)
   VALUES (:id_ads, :orig, :thumb)'
  )->execute([
    ':id_ads' => $id_ads,
    ':orig' => $result['filename'],
    ':thumb'  => $result['filename'],
  ]);
} catch (PDOException $e) {
  error_log('[Allonwheel] Insert gallery image error: ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error.';
  header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
  exit;
}

$_SESSION['success_message'] = 'Image added to gallery.';
header('Location: 02_insert_ad_gallery.php' . ($aow_lt === 'prem' ? '?lt=prem' : '') . '');
exit;
