<?php
// ============================================================
// libs/admin_auth.class.php
// Autenticazione admin SEPARATA dall'autenticazione utente.
//
// Anche se un utente è già loggato come "admin tier", per accedere
// al pannello deve fare un secondo step di autenticazione con la sua
// password. Questo è un requisito di sicurezza standard:
// - protegge da session hijack a basso impatto (un attacker che ruba
//   il cookie di sessione di Marco non ha la sua password)
// - rende esplicita l'azione "sto entrando in modalità admin"
//
// La sessione admin scade dopo templatemo_ADMIN_SESSION_MINUTES minuti
// di inattività. Ogni richiesta al pannello rinfresca il timestamp.
// ============================================================

if (defined('templatemo_ADMIN_AUTH_LOADED')) {
  return;
}
define('templatemo_ADMIN_AUTH_LOADED', true);

class AdminAuth
{
  // Email dell'unico admin abilitato. Per cambiarla in futuro:
  // 1. UPDATE users SET user_tier='admin' WHERE email='nuovo@indirizzo.it';
  // 2. UPDATE users SET user_tier='free'  WHERE email='vecchio@indirizzo.it';
  // 3. cambia questa costante.
  const ADMIN_EMAIL = 'marco.candian@yahoo.it';

  // Durata della sessione admin (minuti di inattività prima del re-login)
  const ADMIN_SESSION_MINUTES = 30;

  // Rate limit login admin (separato da quello utente)
  const ADMIN_MAX_ATTEMPTS_PER_IP = 8;
  const ADMIN_LOCKOUT_HOURS = 2;

  /**
   * Tenta il login admin. Ritorna ['ok' => bool, 'message' => string, 'user_id' => ?int].
   *
   * Doppio check:
   * 1. email + password verificate contro `users`
   * 2. user_tier deve essere 'admin'
   * 3. email deve corrispondere a self::ADMIN_EMAIL (anche se in DB ci fossero
   *  altri utenti con tier=admin, qui solo l'email codificata può loggarsi)
   */
  public static function attemptLogin(PDO $pdo, string $email, string $password, string $ip): array
  {
    $email = trim(strtolower($email));

    // Whitelist email a livello di codice (defense in depth oltre al check tier)
    if ($email !== strtolower(self::ADMIN_EMAIL)) {
    self::logFailedAttempt($pdo, $email, $ip);
    // Stesso messaggio del caso "password sbagliata" — non riveliamo info
    return ['ok' => false, 'message' => 'Invalid credentials.', 'user_id' => null];
    }

    // Rate limit per IP
    if (self::isIpLockedOut($pdo, $ip)) {
    return [
      'ok'  => false,
      'message' => 'Too many failed attempts. Please try again later.',
      'user_id' => null,
    ];
    }

    // Verifica credenziali nel DB
    $stmt = $pdo->prepare(
    'SELECT id_user, username, email, password, user_tier, is_verified
     FROM users
      WHERE email = :email
      LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password'])) {
    self::logFailedAttempt($pdo, $email, $ip);
    return ['ok' => false, 'message' => 'Invalid credentials.', 'user_id' => null];
    }

    if ((int)$row['is_verified'] !== 1) {
    return ['ok' => false, 'message' => 'Account not verified.', 'user_id' => null];
    }

    if ($row['user_tier'] !== 'admin') {
    // Stesso messaggio del caso credenziali sbagliate
    self::logFailedAttempt($pdo, $email, $ip);
    return ['ok' => false, 'message' => 'Invalid credentials.', 'user_id' => null];
    }

    // Login OK: rigenera ID di sessione, pulisce tentativi falliti
    session_regenerate_id(true);
    $_SESSION['admin_user_id']    = (int)$row['id_user'];
    $_SESSION['admin_username']   = (string)$row['username'];
    $_SESSION['admin_authenticated']  = true;
    $_SESSION['admin_last_activity']  = time();
    $_SESSION['admin_login_ip']   = $ip;

    // Pulizia tentativi (la stessa email è stata autenticata correttamente)
    try {
    $pdo->prepare('DELETE FROM login_attempts WHERE email = :email')
      ->execute([':email' => $email]);
    } catch (PDOException $e) {
    // non bloccante
    }

    // Audit
    if (class_exists('UserTier')) {
    UserTier::logAdminAction(
      $pdo,
      (int)$row['id_user'],
      'admin_login',
      null,
      'Admin login successful',
      $ip
    );
    }

    return ['ok' => true, 'message' => 'Login successful.', 'user_id' => (int)$row['id_user']];
  }

  /**
   * Verifica che la sessione admin sia ancora valida.
   * Se non lo è, redirect al login admin.
   *
   * Da chiamare in CIMA a OGNI pagina del pannello /_admin/.
   */
  public static function requireAdminSession(): int
  {
    if (
    empty($_SESSION['admin_authenticated']) ||
    empty($_SESSION['admin_user_id']) ||
    empty($_SESSION['admin_last_activity'])
    ) {
    self::redirectToLogin();
    }

    // Timeout per inattività
    $idle = time() - (int)$_SESSION['admin_last_activity'];
    if ($idle > self::ADMIN_SESSION_MINUTES * 60) {
    self::logout();
    $_SESSION['admin_login_message'] = 'Session expired. Please log in again.';
    self::redirectToLogin();
    }

    // Sanity: l'IP non deve cambiare a metà sessione admin
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SESSION['admin_login_ip']) && $_SESSION['admin_login_ip'] !== $current_ip) {
    self::logout();
    $_SESSION['admin_login_message'] = 'Session invalidated for security reasons.';
    self::redirectToLogin();
    }

    // Rinfresca timestamp di attività
    $_SESSION['admin_last_activity'] = time();

    return (int)$_SESSION['admin_user_id'];
  }

  public static function isAuthenticated(): bool
  {
    return !empty($_SESSION['admin_authenticated']) &&
     !empty($_SESSION['admin_last_activity']) &&
     (time() - (int)$_SESSION['admin_last_activity']) <= self::ADMIN_SESSION_MINUTES * 60;
  }

  public static function logout(): void
  {
    unset(
    $_SESSION['admin_user_id'],
    $_SESSION['admin_username'],
    $_SESSION['admin_authenticated'],
    $_SESSION['admin_last_activity'],
    $_SESSION['admin_login_ip']
    );
  }

  // ---------------------------------------------------------
  // Privati
  // ---------------------------------------------------------

  private static function redirectToLogin(): void
  {
    header('Location: /_admin/index.php');
    exit;
  }

  private static function logFailedAttempt(PDO $pdo, string $email, string $ip): void
  {
    try {
    $pdo->prepare(
      'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
    )->execute([':email' => $email, ':ip' => $ip]);
    } catch (PDOException $e) {
    error_log('[Allonwheel] AdminAuth: log failure: ' . $e->getMessage());
    }
  }

  private static function isIpLockedOut(PDO $pdo, string $ip): bool
  {
    try {
    $stmt = $pdo->prepare(
      'SELECT COUNT(*) FROM login_attempts
      WHERE ip_address = :ip
        AND attempted_at > NOW() - INTERVAL :hours HOUR'
    );
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':hours', self::ADMIN_LOCKOUT_HOURS, PDO::PARAM_INT);
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    return $count >= self::ADMIN_MAX_ATTEMPTS_PER_IP;
    } catch (PDOException $e) {
    // Fail-open: se il check fallisce per problemi DB, non blocco l'admin
    return false;
    }
  }
}
