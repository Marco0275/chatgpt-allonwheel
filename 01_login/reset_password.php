<?php
// FIX: tutta la logica PHP PRIMA di qualsiasi output HTML
require_once __DIR__ . '/../config/database.php';

// BASE_URL definito in config/bootstrap.php

$token = trim($_GET['token'] ?? '');

// FIX: validazione formato token (64 caratteri esadecimali) — nell'originale nessuna validazione
if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) {
  header('Location: ' . BASE_URL . '/01_login/forgot_password.php?error=invalid_token');
  exit;
}

// FIX: verifica DB prima di mostrare il form — nell'originale usava die() senza redirect
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

// Recupera eventuali errori dal redirect di save_new_password.php
$error = $_GET['error'] ?? '';
$errorMessages = [
  'pwd_length' => 'Password must be between 8 and 20 characters.',
  'pwd_mismatch' => 'Passwords do not match. Please try again.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Reset password</title>
<meta name="description" content="All on Wheel Ltd - Choose a new password" />
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
    <div id="page_title">Reset password</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
    <p>Choose a new password. It must be between 8 and 20 characters.</p>

    <?php if ($error && isset($errorMessages[$error])): ?>
    <p class="aow-error-text"><?php echo htmlspecialchars($errorMessages[$error], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div id="contact_form">
    <?php require_once __DIR__ . '/../config/csrf.php'; ?>
    <form action="save_new_password.php" method="post" >
      <?php echo csrf_generate(); ?>

      <!-- Token validato lato server, passato come hidden field -->
      <input type="hidden" name="token"
         value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" />

      <div class="float_l">
        <!-- FIX: corretto il typo "New passwordl:" -->
        <label for="new_password">New password:</label>
        <input type="password" id="new_password" name="new_password"
         class="required input_field"
         minlength="8" maxlength="20" required />
      </div>
      <div class="cleaner h10"></div>

      <div class="float_l">
        <!-- FIX: aggiunto campo conferma password (mancava nell'originale) -->
        <label for="confirm_password">Confirm new password:</label>
        <input type="password" id="confirm_password" name="confirm_password"
         class="required input_field"
         minlength="8" maxlength="20" required />
      </div>
      <div class="cleaner h10"></div>

      <p id="pwd_error" class="aow-error-text" style="display:none;">Passwords do not match.</p>

      <div class="cleaner h20"></div>
      <input type="submit" class="submit_btn float_r" name="submit" value="Save new password" />
    </form>
    </div>
  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>

</div>

<script type="text/javascript" nonce="<?php echo AOW_CSP_NONCE; ?>">
function checkPasswords() {
  var p1 = document.getElementById('new_password').value;
  var p2 = document.getElementById('confirm_password').value;
  if (p1 !== p2) {
    document.getElementById('pwd_error').style.display = 'block';
    return false;
  }
  return true;
}
document.addEventListener('DOMContentLoaded',function(){var f=document.querySelector('form[action="save_new_password.php"]');if(f)f.addEventListener('submit',function(e){if(!checkPasswords())e.preventDefault();});});
</script>
</body>
</html>
