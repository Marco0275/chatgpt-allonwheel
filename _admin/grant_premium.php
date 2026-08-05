<?php
// ============================================================
// /_admin/grant_premium.php
// Handler POST per concedere o revocare il tier premium a un utente.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

// Sessione admin obbligatoria
$admin_id = AdminAuth::requireAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /_admin/dashboard.php');
  exit;
}

// CSRF
csrf_verify();

$target_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action  = (string)($_POST['action'] ?? '');
$confirm = isset($_POST['confirm']) ? (string)$_POST['confirm'] : '';

if ($target_id <= 0 || !in_array($action, ['grant', 'revoke'], true)) {
  $_SESSION['admin_error'] = 'Invalid request.';
  header('Location: /_admin/dashboard.php');
  exit;
}

// Sanity: l'admin non può modificare il proprio tier
if ($target_id === $admin_id) {
  $_SESSION['admin_error'] = 'You cannot modify your own admin tier from this panel.';
  header('Location: /_admin/dashboard.php');
  exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$plan = in_array(($_POST['plan'] ?? 'Premium'), ['Premium', 'gold'], true) ? $_POST['plan'] : 'Premium';

if ($action === 'grant') {
  // La checkbox "Eligible" deve essere flaggata
  if ($confirm !== '1') {
    $_SESSION['admin_error'] = 'You must confirm eligibility before granting premium.';
    header('Location: /_admin/dashboard.php');
    exit;
  }

  if ($plan === 'gold') {
    if (UserTier::setGold($pdo, $admin_id, $target_id, $ip)) {
      $_SESSION['admin_success'] = 'GOLD plan granted to user #' . $target_id . '.';
    } else {
      $_SESSION['admin_error'] = 'Could not grant Gold (user may be admin).';
    }
  } elseif (UserTier::grantPremium($pdo, $admin_id, $target_id, $ip)) {
    $_SESSION['admin_success'] = 'Premium (premium) granted to user #' . $target_id . '.';
  } else {
    $_SESSION['admin_error'] = 'Could not grant premium (user may already be premium or admin).';
  }
} else { // revoke
  if (UserTier::revokeGold($pdo, $admin_id, $target_id, $ip)) {
    $_SESSION['admin_success'] = 'GOLD revoked from user #' . $target_id . ' (back to Basic).';
  } elseif (UserTier::revokePremium($pdo, $admin_id, $target_id, $ip)) {
    $_SESSION['admin_success'] = 'Premium revoked for user #' . $target_id . '.';
  } else {
    $_SESSION['admin_error'] = 'Could not revoke premium (user is not currently premium).';
  }
}

header('Location: /_admin/dashboard.php');
exit;
