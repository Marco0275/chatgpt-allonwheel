<?php
// ============================================================
// /_admin/logout.php
// Termina la sessione admin (NON tocca la sessione utente normale).
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

// Audit log prima del logout (così abbiamo l'admin_user_id ancora in sessione)
if (!empty($_SESSION['admin_user_id'])) {
  UserTier::logAdminAction(
    $pdo,
    (int)$_SESSION['admin_user_id'],
    'admin_logout',
    null,
    'Admin logout',
    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
  );
}

AdminAuth::logout();

$_SESSION['admin_login_message'] = 'You have been signed out.';
header('Location: /index.php'); // logout admin -> home pubblica
exit;
