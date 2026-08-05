<?php
// download_doc.php — Gateway proxy per i documenti tecnici.
// - IDOR-safe: serve solo se l'annuncio e' pubblico (approved) o il richiedente
//   e' il proprietario.
// - Tracciato: log download (chi/quando, IP hash GDPR) + contatore statistiche.
// - I file fisici sono protetti da .htaccess (accesso diretto negato): questo
//   proxy e' l'UNICA via di download, quindi il tracciamento e' garantito.
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/ads_documents.class.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

$docs = new AdsDocuments($pdo);
$doc  = $docs->get($id);
if (!$doc) { http_response_code(404); exit('Not found'); }

$idAds = (int)$doc['id_ads'];
$adTab = (string)$doc['ad_table'];
$uid   = current_user_id();

$allowed = $docs->adIsPublic($idAds, $adTab)
        || ($uid !== null && $docs->ownsAd($idAds, $adTab, $uid));
if (!$allowed) { http_response_code(403); exit('Forbidden'); }

$path = AdsDocuments::storageDir() . basename((string)$doc['file_name']);
if (!is_file($path)) { http_response_code(404); exit('File missing'); }

// Tracciamento (non deve bloccare il download in caso di errore)
$ipHash = isset($_SERVER['REMOTE_ADDR']) ? hash('sha256', (string)$_SERVER['REMOTE_ADDR']) : null;
try {
    $docs->logDownload($id, $uid, $ipHash);
    $docs->bumpPdfDownloads($idAds, $adTab);
} catch (Throwable $ex) {
    error_log('[Allonwheel] download_doc tracking: ' . $ex->getMessage());
}

$mime = (string)($doc['mime'] ?? 'application/octet-stream');
$name = str_replace(['"', "\r", "\n"], '', (string)($doc['original_name'] ?? 'document'));
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . (string)filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
