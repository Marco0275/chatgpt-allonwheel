<?php
// ============================================================
// saved_search_confirm.php — conferma di un alert attivato senza account.
// Il link arriva per email (doppio opt-in): finche' non viene aperto,
// l'alert esiste ma il cron lo ignora, quindi nessuna email parte verso
// un indirizzo che non ha dato conferma.
// Nessun login richiesto: il token e' il consenso.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
if (!function_exists('t')) { require_once __DIR__ . '/config/i18n.php'; }

$token = preg_match('/^[a-f0-9]{32}$/', $_GET['token'] ?? '') ? $_GET['token'] : '';
$ok = false;
$unsub_token = '';

if ($token !== '') {
    try {
        $st = $pdo->prepare('SELECT id, token FROM saved_searches WHERE confirm_token = :t LIMIT 1');
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // Il token di conferma si consuma: un link vale una volta sola.
            $pdo->prepare('UPDATE saved_searches
                              SET confirmed_at = IFNULL(confirmed_at, NOW()), active = 1, confirm_token = NULL
                            WHERE id = :id')
                ->execute([':id' => (int)$row['id']]);
            $unsub_token = (string)$row['token'];
            $ok = true;
        }
    } catch (Throwable $e) {
        error_log('[Allonwheel] saved_search_confirm error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Email alerts</title>
<meta name="robots" content="noindex, nofollow" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
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
    <div id="page_title"><?php te('ss.confirm_title', 'Email alerts'); ?></div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <?php if ($ok): ?>
        <h2><?php te('ss.confirm_ok_h', 'Alert confirmed'); ?></h2>
        <p><?php te('ss.confirm_ok_p', 'Done. We will email you as soon as a listing matching your search is published.'); ?></p>
        <p><a class="more" href="browse.php"><?php te('ss.back_browse', 'Back to the marketplace'); ?></a></p>
        <?php if ($unsub_token !== ''): ?>
        <p class="post_meta"><small><?php te('ss.confirm_unsub', 'Changed your mind?'); ?>
          <a href="saved_search_unsubscribe.php?token=<?php echo htmlspecialchars($unsub_token, ENT_QUOTES, 'UTF-8'); ?>"><?php te('ss.unsub_now', 'Unsubscribe'); ?></a>.</small></p>
        <?php endif; ?>
      <?php else: ?>
        <h2><?php te('ss.confirm_ko_h', 'Link not valid'); ?></h2>
        <p><?php te('ss.confirm_ko_p', 'This confirmation link is not valid or has already been used. If your alert is already confirmed there is nothing else to do.'); ?></p>
        <p><a class="more" href="browse.php"><?php te('ss.back_browse', 'Back to the marketplace'); ?></a></p>
      <?php endif; ?>
    </div>
  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>
