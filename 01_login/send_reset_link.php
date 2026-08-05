<?php
// FIX: file di sola logica — nessun output HTML
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/antispam.php';

// BASE_URL definito in config/bootstrap.php

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php');
  exit;
}
csrf_verify();
if (aow_is_spam()) { header('Location: ' . BASE_URL . '/01_login/forgot_password.php'); exit; }
// P3.19: rate-limit leggero per sessione.
$aow_now = time();
$_SESSION['rl_reset'] = array_values(array_filter($_SESSION['rl_reset'] ?? [], function ($t) use ($aow_now) { return $t > $aow_now - 1800; }));
if (count($_SESSION['rl_reset']) >= 5) { header('Location: ' . BASE_URL . '/01_login/forgot_password.php'); exit; }
$_SESSION['rl_reset'][] = $aow_now;

// FIX: validazione email (mancava del tutto nell'originale)
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php?error=invalid');
  exit;
}

// Cerca l'utente — la logica di invio avviene solo se esiste,
// ma il redirect è SEMPRE lo stesso per evitare user enumeration
$stmt = $pdo->prepare('SELECT id_user FROM users WHERE email = :email LIMIT 1');
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
  // FIX: token da 32 byte = 64 caratteri hex (era 16 byte / 32 hex nell'originale)
  $token = bin2hex(random_bytes(32));
  $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

  $upd = $pdo->prepare(
    'UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id_user = :id'
  );
  $upd->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id_user']]);

  $link  = BASE_URL . '/01_login/reset_password.php?token=' . urlencode($token);
  $subject = 'Reset your All on Wheel password';
  $body  = "You requested a password reset for your All on Wheel account.\n\n"
     . "Click the link below to set a new password (valid for 1 hour):\n\n"
     . $link . "\n\n"
     . "If you did not request this, you can safely ignore this email.\n";

  $headers  = "From: noreply@allonwheel.com\r\n";
  $headers .= "Reply-To: noreply@allonwheel.com\r\n";
  $headers .= 'X-Mailer: PHP/' . phpversion();

  mail($email, $subject, $body, $headers);
}

// FIX: redirect identico in ogni caso — non si rivela se l'email è registrata (user enumeration)
header('Location: ' . BASE_URL . '/01_login/reset_link_sent.php');
exit;
?>
