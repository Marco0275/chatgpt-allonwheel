<?php
// 01_login/account_roles.php — Account settings: ruoli liberi dell'utente
// (Esperto / Project manager / Consulente). Multi-ruolo via tabella user_roles.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/user_roles.class.php';

$uid = current_user_id();
if (!$uid) { header('Location: /01_login/newlogin.php'); exit; }

$ur = new UserRoles($pdo, $user_id);
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $checked = (array)($_POST['role'] ?? []);
    foreach (UserRoles::ROLES as $r) {
        if (isset($checked[$r])) { $ur->addRole((int)$uid, $r); }
        else                     { $ur->removeRole((int)$uid, $r); }
    }
    // Consenso contatti pubblici + telefono (visibili nella directory professionisti)
    $pc    = !empty($_POST['public_contact']) ? 1 : 0;
    $phone = trim($_POST['phone'] ?? '');
    try {
        $pdo->prepare('UPDATE users SET public_contact = :pc, phone = :ph WHERE id_user = :id')
            ->execute([':pc' => $pc, ':ph' => $phone, ':id' => (int)$uid]);
    } catch (Throwable $e) {}
    header('Location: /01_login/account_roles.php?saved=1'); exit;
}

$my = $ur->getRoles((int)$uid);
$urow = ['phone' => '', 'public_contact' => 0];
try {
    $st_u = $pdo->prepare('SELECT phone, public_contact FROM users WHERE id_user = :id LIMIT 1');
    $st_u->execute([':id' => (int)$uid]);
    $urow = $st_u->fetch(PDO::FETCH_ASSOC) ?: $urow;
} catch (Throwable $e) {}
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$has = function(string $r) use ($my): string { return in_array($r, $my, true) ? ' checked' : ''; };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Account roles</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include('../header.php'); ?>
  </div>
  <div id="content_top">
    <div id="page_title">Account roles</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="no_sidebar">
    <div class="post_box">
      <h2>Your roles</h2>
      <?php if (isset($_GET['saved'])): ?><p><em>Saved.</em></p><?php endif; ?>
      <p>Choose the roles you want to take on the platform. You can change them anytime.</p>
      <form method="post" action="account_roles.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>" />
        <p>
          <label><input type="checkbox" name="role[expert]" value="1"<?php echo $has('expert'); ?> />
          <strong>Expert</strong> <span class="badge">Expert</span> &mdash; answer other users' questions on the blog/forum. Your replies show an <strong>Expert</strong> badge.</label>
        </p>
        <p>
          <label><input type="checkbox" name="role[project_manager]" value="1"<?php echo $has('project_manager'); ?> />
          <strong>Project manager</strong> &mdash; be included in the list of project managers sent to companies.</label>
        </p>
        <p>
          <label><input type="checkbox" name="role[consultant]" value="1"<?php echo $has('consultant'); ?> />
          <strong>Consultant</strong> &mdash; be included in the list of consultants sent to companies.</label>
        </p>
        <p>
          <label>Phone (shown on your public contact page if you consent below)<br />
          <input type="text" name="phone" maxlength="30" value="<?php echo htmlspecialchars($urow['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
        </p>
        <p>
          <label><input type="checkbox" name="public_contact" value="1"<?php echo !empty($urow['public_contact']) ? ' checked' : ''; ?> />
          <strong>List me in the public professionals directory</strong> &mdash; other users can see my roles and contact me; my email and phone are shown on my contact page.</label>
        </p>
        <div class="post_meta"><button type="submit" value="Save roles" class="more float_r">Save roles</button></div> 
        <div class="cleaner"></div>
      </form>
    </div>
  </div><!-- end no_sidebar -->
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
