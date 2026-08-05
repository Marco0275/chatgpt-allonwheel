<?php
// ============================================================
// 03_ads/03_02_delete_image_gallery.php
// Elimina una singola immagine dalla 03_ads_gallery di un annuncio
// (tabella: gallery, modulo: 03_ads).
//
// Parametri POST attesi:
// image_id    — id_images del record in gallery
// image_original  — nome file originale
// image_thumbnail — nome file thumbnail
//
// Sicurezza:
// - Solo POST
// - Solo utenti autenticati
// - Verifica che l'annuncio appartenga all'utente loggato
// - CSRF token obbligatorio
// - Protezione path traversal su eliminazione file
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// BASE_URL definito in config/bootstrap.php

// Protezione: solo utenti autenticati
if (!isset($_SESSION['session_id'], $_SESSION['session_id_user']) && !isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/01_login/newlogin.php');
  exit;
}

// Accetta solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
// FIX 22 lug 2026: la galleria del wizard emette il token PERSISTENTE
// (csrf_generate_persistent), mentre qui si verificava con la variante
// ONE-SHOT: due chiavi di sessione diverse (csrf_token vs
// csrf_persistent_token) -> il confronto falliva SEMPRE e cancellare una
// foto dava "Request not allowed". Gli altri handler della galleria
// (02_01_upload_gallery, 02_01_modify_upload_gallery) usano gia' la
// variante persistente: questo era l'unico fuori riga.
// Persistente e' anche l'unica scelta corretta qui: nella pagina ci sono
// PIU' form (uno per foto + upload) e il token one-shot, consumato dal
// primo submit, invaliderebbe tutti gli altri.
csrf_verify_persistent();

$id_user = (int) ($_SESSION['session_id_user'] ?? $_SESSION['user_id'] ?? 0);
// Wizard unificato: la stessa pagina serve free (03_ads) e premium (03_ads)
$aow_lt  = ((($_POST['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
$aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '03_ads';
$image_id  = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
$img_orig  = basename(trim($_POST['image_original']  ?? ''));
$img_thumb = basename(trim($_POST['image_thumbnail'] ?? ''));

if ($image_id <= 0) {
  $_SESSION['error_message'] = 'Invalid image ID.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// Recupera l'immagine e verifica che l'annuncio appartenga all'utente
$stmt = $pdo->prepare(
  'SELECT g.id_images, g.image_original, g.image_thumbnail, g.id_ads
   FROM `' . $aow_tbl . '_gallery` g
   JOIN `' . $aow_tbl . '` a ON a.id_ads = g.id_ads
   WHERE  g.id_images = :image_id
   AND  a.id_user = :id_user
   LIMIT  1'
);
$stmt->execute([':image_id' => $image_id, ':id_user' => $id_user]);
$img = $stmt->fetch();

if (!$img) {
  $_SESSION['error_message'] = 'Image not found or permission denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

$id_ads = (int) $img['id_ads'];

// [GUARD wizard unificato] free -> 03_ads (3 foto) / premium -> 03_ads (20 foto).
// Definizione idempotente e a prova di merge: se una var manca o e' vuota, viene ricalcolata.
if (!isset($aow_lt) || ($aow_lt !== 'free' && $aow_lt !== 'prem')) {
    $aow_lt = ((($_POST['lt'] ?? $_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
}
$_SESSION['aow_listing'] = $aow_lt;
$aow_max = ($aow_lt === 'prem') ? 20 : 3;

// Elimina il record dal database
try {
  $pdo->prepare('DELETE FROM `' . $aow_tbl . '_gallery` WHERE id_images = :image_id')
    ->execute([':image_id' => $image_id]);
} catch (PDOException $e) {
  error_log('[Allonwheel] delete gallery image error (' . $aow_tbl . '): ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error while deleting image.';
  header('Location: ' . BASE_URL . '/03_ads/03_insert_ad_gallery.php?id_ads=' . $id_ads . ($aow_lt === 'prem' ? '&lt=prem' : ''));
  exit;
}

// Elimina i file fisici dal filesystem
$uploadBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/' . $aow_tbl . '/';

$deleteFile = static function (string $dir, string $filename): void {
  $filename = basename($filename);
  if ($filename === '' || $filename === 'no_image.jpg') {
    return;
  }
  $fullPath = realpath($dir . $filename);
  $basePath = realpath($dir);
  if ($fullPath === false || $basePath === false) {
    return;
  }
  if (strpos($fullPath, $basePath . DIRECTORY_SEPARATOR) !== 0) {
    error_log('[Allonwheel] deleteFile: path traversal blocked: ' . $filename);
    return;
  }
  if (is_file($fullPath)) {
    unlink($fullPath);
  }
};

$deleteFile($uploadBase . 'original/',  $img['image_original']);
$deleteFile($uploadBase . 'thumbnail/', $img['image_thumbnail']);

$_SESSION['success_message'] = 'Image deleted successfully.';
// Ritorno alla pagina di provenienza: modifica (ret=modify) o wizard di inserimento.
$aow_ret = ($_POST['ret'] ?? '') === 'modify';
if ($aow_ret) {
  $aow_back = ($aow_lt === 'prem')
    ? '/03_ads/03_insert_ad_gallery.php?id_ads=' . $id_ads
    : '/03_ads/03_insert_ad_gallery.php?id_ads=' . $id_ads;
} else {
  $aow_back = '/03_ads/03_insert_ad_gallery.php?id_ads=' . $id_ads . ($aow_lt === 'prem' ? '&lt=prem' : '');
}
header('Location: ' . BASE_URL . $aow_back);
exit;
?>
