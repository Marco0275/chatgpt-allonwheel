<?php
// ============================================================
// scripts/expire_ads.php
// Eliminazione automatica degli annunci scaduti dal database.
//
// COMPORTAMENTO:
//   Cancella SOLO i record dal database quando l'inserzione ha
//   raggiunto la propria scadenza. NON tocca i file fisici su disco.
//   La pulizia dei file orfani è delegata a cleanup_unused_uploads.php.
//
// SCADENZE:
//   02_free_ads  → 45 giorni da created_at (colonna expires_at)
//   03_ads       → 60 giorni da created_at (colonna expires_at)
//
// UTILIZZO (cron job giornaliero):
//   0 3 * * * php /home/<user>/htdocs/scripts/expire_ads.php >> /var/log/templatemo_expire.log 2>&1
//
// Può essere eseguito anche manualmente da CLI:
//   php scripts/expire_ads.php
//   php scripts/expire_ads.php --dry-run   ← solo simulazione, nessuna cancellazione
//
// SICUREZZA:
//   - Questo script NON deve essere raggiungibile via HTTP.
//   - Proteggere con .htaccess (già presente in scripts/) o
//     spostandolo fuori dalla webroot.
// ============================================================

// ---- Percorsi + bootstrap (carica .env, costanti, PDO) ----
// Il bootstrap va caricato PRIMA del controllo accessi perche' il token
// cron e' definito in .env (CRON_TOKEN) e letto via getenv().
$webroot = dirname(__DIR__);
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';

// ---- Controllo accessi ----
// Consentito da: (a) riga di comando (cron locale) oppure (b) HTTP con token
// valido, per permettere a servizi esterni come cron-job.org di richiamarlo.
// Il token va impostato in .env come CRON_TOKEN e inviato dal cron esterno
// nell'header 'X-Cron-Token' (consigliato) o come parametro ?token=.
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

// ---- Modalità dry-run (CLI: --dry-run | HTTP: ?dry-run=1) ----
$dry_run = (in_array('--dry-run', $argv ?? [], true)) || isset($_GET['dry-run']);
