<?php
// ============================================================
// /01_login/request_premium.php
// L'utente clicca "Request premium" da my_posts.php; questo handler
// imposta premium_requested=1, premium_requested_at=NOW().
// L'admin vedra' la richiesta nel filtro "Pending" della dashboard.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$user_id = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /01_login/my_posts.php');
  exit;
}

csrf_verify();

$tier = UserTier::getTier($pdo, $user_id);

if ($tier === UserTier::TIER_PREMIUM || $tier === UserTier::TIER_ADMIN) {
  $_SESSION['error_message'] = 'You are already a premium user.';
  header('Location: /01_login/my_posts.php');
  exit;
}

if (UserTier::hasPendingPremiumRequest($pdo, $user_id)) {
  $_SESSION['error_message'] = 'You already have a pending premium request.';
  header('Location: /01_login/my_posts.php');
  exit;
}

if (UserTier::requestPremium($pdo, $user_id)) {
  $_SESSION['success_message'] = 'Premium upgrade requested. An administrator will review your request shortly.';
} else {
  $_SESSION['error_message'] = 'Could not submit premium request. Please try again.';
}

header('Location: /01_login/my_posts.php');
exit;
