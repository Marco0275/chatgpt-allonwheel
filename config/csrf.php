<?php
// ============================================================
// config/csrf.php — Helper CSRF (versione 2)
// Helper per la protezione CSRF (Cross-Site Request Forgery).
//
// API:
// csrf_generate()      — token one-shot (consumato da csrf_verify)
// csrf_verify()      — verifica token + lo invalida (form one-shot)
//
// csrf_generate_persistent() — token che NON viene invalidato dopo verify.
//            USARE per wizard multi-step (es. insert_ad
//            → upload_image → upload_gallery), dove il
//            flusso passa per più POST consecutivi.
// csrf_verify_persistent()   — verifica senza consumare il token.
//
// REGOLA D'USO:
// - Form unico (delete, save_company, login)   → csrf_generate / csrf_verify
// - Form multi-step nello stesso flusso      → csrf_generate_persistent / csrf_verify_persistent
// - All'inizio di un nuovo flusso (nuovo wizard)   → csrf_rotate_persistent()
// ============================================================

if (defined('templatemo_CSRF_LOADED')) {
  return;
}
define('templatemo_CSRF_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// =============================================================
// VARIANTE ONE-SHOT (per form singoli — delete, login, save)
// =============================================================

/**
 * Genera un token CSRF one-shot e lo salva in sessione.
 * Restituisce l'HTML del campo hidden da inserire nel form.
 *
 * Se un token esiste già in sessione, lo riusa (idempotente).
 */
function csrf_generate(): string
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $token = $_SESSION['csrf_token'];
  return '<input type="hidden" name="csrf_token" value="'
    . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Verifica il token CSRF nel POST. In caso di mismatch interrompe con HTTP 403.
 * Dopo la verifica IL TOKEN VIENE CONSUMATO (rimosso dalla sessione).
 *
 * NON USARE in wizard multi-step: il primo POST funziona, i successivi
 * falliscono perché il token è già stato consumato.
 */
function csrf_verify(): void
{
  $posted = $_POST['csrf_token'] ?? '';
  $stored = $_SESSION['csrf_token'] ?? '';

  if (empty($posted) || empty($stored) || !hash_equals($stored, $posted)) {
    http_response_code(403);
    error_log('[Allonwheel] CSRF token mismatch (one-shot) — possibile attacco CSRF.');
    exit('Request not allowed. Please go back and try again.');
  }

  // Consuma il token: previene replay
  unset($_SESSION['csrf_token']);
}

// =============================================================
// VARIANTE PERSISTENTE (per wizard multi-step)
// =============================================================

/**
 * Genera (o riusa) un token CSRF persistente salvato in $_SESSION['csrf_persistent_token'].
 * Restituisce l'HTML del campo hidden.
 *
 * Il token rimane valido per l'intera durata del wizard. Da chiamare in
 * OGNI form del flusso (passo 1, passo 2, passo 3).
 */
function csrf_generate_persistent(): string
{
  if (empty($_SESSION['csrf_persistent_token'])) {
    $_SESSION['csrf_persistent_token'] = bin2hex(random_bytes(32));
  }
  $token = $_SESSION['csrf_persistent_token'];
  return '<input type="hidden" name="csrf_token" value="'
    . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Verifica il token persistente SENZA consumarlo.
 * In caso di mismatch interrompe con HTTP 403.
 */
function csrf_verify_persistent(): void
{
  $posted = $_POST['csrf_token'] ?? '';
  $stored = $_SESSION['csrf_persistent_token'] ?? '';

  if (empty($posted) || empty($stored) || !hash_equals($stored, $posted)) {
    http_response_code(403);
    error_log('[Allonwheel] CSRF token mismatch (persistent) — possibile attacco CSRF.');
    exit('Request not allowed. Please go back and try again.');
  }
  // NB: token NON consumato — riutilizzabile per il prossimo step
}

/**
 * Rigenera il token persistente — da chiamare quando un wizard è
 * COMPLETATO (es. dopo l'ultimo save) per forzare un nuovo token nel
 * prossimo wizard. Senza questa rotazione, il token resta valido a tempo
 * indefinito (per la durata della sessione PHP).
 */
function csrf_rotate_persistent(): void
{
  unset($_SESSION['csrf_persistent_token']);
}
?>
