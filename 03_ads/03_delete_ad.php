<?php
// ============================================================
// 03_ads/03_delete_ad.php
// Elimina un annuncio premium dell'utente autenticato.
// (Stessa struttura di 03_delete_ad.php — qui in più: 03_ads_tech_details)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /01_login/my_posts.php');
  exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
  $_SESSION['error_message'] = 'Invalid security token. Please try again.';
  header('Location: /01_login/my_posts.php');
  exit;
}
unset($_SESSION['csrf_token']);

$id_ads = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : 0;
if ($id_ads <= 0) {
  $_SESSION['error_message'] = 'Invalid ad ID.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// Verifica ownership e recupera nomi file
$stmt = $pdo->prepare(
  'SELECT image_original, image_thumbnail
   FROM `03_ads`
  WHERE id_ads = :id_ads AND id_user = :id_user
  LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
$ad = $stmt->fetch();

if (!$ad) {
  $_SESSION['error_message'] = 'Ad not found or you do not have permission to delete it.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// Gallery files
$stmtGal = $pdo->prepare(
  'SELECT image_original, image_thumbnail
   FROM `03_ads_gallery`
  WHERE id_ads = :id_ads'
);
$stmtGal->execute([':id_ads' => $id_ads]);
$gallery_images = $stmtGal->fetchAll(PDO::FETCH_ASSOC);

// Cancellazione in transazione
try {
  $pdo->beginTransaction();

  $pdo->prepare('DELETE FROM `03_ads_gallery` WHERE id_ads = :id_ads')
    ->execute([':id_ads' => $id_ads]);

  // 03_ads_tech_details ha FK CASCADE, ma lo facciamo esplicito
  $pdo->prepare('DELETE FROM `03_ads_tech_details` WHERE id_ads = :id_ads')
    ->execute([':id_ads' => $id_ads]);

  $pdo->prepare('DELETE FROM `03_ads` WHERE id_ads = :id_ads AND id_user = :id_user')
    ->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);

  $pdo->commit();
} catch (PDOException $e) {
  $pdo->rollBack();
  error_log('[Allonwheel] Delete premium_ad error (id_ads=' . $id_ads . '): ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error while deleting the ad. Please try again.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// File fisici
$uploadBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/03_ads/';

$deleteFile = static function (string $dir, string $filename): void {
  $filename = basename($filename);
  if ($filename === '' || $filename === 'no_image.jpg') return;
  $fullPath = realpath($dir . $filename);
  $basePath = realpath($dir);
  if ($fullPath === false || $basePath === false) return;
  if (strpos($fullPath, $basePath . DIRECTORY_SEPARATOR) !== 0) {
    error_log('[Allonwheel] deleteFile: path traversal blocked: ' . $filename);
    return;
  }
  if (is_file($fullPath)) unlink($fullPath);
};

$deleteFile($uploadBase . 'original/',  $ad['image_original']);
$deleteFile($uploadBase . 'thumbnail/', $ad['image_thumbnail']);

foreach ($gallery_images as $gal) {
  $deleteFile($uploadBase . 'original/',  $gal['image_original']);
  $deleteFile($uploadBase . 'thumbnail/', $gal['image_thumbnail']);
}

$_SESSION['success_message'] = 'Premium ad deleted successfully.';
header('Location: /01_login/my_posts.php');
exit;
?>
