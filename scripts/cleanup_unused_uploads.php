<?php
// ============================================================
// scripts/cleanup_unused_uploads.php
// Elimina i file fisici in /upload_image/ non referenziati
// da nessun record del database.
//
// SICUREZZA:
//  - Opera SOLO sulla cartella /upload_image/ (non tocca altro).
//  - I file .htaccess vengono sempre preservati.
//  - Usa realpath() per prevenire path traversal.
//  - Modalità dry-run: php cleanup_unused_uploads.php --dry-run
//
// UTILIZZO (cron o manuale, mai via HTTP):
//  php scripts/cleanup_unused_uploads.php
//  php scripts/cleanup_unused_uploads.php --dry-run
//
// Protezione web: il file .htaccess in scripts/ blocca l'accesso HTTP.
// ============================================================

declare(strict_types=1);

// ---- Percorsi ----
$webroot    = dirname(__DIR__); // /path/to/htdocs
$upload_dir = $webroot . '/upload_image';

// ---- Bootstrap + PDO (PRIMA del controllo accessi: carica .env -> CRON_TOKEN) ----
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';

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

// ---- Modalità dry-run (CLI: --dry-run | HTTP: ?dry-run=1) ----
$dry_run = (in_array('--dry-run', $argv ?? [], true)) || isset($_GET['dry-run']);
// $pdo disponibile

// ---- Funzione di log ----
function cleanupLog(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// ============================================================
// 1. Verifica che la cartella /upload_image/ esista
// ============================================================
$real_upload = realpath($upload_dir);
if ($real_upload === false || !is_dir($real_upload)) {
    cleanupLog('ERRORE: directory upload_image non trovata in: ' . $upload_dir);
    exit(1);
}

cleanupLog('=== cleanup_unused_uploads START' . ($dry_run ? ' [DRY-RUN]' : '') . ' ===');
cleanupLog('Scansione: ' . $real_upload);

// ============================================================
// 2. Costruisci la lista dei file AMMESSI dal database
// ============================================================
$allowed = []; // set: $realpath => true

/**
 * Aggiunge i percorsi fisici di un filename alle due sottodirectory
 * (original/ e thumbnail/) di una cartella modulo.
 */
function addModuleFile(
    string  $base_dir,  // es. $real_upload . '/02_free_ads'
    ?string $filename,
    array  &$allowed
): void {
    $filename = trim((string)$filename);
    if ($filename === '' || $filename === 'no_image.jpg') {
        return;
    }
    $safe = basename($filename);
    foreach (['original', 'thumbnail'] as $sub) {
        $candidate = $base_dir . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . $safe;
        $real = realpath($candidate);
        if ($real !== false) {
            $allowed[$real] = true;
        } else {
            // Il file potrebbe non esistere ancora: registriamo il path
            // normalizzato per evitare falsi positivi su file appena creati.
            $allowed[$candidate] = true;
        }
    }
}

/**
 * Aggiunge un file che sta DIRETTAMENTE nella cartella base
 * (vecchio schema flat, es. 06_company legacy o future varianti).
 */
function addFlatFile(
    string  $base_dir,
    ?string $filename,
    array  &$allowed
): void {
    $filename = trim((string)$filename);
    if ($filename === '' || $filename === 'no_image.jpg') {
        return;
    }
    $safe = basename($filename);
    $candidate = $base_dir . DIRECTORY_SEPARATOR . $safe;
    $real = realpath($candidate);
    if ($real !== false) {
        $allowed[$real] = true;
    } else {
        $allowed[$candidate] = true;
    }
}

try {
    // ----------------------------------------------------------
    // 02_free_ads — immagine principale
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT image_original, image_thumbnail FROM `02_free_ads`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '02_free_ads';
        addModuleFile($base, $row['image_original'],  $allowed);
        addModuleFile($base, $row['image_thumbnail'], $allowed);
    }

    // ----------------------------------------------------------
    // 02_free_ads_gallery
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT image_original, image_thumbnail FROM `02_free_ads_gallery`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '02_free_ads';
        addModuleFile($base, $row['image_original'],  $allowed);
        addModuleFile($base, $row['image_thumbnail'], $allowed);
    }

    // ----------------------------------------------------------
    // 03_ads — immagine principale
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT image_original, image_thumbnail FROM `03_ads`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '03_ads';
        addModuleFile($base, $row['image_original'],  $allowed);
        addModuleFile($base, $row['image_thumbnail'], $allowed);
    }

    // ----------------------------------------------------------
    // 03_ads_gallery
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT image_original, image_thumbnail FROM `03_ads_gallery`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '03_ads';
        addModuleFile($base, $row['image_original'],  $allowed);
        addModuleFile($base, $row['image_thumbnail'], $allowed);
    }

    // ----------------------------------------------------------
    // 06_company — logo (salvato in original/ e thumbnail/)
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT logo FROM `06_company`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '06_company';
        addModuleFile($base, $row['logo'], $allowed);
        // Compatibilità con vecchi upload flat (pre-refactoring)
        addFlatFile($base, $row['logo'], $allowed);
    }

    // ----------------------------------------------------------
    // 06_company_gallery — immagine (salvata in original/ e thumbnail/)
    // ----------------------------------------------------------
    $stmt = $pdo->query('SELECT immagine FROM `06_company_gallery`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $base = $real_upload . DIRECTORY_SEPARATOR . '06_company';
        addModuleFile($base, $row['immagine'], $allowed);
        // Compatibilità con vecchi upload flat
        addFlatFile($base, $row['immagine'], $allowed);
    }

} catch (PDOException $e) {
    cleanupLog('ERRORE DB: ' . $e->getMessage());
    exit(1);
}

cleanupLog('File ammessi dal DB: ' . count($allowed));

// ============================================================
// 3. Scansiona tutti i file fisici in /upload_image/
// ============================================================
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($real_upload, FilesystemIterator::SKIP_DOTS)
);

$deleted = 0;
$errors  = 0;
$skipped = 0;

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $filepath = str_replace('\\', '/', $fileInfo->getRealPath());

    // Preserva sempre i file .htaccess
    if (basename($filepath) === '.htaccess') {
        $skipped++;
        continue;
    }

    // Verifica che il file sia effettivamente dentro /upload_image/
    if (strpos($filepath, str_replace('\\', '/', $real_upload)) !== 0) {
        cleanupLog('WARN: path traversal bloccato: ' . $filepath);
        continue;
    }

    // Confronto con la lista ammessa (usa sia il real path che il path normalizzato)
    $in_allowed = isset($allowed[$fileInfo->getRealPath()])
               || isset($allowed[$filepath]);

    if ($in_allowed) {
        $skipped++;
        continue;
    }

    // File non referenziato → elimina (o simula)
    if ($dry_run) {
        cleanupLog('[dry-run] eliminerei: ' . $filepath);
        $deleted++;
    } else {
        if (@unlink($fileInfo->getRealPath())) {
            cleanupLog('Eliminato: ' . $filepath);
            $deleted++;
        } else {
            cleanupLog('ERRORE eliminazione: ' . $filepath);
            $errors++;
        }
    }
}

cleanupLog('=== FINE: eliminati=' . $deleted
         . ' mantenuti=' . $skipped
         . ' errori=' . $errors
         . ($dry_run ? ' [DRY-RUN]' : '') . ' ===');

exit($errors > 0 ? 1 : 0);
