<?php
// ============================================================
// 02_free_ads/02_delete_ad.php
// Elimina un annuncio gratuito dell'utente autenticato.
//
// Modifiche rispetto alla versione precedente:
//  - Usa session_helper centralizzato (require_user_logged_in).
//  - Rimosso doppio fallback session_id_user/user_id (compreso nell'helper).
//  - Messaggio di successo in inglese (coerente col resto del sito).
//  - Mantenuto: CSRF, ownership check nella WHERE, transazione,
//  cancellazione file con realpath/path-traversal protection.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

// Forza login (redirect automatico se non loggato)
$id_user = require_user_logged_in();

// Solo POST consentito
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /01_login/my_posts.php');
  exit;
}

// --- Validazione token CSRF ---
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
  $_SESSION['error_message'] = 'Invalid security token. Please try again.';
  header('Location: /01_login/my_posts.php');
  exit;
}
// Consuma il token
unset($_SESSION['csrf_token']);

$id_ads = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : 0;
if ($id_ads <= 0) {
  $_SESSION['error_message'] = 'Invalid ad ID.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// --- Recupera il record verificando ownership PRIMA di toccare i file ---
$stmt = $pdo->prepare(
  'SELECT image_original, image_thumbnail
   FROM `02_free_ads`
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

// --- Recupera i file della gallery PRIMA di eliminarli dal DB ---
$stmtGal = $pdo->prepare(
  'SELECT image_original, image_thumbnail
   FROM `02_free_ads_gallery`
  WHERE id_ads = :id_ads'
);
$stmtGal->execute([':id_ads' => $id_ads]);
$gallery_images = $stmtGal->fetchAll(PDO::FETCH_ASSOC);

// --- Elimina dal database (in transazione) ---
try {
  $pdo->beginTransaction();

  // Gallery prima (anche se la FK è ON DELETE CASCADE, lo facciamo
  // esplicitamente per essere sicuri di avere $gallery_images consistenti)
  $pdo->prepare('DELETE FROM `02_free_ads_gallery` WHERE id_ads = :id_ads')
    ->execute([':id_ads' => $id_ads]);

  // Annuncio (con ownership check anche qui per sicurezza)
  $pdo->prepare('DELETE FROM `02_free_ads` WHERE id_ads = :id_ads AND id_user = :id_user')
    ->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);

  $pdo->commit();
} catch (PDOException $e) {
  $pdo->rollBack();
  error_log('[Allonwheel] Delete free_ad error (id_ads=' . $id_ads . '): ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error while deleting the ad. Please try again.';
  header('Location: /01_login/my_posts.php');
  exit;
}

// --- Elimina i file fisici DOPO il commit del DB ---
$uploadBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/02_free_ads/';

$deleteFile = static function (string $dir, string $filename): void {
  $filename = basename($filename); // strip path components
  if ($filename === '' || $filename === 'no_image.jpg') {
    return;
  }
  $fullPath = realpath($dir . $filename);
  $basePath = realpath($dir);
  if ($fullPath === false || $basePath === false) {
    return;
  }
  // Path traversal protection: il file risolto deve stare DENTRO la cartella base
  if (strpos($fullPath, $basePath . DIRECTORY_SEPARATOR) !== 0) {
    error_log('[Allonwheel] deleteFile: path traversal blocked: ' . $filename);
    return;
  }
  if (is_file($fullPath)) {
    unlink($fullPath);
  }
};

$deleteFile($uploadBase . 'original/',  $ad['image_original']);
$deleteFile($uploadBase . 'thumbnail/', $ad['image_thumbnail']);

foreach ($gallery_images as $gal) {
  $deleteFile($uploadBase . 'original/',  $gal['image_original']);
  $deleteFile($uploadBase . 'thumbnail/', $gal['image_thumbnail']);
}

$_SESSION['success_message'] = 'Free ad deleted successfully.';
header('Location: /01_login/my_posts.php');
exit;
?>
