<?php
// 06_company/06_40_my_leads.php  Area lead del fornitore (punto: claim RFQ).
//
// 20 lug 2026. Il fornitore loggato vede qui i lead (RFQ) ricevuti dalla sua
// azienda e li "prende in carico" (claim). Finora li riceveva solo via email.
//
// Legame: users.id_user -> 06_company.user_id (una azienda per utente) ->
//         quote_request_recipients.company_id -> quote_requests.
// Il claim scrive claimed_at/claimed_by (patch 2026-07-20_lead_claim.sql) e
// serve al cron di riassegnazione per NON scavalcare chi si e' gia' mosso.
//
// Solo classi CSS esistenti (dir. 8). CSRF sul claim. Solo dati reali (dir. 14).
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$user_id  = require_user_logged_in();
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// L'azienda dell'utente (una sola: 06_company.user_id ha UNIQUE).
$company = null;
try {
    $st = $pdo->prepare('SELECT id, ragione_sociale FROM `06_company` WHERE user_id = :u LIMIT 1');
    $st->execute([':u' => $user_id]);
    $company = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('[Allonwheel] my_leads company: ' . $e->getMessage());
}

// POST: prendi in carico un lead (claim). Solo un lead REALMENTE assegnato
// alla PROPRIA azienda puo' essere rivendicato (il WHERE lo garantisce).
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $company) {
    csrf_verify();
    $rid = (int)($_POST['request_id'] ?? 0);
    if ($rid > 0) {
        try {
            $up = $pdo->prepare(
                'UPDATE `quote_request_recipients`
                    SET claimed_at = NOW(), claimed_by = :cid
                  WHERE request_id = :rid AND company_id = :cid AND claimed_at IS NULL'
            );
            $up->execute([':cid' => (int)$company['id'], ':rid' => $rid]);
            $flash = $up->rowCount() > 0
                ? 'Lead taken. The buyer expects your reply by e-mail.'
                : 'This lead was already taken or is no longer available.';
        } catch (Throwable $e) {
            error_log('[Allonwheel] my_leads claim: ' . $e->getMessage());
            $flash = 'Could not update the lead. Please try again.';
        }
    }
    // PRG: evita il re-claim al refresh
    $_SESSION['aow_leads_flash'] = $flash;
    header('Location: 06_40_my_leads.php');
    exit;
}
if (isset($_SESSION['aow_leads_flash'])) {
    $flash = (string)$_SESSION['aow_leads_flash'];
    unset($_SESSION['aow_leads_flash']);
}

// I lead ricevuti dalla propria azienda, piu' recenti prima.
$leads = [];
if ($company) {
    try {
        $q = $pdo->prepare(
            "SELECT q.id, q.buyer_name, q.macro, q.message, q.created_at, q.status,
                    r.claimed_at, r.match_score
               FROM `quote_request_recipients` r
               JOIN `quote_requests` q ON q.id = r.request_id
              WHERE r.company_id = :cid
                AND (r.deliver_at IS NULL OR r.deliver_at <= NOW())
              ORDER BY q.created_at DESC
              LIMIT 100"
        );
        $q->execute([':cid' => (int)$company['id']]);
        $leads = $q->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[Allonwheel] my_leads list: ' . $e->getMessage());
    }
}

// csrf_generate() restituisce l'INPUT HTML gia' pronto, non il token:
// infilarlo dentro un value="" produceva un token corrotto e il submit
// veniva respinto con "Request not allowed". Si legge il token dalla sessione.
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - My leads</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
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
    <div id="page_title">My leads</div>
    <div id="search_box">
      <form action="<?php echo $base_url; ?>browse.php" method="get">
        <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if ($flash !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <?php if (!$company): ?>
      <div class="post_box">
        <h2>My leads</h2>
        <p>This page shows the quotation requests sent to your company.</p>
        <p>You have not registered a company yet. <a href="06_10_register_company.php">Register your company</a> to appear in the supplier directory and start receiving leads.</p>
      </div>
    <?php else: ?>
      <div class="post_box">
        <h2>Leads for <?php echo htmlspecialchars((string)$company['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>Buyer requests matched to your products. Take a lead to let us know you are handling it &mdash; you reply to the buyer directly by e-mail.</p>
      </div>

      <?php if (empty($leads)): ?>
      <div class="post_box">
        <p>No leads yet. Make sure your <a href="06_12_company_products.php">product categories</a> are complete &mdash; the more accurate they are, the better we can match you.</p>
      </div>
      <?php else: ?>
        <?php foreach ($leads as $l): ?>
        <div class="post_box">
          <h3><?php echo htmlspecialchars((string)$l['buyer_name'], ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($l['macro'])): ?><span class="badge badge_type"><?php echo htmlspecialchars((string)$l['macro'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
          </h3>
          <p><em><?php echo htmlspecialchars(date('d M Y', strtotime((string)$l['created_at'])), ENT_QUOTES, 'UTF-8'); ?></em></p>
          <?php if (!empty($l['message'])): ?>
          <p><?php echo nl2br(htmlspecialchars((string)$l['message'], ENT_QUOTES, 'UTF-8')); ?></p>
          <?php endif; ?>
          <div class="post_meta">
            <span class="cat">
              <?php if (!empty($l['claimed_at'])): ?>
                <strong>Taken</strong> on <?php echo htmlspecialchars(date('d M Y', strtotime((string)$l['claimed_at'])), ENT_QUOTES, 'UTF-8'); ?>
              <?php else: ?>
                Awaiting a reply
              <?php endif; ?>
            </span>
            <?php if (empty($l['claimed_at'])): ?>
            <form action="06_40_my_leads.php" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="request_id" value="<?php echo (int)$l['id']; ?>" />
              <input type="submit" class="more" value="Take this lead" />
            </form>
            <?php endif; ?>
          </div>
          <div class="cleaner"></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>

  </div><!-- end templatemo_content -->

  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div>

  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
