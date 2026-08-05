<?php
// ============================================================
// config/session_helper.php
// Helper centralizzato per la gestione della sessione utente.
//
// Tutte le pagine PHP del progetto DEVONO usare queste funzioni
// invece di leggere $_SESSION['user_id'] / $_SESSION['session_id*']
// direttamente. Questo garantisce che le chiavi di sessione possano
// essere cambiate in un solo punto senza rompere l'intera applicazione.
//
// Storia: nel progetto convivevano 6 chiavi diverse per identificare
// l'utente loggato (session_id, session_id_user, session_username,
// session_email, session_phone, user_id), ognuna usata da file diversi.
// Questo helper le unifica fornendo un'unica API.
// ============================================================

if (defined('templatemo_SESSION_HELPER_LOADED')) {
  return;
}
define('templatemo_SESSION_HELPER_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
  // Cookie di sessione hardened
  $cookie_params = [
    'lifetime' => 0,
    'path'   => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ];
  session_set_cookie_params($cookie_params);
  session_start();
}

/**
 * Ritorna l'ID dell'utente loggato, o null se nessun utente è loggato.
 * Accetta sia la chiave nuova ('user_id') sia quella legacy ('session_id_user')
 * per compatibilità durante la transizione. Le pagine refactorizzate
 * useranno SEMPRE questa funzione, mai $_SESSION direttamente.
 */
function current_user_id(): ?int
{
  $id = $_SESSION['user_id']
    ?? $_SESSION['session_id_user']
    ?? null;

  if ($id === null || (int)$id <= 0) {
    return null;
  }
  return (int)$id;
}

/**
 * Ritorna lo username dell'utente loggato, o stringa vuota.
 */
function current_username(): string
{
  return (string)($_SESSION['username']
    ?? $_SESSION['session_username']
    ?? '');
}

/**
 * Ritorna l'email dell'utente loggato, o stringa vuota.
 */
function current_user_email(): string
{
  return (string)($_SESSION['email']
    ?? $_SESSION['session_email']
    ?? '');
}

/**
 * True se l'utente è loggato.
 */
function is_user_logged_in(): bool
{
  return current_user_id() !== null;
}

/**
 * Forza il login: se l'utente non è loggato esegue redirect a newlogin.php
 * salvando l'URL corrente per il redirect post-login.
 *
 * USO TIPICO (prima riga di ogni pagina protetta):
 * require_once __DIR__ . '/../config/session_helper.php';
 * $user_id = require_user_logged_in();
 *
 * Ritorna l'id_user (int) per uso immediato.
 */
function require_user_logged_in(): int
{
  $user_id = current_user_id();

  if ($user_id === null) {
    // Salva l'URL corrente per ritornarci dopo il login
    $current_url = $_SERVER['REQUEST_URI'] ?? '/';
    $_SESSION['redirect_after_login'] = $current_url;

    // Redirect ASSOLUTO alla radice: funziona da qualsiasi sottocartella
    // (07_rent, 04_request_offer, 05_wanted, ...), senza elenco di cartelle.
    $aow_login_url = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/01_login/newlogin.php';
    header('Location: ' . $aow_login_url);
    exit;
  }

  return $user_id;
}

/**
 * Imposta tutte le chiavi di sessione utente in modo coerente.
 * Da chiamare SOLO da login.php / register.php dopo autenticazione riuscita.
 *
 * Imposta sia le chiavi NUOVE sia quelle LEGACY in modo che i vecchi
 * file (non ancora refactorizzati) continuino a funzionare.
 */
function login_user(array $user_row): void
{
  if (empty($user_row['id_user'])) {
    throw new InvalidArgumentException('login_user: id_user mancante');
  }

  // Rigenera ID di sessione per prevenire session fixation
  session_regenerate_id(true);

  // === Chiavi nuove (canoniche) ===
  $_SESSION['user_id']  = (int)$user_row['id_user'];
  $_SESSION['username'] = (string)($user_row['username'] ?? '');
  $_SESSION['email']  = (string)($user_row['email'] ?? '');

  // === Chiavi legacy (compatibilità) ===
  // Da rimuovere quando tutti i file 02_*, 03_*, header.php saranno
  // refactorizzati per usare current_user_id() / current_username().
  $_SESSION['session_id']   = session_id();
  $_SESSION['session_id_user']  = (int)$user_row['id_user'];
  $_SESSION['session_username'] = (string)($user_row['username'] ?? '');
  $_SESSION['session_email']  = (string)($user_row['email'] ?? '');
  $_SESSION['session_phone']  = (string)($user_row['phone'] ?? '');
}

/**
 * Logout completo: distrugge la sessione lato server e lato client.
 */
function logout_user(): void
{
  $_SESSION = [];

  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
    );
  }

  session_destroy();
}
?>
