<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../config/database.php';

// BASE_URL definito in config/bootstrap.php

$msg = '';
$img = '../images/my_profile/profile_ko.jpg';
$success = false;

if (!empty($_GET['token'])) {
  $token = trim($_GET['token']);

  try {
    $check = $pdo->prepare(
    'SELECT id_user, is_verified, email, username FROM users WHERE email_verification_token = :token LIMIT 1'
    );
    $check->bindParam(':token', $token, PDO::PARAM_STR);
    $check->execute();
    $user = $check->fetch();

    if ($user) {
    if ($user['is_verified'] == 0) {
      $upd = $pdo->prepare(
        'UPDATE users SET is_verified = 1, email_verification_token = NULL WHERE id_user = :id'
      );
      $upd->bindParam(':id', $user['id_user'], PDO::PARAM_INT);

      if ($upd->execute()) {
        $msg   = 'Your account has been verified! You can now log in.';
        $img   = '../images/my_profile/profile_ok.jpg';
        $success = true;

        // M2: email di benvenuto con i 3 passi (best-effort, non blocca la verifica)
        try {
          require_once __DIR__ . '/../config/bootstrap.php';
          require_once __DIR__ . '/../libs/mailer.class.php';
          $aow_wb = rtrim(defined('BASE_URL') ? BASE_URL : 'https://www.allonwheel.com', '/');
          $aow_wu = htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8');
          $aow_wbody = '<html><body style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.5;">'
            . '<p>Welcome to <strong>All on Wheel</strong>' . ($aow_wu !== '' ? ', ' . $aow_wu : '') . '!</p>'
            . '<p>Your account is now active. Here is a quick guide to everything you can do &mdash; pick what fits you.</p>'

            . '<p><strong>Sell a vehicle</strong></p>'
            . '<ul>'
            . '<li><a href="' . $aow_wb . '/02_free_ads/02_00_select_type.php">Post a free ad</a> &mdash; up to 3 photos.</li>'
            . '<li><a href="' . $aow_wb . '/02_free_ads/02_00_select_type.php?listing=prem">Post a premium ad</a> &mdash; up to 20 photos, technical sheet, more visibility. <a href="' . $aow_wb . '/01_login/request_premium.php">Upgrade to premium</a> if needed.</li>'
            . '</ul>'

            . '<p><strong>Buy a vehicle</strong></p>'
            . '<ul>'
            . '<li><a href="' . $aow_wb . '/04_request_offer/04_request_offer.php">Request a quotation</a> &mdash; describe what you need and matching suppliers reply to you.</li>'
            . '<li><a href="' . $aow_wb . '/05_wanted/wanted_post.php">Post a wanted request</a> &mdash; publish what you are looking for; you are notified when a matching vehicle is listed. Track them under <a href="' . $aow_wb . '/05_wanted/wanted_manage.php">My wanted requests</a>.</li>'
            . '</ul>'

            . '<p><strong>Get found as a supplier</strong></p>'
            . '<ul>'
            . '<li><a href="' . $aow_wb . '/06_company/06_10_register_company.php">Register your company</a> in the supplier directory &mdash; certifications and references build trust and bring you RFQ leads.</li>'
            . '</ul>'

            . '<p><strong>Manage your account</strong></p>'
            . '<ul>'
            . '<li><a href="' . $aow_wb . '/01_login/my_posts.php">My posts</a> &mdash; view, edit or delete all your listings in one place.</li>'
            . '<li><a href="' . $aow_wb . '/01_login/seller_dashboard.php">Seller dashboard</a> &mdash; your listings and the leads they generate.</li>'
            . '<li><a href="' . $aow_wb . '/01_login/all_about_me.php">My profile</a> and <a href="' . $aow_wb . '/01_login/modify_user_details.php">Account settings</a> &mdash; keep your details up to date.</li>'
            . '</ul>'

            . '<p>Not sure where to start? <a href="' . $aow_wb . '/01_login/my_posts.php">Open your account</a> and take a look around.</p>'
            . '<p>All on Wheel Ltd &mdash; allonwheel.com</p></body></html>';
          if (!empty($user['email'])) {
            Mailer::send((string)$user['email'], 'Welcome to All on Wheel - your account is active', $aow_wbody, 'info@allonwheel.com');
          }
        } catch (Throwable $e) {
          error_log('[Allonwheel] welcome email error: ' . $e->getMessage());
        }
        // FIX: redirect automatico abilitato (era commentato nell'originale)
        header('Refresh: 4; url=' . BASE_URL . '/01_login/newlogin.php');
      } else {
        $msg = 'An error occurred while verifying your account. Please try again.';
      }
    } else {
      $msg   = 'This account is already verified. You can log in.';
      $img   = '../images/my_profile/profile_ok.jpg';
      $success = true;
    }
    } else {
    $msg = 'Invalid or expired verification link.';
    }
  } catch (PDOException $e) {
    error_log('PDO error in verify.php: ' . $e->getMessage());
    $msg = 'A technical error occurred. Please try again later.';
  }
} else {
  $msg = 'Verification token is missing.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Account verification</title>
<meta name="description" content="All on Wheel - Account verification" />
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<meta name="author" content="All on Wheel Ltd" />

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header"><?php include('../header.php'); ?></div>

  <div id="content_top">
    <div id="page_title">Account verification</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
    <table width="566" border="0" align="center">
    <tr>
      <td width="200">
        <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
         alt="Verification status" width="220" height="150" loading="lazy" decoding="async" />
      </td>
      <td>
        <p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($success): ?>
        <p><small>You will be redirected to login in 4 seconds...</small></p>
        <?php endif; ?>
        <p><a href="newlogin.php" class="more float_r">Go to login</a></p>
      </td>
    </tr>
    </table>
  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>

</div>
</body>
</html>
