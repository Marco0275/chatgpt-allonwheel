<?php
// ============================================================
// 01_login/login.php — Processore del login
// Riceve il POST da newlogin.php (campi: email, password, csrf_token)
//
// Modifiche rispetto alla versione precedente:
//  - Usa session_helper::login_user() invece di impostare manualmente
//  le 6 chiavi di sessione duplicate.
//  - Aggiunta protezione brute-force PER IP (oltre che per email).
//  Il rate-limit precedente bloccava solo la stessa email; un
//  attacker con email valide multiple poteva fare 10×N tentativi
//  dallo stesso IP. Ora: 10/email/2h + 30/IP/2h.
//  - Mantenute le stesse risposte/redirect dell'originale per non
//  rompere newlogin.php / login_error_activation.php.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

// Accetta solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
  header('Location: ' . BASE_URL . '/01_login/newlogin.php');
  exit;
}

// Verifica CSRF (one-shot: questo è un form singolo, non un wizard)
csrf_verify();

$email  = trim($_POST['email']  ?? '');
$password = $_POST['password']  ?? '';

if ($email === '' || $password === '') {
  $_SESSION['login_message'] = 'Please fill in all fields.';
  header('Location: ' . BASE_URL . '/01_login/login_error_activation.php');
  exit;
}

// ============================================================
// PROTEZIONE BRUTE-FORCE
// ============================================================
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Soglie (tunabili)
const MAX_ATTEMPTS_PER_EMAIL = 10;
const MAX_ATTEMPTS_PER_IP  = 30;
const LOCKOUT_WINDOW_HOURS = 2;

try {
  // Limit per email
  $stmtEmail = $pdo->prepare(
    'SELECT COUNT(*) FROM login_attempts
    WHERE email = :email
    AND attempted_at > NOW() - INTERVAL :hours HOUR'
  );
  $stmtEmail->bindValue(':email', $email, PDO::PARAM_STR);
  $stmtEmail->bindValue(':hours', LOCKOUT_WINDOW_HOURS, PDO::PARAM_INT);
  $stmtEmail->execute();
  $attempts_email = (int)$stmtEmail->fetchColumn();

  if ($attempts_email >= MAX_ATTEMPTS_PER_EMAIL) {
    $_SESSION['login_message'] = 'Too many failed login attempts for this email. Please try again in 2 hours or reset your password.';
    header('Location: ' . BASE_URL . '/01_login/login_error_activation.php');
    exit;
  }

  // Limit per IP (issue #11 dell'audit — non era presente nella versione precedente)
  $stmtIp = $pdo->prepare(
    'SELECT COUNT(*) FROM login_attempts
    WHERE ip_address = :ip
    AND attempted_at > NOW() - INTERVAL :hours HOUR'
  );
  $stmtIp->bindValue(':ip', $ip, PDO::PARAM_STR);
  $stmtIp->bindValue(':hours', LOCKOUT_WINDOW_HOURS, PDO::PARAM_INT);
  $stmtIp->execute();
  $attempts_ip = (int)$stmtIp->fetchColumn();

  if ($attempts_ip >= MAX_ATTEMPTS_PER_IP) {
    // Non riveliamo che è IP-based per non aiutare l'attacker a profilare
    $_SESSION['login_message'] = 'Too many failed login attempts. Please try again later.';
    error_log('[Allonwheel] IP rate-limit hit: ' . $ip . ' (' . $attempts_ip . ' attempts in ' . LOCKOUT_WINDOW_HOURS . 'h)');
    header('Location: ' . BASE_URL . '/01_login/login_error_activation.php');
    exit;
  }
} catch (PDOException $e) {
  error_log('[Allonwheel] login_attempts query failed: ' . $e->getMessage());
  // Non blocchiamo il login se la tabella di rate-limit ha problemi.
  // Fail-open è meglio che bloccare gli utenti legittimi se il DB ha
  // un problema isolato sulla tabella di logging.
}

