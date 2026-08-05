<?php
// FIX: file di sola logica — nessun output HTML
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';

// BASE_URL definito in config/bootstrap.php

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php');
  exit;
}
csrf_verify();

$token    = trim($_POST['token']     ?? '');
$new_password = $_POST['new_password']   ?? '';
$confirm_pwd  = $_POST['confirm_password']   ?? '';

// FIX: validazione formato token
if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php?error=invalid_token');
  exit;
}

// FIX: validazione lunghezza password lato server (nell'originale nessuna validazione)
$pwdlength = mb_strlen($new_password);
if ($pwdlength < 8 || $pwdlength > 20) {
  header('Location: ' . BASE_URL . '/01_login/reset_password.php?token=' . urlencode($token) . '&error=pwd_length');
  exit;
}

// FIX: verifica corrispondenza password lato server (nell'originale mancava del tutto)
if ($new_password !== $confirm_pwd) {
  header('Location: ' . BASE_URL . '/01_login/reset_password.php?token=' . urlencode($token) . '&error=pwd_mismatch');
  exit;
}

// Verifica token nel DB
$stmt = $pdo->prepare(
  'SELECT id_user FROM users WHERE reset_token = :token AND reset_expires > NOW() LIMIT 1'
);
$stmt->bindParam(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php?error=expired_token');
  exit;
}

// FIX: usa PASSWORD_BCRYPT coerente con register.php (nell'originale era PASSWORD_DEFAULT)
$hashed = password_hash($new_password, PASSWORD_BCRYPT);

// Aggiorna password e azzera il token (non è più riutilizzabile)
$upd = $pdo->prepare(
  'UPDATE users SET password = :pwd, reset_token = NULL, reset_expires = NULL WHERE id_user = :id'
);
$upd->execute([':pwd' => $hashed, ':id' => $user['id_user']]);

// FIX: redirect alla pagina di conferma invece di echo inline (nell'originale solo echo)
header('Location: ' . BASE_URL . '/01_login/password_reset_ok.php');
exit;
?>
