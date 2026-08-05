<?php
// ============================================================
// /_admin/index.php
// Pagina di login admin (form HTML).
//
// È protetta da:
//  - .htaccess (no directory listing, no indexing)
//  - rate-limit per IP in AdminAuth
//  - whitelist email codificata in AdminAuth::ADMIN_EMAIL
//  - re-autenticazione con password anche se l'utente è già loggato come admin
//
// Layout: stesso template del sito (allonwheel_style.css) ma SENZA
// header.php pubblico (la cartella admin è invisibile, non vogliamo
// che il menu pubblico riveli l'esistenza di /_admin/).
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

// Se già loggato come admin, redirect alla dashboard
if (AdminAuth::isAuthenticated()) {
  header('Location: /_admin/dashboard.php');
  exit;
}

// Recupera flash message se presente (es. "session expired")
$message = $_SESSION['admin_login_message'] ?? '';
unset($_SESSION['admin_login_message']);

csrf_generate();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Restricted Area</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <div id="site_title">
    <h1><a href="/index.php"></a></h1>
    </div>
  </div>

  <div id="content_top">
    <div id="page_title">Restricted Area</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <div class="post_box">

    <h2>Sign in</h2>

    <?php if ($message !== ''): ?>
      <p class="error-msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div id="contact_form">
    <form action="login.php" method="post">

      <?php echo csrf_generate(); ?>

      <div class="form_row">
          <label for="email">Email:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="email" name="email" id="email" class="input_field" required autocomplete="username" /></label>
      </div>

      <div class="form_row">
        <p>&nbsp;</p>
        <p>
          <label for="password">Password:
          <input type="password" name="password" id="password" class="input_field" required autocomplete="current-password" /></label>
        </p>
      </div>
<div class="cleaner h20"></div>
      <div class="form_row">
        <input type="submit" class="submit_btn float_r" name="login" value="Sign in" />
      </div>

    </form>
    </div>

    <p class="required-note"><em>This is a restricted area. Unauthorized access attempts are logged.</em></p>
    </div>

  </div>
	<div id="templatemo_sidebar"><?php include __DIR__ . '/../include_sidebar.php'; ?></div>
  <div class="cleaner"></div>
  <div><?php include('../footer.php'); ?></div>
</div>
</body>
</html>
