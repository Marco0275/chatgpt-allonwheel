<?php
// professionals.php — Directory pubblica dei professionisti.
// Elenca gli utenti con ruolo speciale (Esperto / Project manager / Consulente)
// che hanno dato il consenso pubblico (users.public_contact = 1).
// Visibile a tutti. Solo classi del foglio di stile esistente (dir. 8).
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';

// Etichette ruolo (UI in inglese, dir. 0)
$role_labels = [
    'expert'          => 'Expert',
    'project_manager' => 'Project manager',
    'consultant'      => 'Consultant',
];

$pros = [];
try {
    $sql = "SELECT u.id_user, u.username,
                   GROUP_CONCAT(DISTINCT r.role ORDER BY r.role) AS roles
            FROM `users` u
            JOIN `user_roles` r ON r.user_id = u.id_user
            WHERE u.public_contact = 1
            GROUP BY u.id_user, u.username
            ORDER BY u.username";
    $pros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pros = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Professionals directory</title>
<meta name="description" content="Directory of experts, project managers and consultants on All on Wheel who are available to be contacted." />
<meta name="robots" content="index, follow" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.png" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php $seo_canonical = 'professionals.php'; include __DIR__ . '/includes/seo_head.php'; ?>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Professionals</div>
    <div id="search_box">
      <form action="<?php echo $base_url; ?>browse.php" method="get">
        <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="no_sidebar">
    <div class="post_box">
      <h2>Experts, project managers &amp; consultants</h2>
      <p>These members offer their professional services on the platform and have
         agreed to be contacted. Pick a profile to send a message; their phone and
         email are shown on their contact page.</p>
    </div>

    <?php if (empty($pros)): ?>
      <div class="post_box">
        <p><em>No professionals are listed yet.</em></p>
      </div>
    <?php else: ?>
      <?php foreach ($pros as $p): ?>
        <?php
          $roles = explode(',', (string)$p['roles']);
          $id    = (int)$p['id_user'];
          $name  = htmlspecialchars($p['username'], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="post_box">
          <h3><?php echo $name; ?></h3>
          <div class="badges">
            <?php foreach ($roles as $r): ?>
              <span class="badge badge_type"><?php echo htmlspecialchars($role_labels[$r] ?? $r, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; ?>
          </div>
          <div class="post_meta">
            <a class="more float_r" href="contact_professional.php?id=<?php echo $id; ?>">Contact</a>
          </div>
          <div class="cleaner"></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- end no_sidebar -->

  <?php include 'footer.php'; ?>
</div>
</body>
</html>
