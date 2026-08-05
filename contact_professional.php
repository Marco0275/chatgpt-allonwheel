<?php
// contact_professional.php — Pagina di contatto di un professionista.
// Mostra telefono ed email SOLO se l'utente ha dato il consenso pubblico
// (users.public_contact = 1) ed ha almeno un ruolo speciale. Form di invio
// mail via Mailer. Solo classi del foglio di stile esistente (dir. 8).
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/libs/mailer.class.php';
require_once __DIR__ . '/libs/antispam.php';

$role_labels = [
    'expert'          => 'Expert',
    'project_manager' => 'Project manager',
    'consultant'      => 'Consultant',
];

$id = (int)($_GET['id'] ?? 0);

// Carico il professionista solo se consenziente e con almeno un ruolo
$pro = null;
if ($id > 0) {
    $st = $pdo->prepare(
        "SELECT u.id_user, u.username, u.email, u.phone,
                GROUP_CONCAT(DISTINCT r.role ORDER BY r.role) AS roles
         FROM `users` u
         JOIN `user_roles` r ON r.user_id = u.id_user
         WHERE u.id_user = :id AND u.public_contact = 1
         GROUP BY u.id_user, u.username, u.email, u.phone
         LIMIT 1"
    );
    try {
        $st->execute([':id' => $id]);
        $pro = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { $pro = null; }
}

$sent  = false;
$error = '';

if ($pro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // Honeypot anti-spam (campo nascosto che deve restare vuoto)
    if (!empty($_POST['website']) || aow_is_spam(trim($_POST['message'] ?? ''))) {
        $error = 'Spam detected.';
    } else {
        $from_name = trim($_POST['from_name'] ?? '');
        $from_mail = trim($_POST['from_email'] ?? '');
        $body_txt  = trim($_POST['message'] ?? '');
        if ($from_name === '' || !filter_var($from_mail, FILTER_VALIDATE_EMAIL) || $body_txt === '') {
            $error = 'Please fill in your name, a valid email and a message.';
        } else {
            $subject = 'New message from All on Wheel — ' . $from_name;
            $safe_name = htmlspecialchars($from_name, ENT_QUOTES, 'UTF-8');
            $safe_mail = htmlspecialchars($from_mail, ENT_QUOTES, 'UTF-8');
            $safe_body = nl2br(htmlspecialchars($body_txt, ENT_QUOTES, 'UTF-8'));
            $html = '<p>You received a message via your All on Wheel professional profile.</p>'
                  . '<p><strong>From:</strong> ' . $safe_name . ' &lt;' . $safe_mail . '&gt;</p>'
                  . '<hr /><p>' . $safe_body . '</p>';
            $ok = Mailer::send((string)$pro['email'], $subject, $html, $from_mail, (string)$pro['username']);
            // Copia separata di ogni richiesta a info@ (visibilita' piattaforma).
            Mailer::send('info@allonwheel.com', 'Professional contact (info copy) - ' . $safe_name, $html, $from_mail);
            if ($ok) { $sent = true; }
            else { $error = 'Could not send the message right now. Please try again later.'; }
        }
    }
}

$roles_arr = $pro ? explode(',', (string)$pro['roles']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Contact a professional</title>
<meta name="robots" content="noindex, follow" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.png" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Contact</div>
    <div id="search_box">
      <form action="<?php echo $base_url; ?>browse.php" method="get">
        <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="no_sidebar">
    <?php if (!$pro): ?>
      <div class="post_box">
        <h2>Profile not available</h2>
        <p>This professional is not available for contact. See the
           <a href="professionals.php">professionals directory</a>.</p>
      </div>
    <?php else: ?>
      <div class="post_box">
        <h2><?php echo htmlspecialchars($pro['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="badges">
          <?php foreach ($roles_arr as $r): ?>
            <span class="badge badge_type"><?php echo htmlspecialchars($role_labels[$r] ?? $r, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        </div>
        <ul class="templatemo_list">
          <li><strong>Email:</strong>
            <a href="mailto:<?php echo htmlspecialchars($pro['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pro['email'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php if (trim((string)$pro['phone']) !== ''): ?>
          <li><strong>Phone:</strong> <?php echo htmlspecialchars($pro['phone'], ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="post_box">
        <h3>Send a message</h3>
        <?php if ($sent): ?>
          <p class="flash flash_ok">Your message has been sent.</p>
        <?php else: ?>
          <?php if ($error !== ''): ?><p class="flash flash_err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
          <form method="post" action="contact_professional.php?id=<?php echo (int)$pro['id_user']; ?>">
            <?php echo csrf_generate(); ?>
            <?php echo aow_spam_fields(); ?>
            <!-- honeypot: lasciare vuoto -->
            <div style="display:none;"><label>Website <input type="text" name="website" value="" autocomplete="off" /></label></div>
            <p><label>Your name<br /><input type="text" name="from_name" maxlength="80" required="required" value="<?php echo htmlspecialchars($_POST['from_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label></p>
            <p><label>Your email<br /><input type="text" name="from_email" maxlength="120" required="required" value="<?php echo htmlspecialchars($_POST['from_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label></p>
            <p><label>Message<br /><textarea name="message" rows="6" cols="50" required="required"><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label></p>
            <div class="post_meta"><button type="submit" value="Send message" class="more float_r">Send message</button></div>
            <div class="cleaner"></div>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div><!-- end no_sidebar -->

  <?php include 'footer.php'; ?>
</div>
</body>
</html>
