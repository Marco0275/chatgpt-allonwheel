<?php
// ============================================================
// scripts/purge_personal_data.php — Retention (GDPR Art. 5(1)(e))
// Da eseguire da cron (es. giornaliero):
//   php /percorso/scripts/purge_personal_data.php
//
// Cancella i dati personali oltre il periodo di conservazione:
//   - login_attempts (email + IP) piu' vecchi di 90 giorni
// Eseguibile SOLO da CLI (non via web).
// ============================================================

// Bootstrap PRIMA del controllo accessi (carica .env -> CRON_TOKEN)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

// ---- Controllo accessi (CLI oppure HTTP con token cron) ----
// Consentito da: (a) riga di comando (cron locale) oppure (b) HTTP con token
// valido (cron-job.org). Il token e' in .env come CRON_TOKEN, inviato via
// header 'X-Cron-Token' (consigliato) o ?token=. Senza token valido -> 403.
$is_cli = (PHP_SAPI === 'cli');
if (!$is_cli) {
    $expected = (string) getenv('CRON_TOKEN');
    $provided = (string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? $_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        exit('403 Forbidden' . PHP_EOL);
    }
    header('Content-Type: text/plain');
}


const RETENTION_LOGIN_ATTEMPTS_DAYS = 90;

try {
    $stmt = $pdo->prepare(
        'DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL :days DAY'
    );
    $stmt->bindValue(':days', RETENTION_LOGIN_ATTEMPTS_DAYS, PDO::PARAM_INT);
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo '[' . date('c') . "] purge login_attempts: {$deleted} rows removed\r\n";
} catch (Throwable $e) {
    error_log('[Allonwheel] purge_personal_data: ' . $e->getMessage());
    fwrite(STDERR, "purge error: " . $e->getMessage() . "\r\n");
    exit(1);
}

// ---- Bozze annuncio scadute (17 lug 2026) ----
// Una bozza di un ospite contiene email e telefono di chi NON si e' mai
// registrato e non ha mai dato un consenso: tenerla oltre il necessario
// sarebbe una raccolta silenziosa di dati personali (GDPR Art. 5(1)(e),
// stessa ragione del resto di questo script).
// In blocco separato: se la tabella non c'e' ancora (patch non applicata),
// il purge delle altre tabelle deve restare valido.
try {
    require_once __DIR__ . '/../libs/ad_draft.class.php';
    $drafts_removed = AdDraft::purgeExpired($pdo);
    echo '[' . date('c') . "] purge ad_drafts: {$drafts_removed} rows removed\r\n";
} catch (Throwable $e) {
    // Non fatale: la tabella potrebbe non esistere ancora.
    error_log('[Allonwheel] purge ad_drafts: ' . $e->getMessage());
    echo '[' . date('c') . "] purge ad_drafts: skipped (" . $e->getMessage() . ")\r\n";
}

echo "purge done\r\n";
