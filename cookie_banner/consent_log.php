<?php
// cookie_banner/consent_log.php — registra la prova del consenso (Art. 7(1) GDPR)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{}'); }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['categories'])) { http_response_code(400); exit('{}'); }

// IP pseudonimizzato (prova senza conservare l'IP in chiaro)
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';
$ip_hash = hash('sha256', $ip . '|aow-salt');   // sostituire con un salt server-side reale
$ua      = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$cats    = json_encode($data['categories'], JSON_UNESCAPED_UNICODE);
$ver     = substr((string)($data['version'] ?? ''), 0, 20);
$action  = ($data['categories']['analytics'] || $data['categories']['marketing']) ? 'grant' : 'deny';

try {
    $stmt = $pdo->prepare(
      'INSERT INTO consent_log (consent_id, ip_hash, user_agent, categories, consent_version, action)
       VALUES (UUID(), :ip, :ua, :cats, :ver, :act)'
    );
    $stmt->execute([':ip' => $ip_hash, ':ua' => $ua, ':cats' => $cats, ':ver' => $ver, ':act' => $action]);
} catch (Throwable $e) {
    error_log('[Allonwheel] consent_log: ' . $e->getMessage());
}
echo json_encode(['ok' => true]);
