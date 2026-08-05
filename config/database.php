<?php
// ============================================================
// config/database.php
// Connessione al database: crea $pdo (PDO), unico driver del progetto.
// Tutte le classi (CompanyManager, UserRoles) e le pagine usano $pdo;
// non viene piu' aperta alcuna connessione MySQLi.
//
// ⚠️  NON inserire mai password in questo file.
// ⚠️  NON committare questo file con dati reali su Git.
// ============================================================

require_once __DIR__ . '/bootstrap.php';

$db_host   = getenv('DB_HOST');
$db_name   = getenv('DB_NAME');
$db_user   = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

if (!$db_host || !$db_name || !$db_user || $db_password === false || $db_password === '') {
  error_log('[Allonwheel] FATAL: Database credentials not found in environment variables.');
  http_response_code(503);
  exit('Service temporarily unavailable. Please try again later.');
}

// -------------------------------------------------------
// Connessione PDO (unico driver, usata da tutto il progetto)
// -------------------------------------------------------
$pdo_options = [
  PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
  PDO::ATTR_PERSISTENT   => false,
];

$db_dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
  $pdo = new PDO($db_dsn, $db_user, $db_password, $pdo_options);
} catch (PDOException $e) {
  error_log('[Allonwheel] PDO connection failed: ' . $e->getMessage());
  http_response_code(503);
  exit('Unable to connect to the database. Please try again later.');
}

// Pulisci le variabili sensibili dalla memoria
unset($db_host, $db_name, $db_user, $db_password, $db_dsn, $pdo_options);
?>
