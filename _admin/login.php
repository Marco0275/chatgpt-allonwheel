<?php
// ============================================================
// /_admin/login.php
// Handler POST del form di login admin.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /_admin/index.php');
  exit;
}

// CSRF
csrf_verify();

$email  = (string)($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($email === '' || $password === '') {
  $_SESSION['admin_login_message'] = 'Please fill in all fields.';
  header('Location: /_admin/index.php');
  exit;
}

$result = AdminAuth::attemptLogin($pdo, $email, $password, $ip);

if (!$result['ok']) {
  $_SESSION['admin_login_message'] = $result['message'];
  header('Location: /_admin/index.php');
  exit;
}

header('Location: /_admin/dashboard.php');
exit;
