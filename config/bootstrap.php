<?php
// ============================================================
// config/bootstrap.php (versione 2)
// Carica il file .env con le variabili d'ambiente E applica gli
// header HTTP di sicurezza globalmente.
//
// Struttura attesa sul server:
// /home/user/.env    ← file .env (fuori webroot)
// /home/user/htdocs/   ← webroot
// /home/user/htdocs/config/  ← questa cartella
//
// Modifiche rispetto alla v1:
//  - Inclusione automatica di security_headers.php (CSP, HSTS, etc.)
//  Gli header vengono inviati prima di qualsiasi output, purché
//  bootstrap.php sia incluso prima di emettere HTML.
// ============================================================

// Evita doppio caricamento
if (defined('templatemo_BOOTSTRAP_LOADED')) {
  return;
}
define('templatemo_BOOTSTRAP_LOADED', true);

// ------------------------------------------------------------
// 1. Header HTTP di sicurezza
// ------------------------------------------------------------
// IMPORTANTE: questo include va PRIMA di qualsiasi altra cosa che
// possa fare echo / header(). security_headers.php è idempotente.
require_once __DIR__ . '/security_headers.php';

// ------------------------------------------------------------
// 2. URL base del sito — unico punto di configurazione
// ------------------------------------------------------------
if (!defined('BASE_URL')) {
  define('BASE_URL', 'https://www.allonwheel.com');
}

// ------------------------------------------------------------
// 3. Caricamento variabili d'ambiente da .env
// ------------------------------------------------------------
// __DIR__ = /htdocs/config → dirname = /htdocs → dirname = root progetto
$envFile = dirname(dirname(__DIR__)) . '/.env';

// Fallback: cerca .env anche nella root della webroot (utile in dev locale)
if (!file_exists($envFile)) {
  $envFile = dirname(__DIR__) . '/.env';
}

if (!file_exists($envFile)) {
  error_log('[Allonwheel] .env file not found. Searched: ' . $envFile);
  http_response_code(503);
  exit('Service temporarily unavailable.');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
  $line = trim($line);

  // Salta righe vuote e commenti
  if ($line === '' || strncmp($line, '#', 1) === 0) {
    continue;
  }

  if (strpos($line, '=') !== false) {
    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    // Strip quotes circostanti se presenti (es. KEY="value")
    if (strlen($value) >= 2) {
    $first = $value[0];
    $last  = $value[strlen($value) - 1];
    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
      $value = substr($value, 1, -1);
    }
    }

    if (!getenv($key)) {
    putenv("$key=$value");
    $_ENV[$key]  = $value;
    $_SERVER[$key] = $value;
    }
  }
}

// ------------------------------------------------------------
// 4. Internazionalizzazione (fondamenta - architettura /en/)
// ------------------------------------------------------------
require_once __DIR__ . '/i18n.php';

// Flag di prodotto (moderazione, tetti RFQ/wanted). Vedi il file per i default.
require_once __DIR__ . '/app_settings.php';
?>
