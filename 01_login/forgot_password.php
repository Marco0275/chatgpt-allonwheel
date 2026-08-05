<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Forgot password</title>
<meta name="description" content="All on Wheel Ltd - Reset your password" />
<meta name="robots" content="index, follow" />
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
    <div id="page_title">Forgot password</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
    <p>Enter your email address and we will send you a link to reset your password.</p>

    <?php if (isset($_GET['error'])): ?>
    <p class="aow-error-text">
      <?php
      $errors = [
        'invalid'   => 'Please enter a valid email address.',
        'expired_token' => 'The reset link has expired or is invalid. Please request a new one.',
        'invalid_token' => 'Invalid reset link. Please request a new one.',
      ];
      $code = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
      echo $errors[$code] ?? 'An error occurred. Please try again.';
      ?>
    </p>
    <?php endif; ?>

    <div id="contact_form">
    <?php require_once __DIR__ . '/../config/csrf.php'; require_once __DIR__ . '/../libs/antispam.php'; ?>
    <form action="send_reset_link.php" method="post">
      <?php echo csrf_generate(); ?>
      <?php echo aow_spam_fields(); ?>
      <div class="float_l">
        <label for="email">Your email address:</label>
        <!-- FIX: era type="text" senza validazione -->
        <input type="email" id="email" name="email"
         class="required input_field"
         placeholder="you@example.com"
         maxlength="100"
         required />
      </div>
      <div class="cleaner h20"></div>
      <input type="submit" class="submit_btn float_r" name="submit" value="Send reset link" />
    </form>
    </div>
  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>

</div>
</body>
</html>
