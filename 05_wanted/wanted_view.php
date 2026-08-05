<?php
// 05_wanted/wanted_view.php — Dettaglio di una richiesta "Wanted" + risposta del venditore.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/wanted_ads.class.php';
require_once __DIR__ . '/../libs/mailer.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';

$id_user = require_user_logged_in();
if (!PlanPolicy::canWanted(UserTier::getTier($pdo, $id_user))) {
    $_SESSION['error_message'] = 'Access to Wanted Requests is a Premium and Gold feature. Upgrade your plan to unlock buyer leads.';
    header('Location: ../01_login/request_premium.php');
    exit;
}

$wanted = new WantedAds($pdo);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = $id > 0 ? $wanted->get($id) : null;
if (!$row) { header('Location: wanted_list.php'); exit; }

$uid    = current_user_id();
$is_owner = ($uid !== null && (int)$row['id_user'] === $uid);
$notice = (string)($_SESSION['aow_wanted_notice'] ?? '');
unset($_SESSION['aow_wanted_notice']);

// Risposta del venditore: invia un'email one-to-one al buyer (richiede login).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'respond') {
    csrf_verify();
    if ($uid === null) { header('Location: ' . BASE_URL . '/01_login/newlogin.php'); exit; }
    if (!$is_owner) {
        $msg = trim((string)($_POST['message'] ?? ''));
        $me = $pdo->prepare('SELECT username, email FROM `users` WHERE id_user = :u LIMIT 1');
        $me->execute([':u' => $uid]);
        $resp = $me->fetch(PDO::FETCH_ASSOC) ?: ['username' => 'A seller', 'email' => ''];
        $body = '<p>Dear ' . htmlspecialchars((string)$row['username']) . ',</p>'
              . '<p>A seller can supply what you are looking for: <strong>'
              . htmlspecialchars((string)$row['title']) . '</strong></p>'
              . ($msg !== '' ? '<p>Message:<br>' . nl2br(htmlspecialchars($msg)) . '</p>' : '')
              . '<p>Seller: ' . htmlspecialchars((string)$resp['username'])
              . (!empty($resp['email']) ? ' (' . htmlspecialchars((string)$resp['email']) . ')' : '') . '</p>'
              . '<p>All on Wheel Ltd</p>';
        try {
            Mailer::send((string)$row['buyer_email'], 'A seller responded to your wanted request',
                         $body, (string)($resp['email'] ?? ''), (string)$row['username']);
            $_SESSION['aow_wanted_notice'] = 'Your response has been sent to the buyer.';
        } catch (Throwable $ex) {
            error_log('[Allonwheel] wanted respond: ' . $ex->getMessage());
            $_SESSION['aow_wanted_notice'] = 'Could not send the response right now.';
        }
    }
    header('Location: wanted_view.php?id=' . $id);
    exit;
}

$macros = $pdo->query('SELECT slug, name FROM `product_macros`')->fetchAll(PDO::FETCH_KEY_PAIR);
$matches = $wanted->adsForMacro((string)$row['macro'], 20);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Wanted request</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Wanted request</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <?php if ($notice !== ''): ?><p><em><?php echo $e($notice); ?></em></p><?php endif; ?>
      <h2><?php echo $e($row['title']); ?></h2>
      <p class="post_meta">
        Category: <strong><?php echo $e($macros[$row['macro']] ?? $row['macro']); ?></strong>
        <?php if (!empty($row['vehicle_type'])): ?> &middot; Type: <?php echo $e($row['vehicle_type']); ?><?php endif; ?>
        <?php if (!empty($row['budget'])): ?> &middot; Budget: &euro;<?php echo $e(number_format((float)$row['budget'], 0, '.', ',')); ?><?php endif; ?>
        <?php if (!empty($row['country_code'])): ?> &middot; <?php echo $e($row['country_code']); ?><?php endif; ?>
        &middot; by <?php echo $e($row['username']); ?>
      </p>
      <p><?php echo nl2br($e($row['description'])); ?></p>

      <?php if ($is_owner): ?>
        <p><a class="more float_r" href="wanted_manage.php">Manage my requests</a></p>
        <div class="cleaner"></div>
        <?php if ($matches): ?>
          <h3>Listings that may match (<?php echo count($matches); ?>)</h3>
          <ul>
          <?php foreach ($matches as $a): ?>
            <li><a href="<?php echo BASE_URL; ?>/<?php echo $a['ad_table'] === '03_ads' ? '03_ads/03_view_ad.php' : '02_free_ads/02_view_ad.php'; ?>?id_ads=<?php echo (int)$a['id_ads']; ?>"><?php echo $e($a['title']); ?></a></li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?php elseif ($uid !== null): ?>
        <h3>Respond to this buyer</h3>
        <form method="post" action="wanted_view.php">
          <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
          <input type="hidden" name="id" value="<?php echo (int)$id; ?>" />
          <input type="hidden" name="action" value="respond" />
          <p><textarea name="message" rows="4" cols="60" placeholder="Tell the buyer what you can offer..."></textarea></p>
          <div class="post_meta"><button type="submit" value="Send response" class="more float_r">Send response</button></div>
          <div class="cleaner"></div>
        </form>
      <?php else: ?>
        <p><a class="more" href="<?php echo BASE_URL; ?>/01_login/newlogin.php">Log in to respond</a></p>
      <?php endif; ?>
      <div class="cleaner h10"></div>
      <div><a href="wanted_list.php">&laquo; Back to all requests</a></div>
    </div>
  </div><!-- end templatemo_content -->

  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div><!-- end templatemo_sidebar -->

  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>