// ============================================================
// VERIFICA CREDENZIALI
// ============================================================
$stmt = $pdo->prepare(
  'SELECT id_user, username, email, phone, password, is_verified
   FROM users
  WHERE email = :email
  LIMIT 1'
);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  // Logga il tentativo fallito (sia email che IP per il prossimo check)
  try {
    $pdo->prepare(
    'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
    )->execute([':email' => $email, ':ip' => $ip]);
  } catch (PDOException $e) {
    error_log('[Allonwheel] Could not log login attempt: ' . $e->getMessage());
  }

  $_SESSION['login_message'] = 'Incorrect email or password. Please try again.';
  header('Location: ' . BASE_URL . '/01_login/login_error_activation.php');
  exit;
}

if ((int)$user['is_verified'] !== 1) {
  $_SESSION['login_message'] = 'Your account has not been verified yet. Please check your email for the activation link.';
  header('Location: ' . BASE_URL . '/01_login/login_error_activation.php');
  exit;
}

// ============================================================
// LOGIN RIUSCITO
// ============================================================

// Pulisci i tentativi falliti per questa email (è autenticata correttamente)
try {
  $pdo->prepare('DELETE FROM login_attempts WHERE email = :email')
    ->execute([':email' => $email]);
} catch (PDOException $e) {
  // Non bloccante
}

// Imposta TUTTE le chiavi di sessione (canoniche + legacy) in un colpo solo.
// Vedi config/session_helper.php — questa funzione fa anche il
// session_regenerate_id() per prevenire session fixation.
login_user($user);

// ---- Bozza annuncio compilata da ospite (17 lug 2026) ----
// Se prima di accedere aveva compilato il wizard, quella bozza e' sua da
// adesso. claim() assegna SOLO bozze ancora senza proprietario, quindi un
// token riciclato non puo' rubare la bozza di un altro.
// Non bloccante: un problema qui non deve impedire il login.
try {
    require_once __DIR__ . '/../libs/ad_draft.class.php';
    $aow_dtok = AdDraft::currentToken(false); // false = non crearne uno nuovo
    if ($aow_dtok !== '') {
        AdDraft::claim($pdo, $aow_dtok, (int)$user['id_user']);
    }
} catch (Throwable $e) {
    error_log('[Allonwheel] login: claim bozza: ' . $e->getMessage());
}

// ---- Ritorno al punto in cui l'utente era stato interrotto (17 lug 2026) ----
// require_user_logged_in() (config/session_helper.php:99) salvava gia'
// $_SESSION['redirect_after_login'] con l'URL richiesto, ma NESSUNO lo
// leggeva: dopo il login si finiva sempre in dashboard. Chi cliccava
// "vendi il tuo veicolo" veniva mandato al login e poi scaricato sulla
// dashboard, dovendo ritrovare da solo la strada per il wizard.
// Ora si torna dove si voleva andare.
$aow_dest = BASE_URL . '/01_login/dashboard.php';
$aow_ret  = $_SESSION['redirect_after_login'] ?? '';
unset($_SESSION['redirect_after_login']); // one-shot: vale per questo login

if (is_string($aow_ret) && $aow_ret !== '') {
    // SICUREZZA: si accettano SOLO percorsi relativi di questo sito.
    // Un URL assoluto ("https://...") o protocol-relative ("//evil.com")
    // trasformerebbe il login in un open redirect: un link malevolo
    // porterebbe l'utente, gia' autenticato, su un dominio di phishing.
    // Rifiutati anche i backslash (\evil.com e' interpretato come // da
    // alcuni browser) e i newline (header injection).
    $aow_ok = ($aow_ret[0] === '/')
        && strpos($aow_ret, '//') !== 0
        && strpos($aow_ret, '/\\') !== 0
        && strpos($aow_ret, '\\') === false
        && strpbrk($aow_ret, "\r\n") === false;
    if ($aow_ok) { $aow_dest = $aow_ret; }
}

header('Location: ' . $aow_dest);
exit;
?>
