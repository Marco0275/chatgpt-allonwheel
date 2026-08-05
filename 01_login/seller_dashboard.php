<?php
// 01_login/seller_dashboard.php — Dashboard venditore (lead-centric).
// Aggrega: annunci dell'utente + download documenti (seller_statistics),
// RFQ ricevute (quote_request_recipients -> 06_company.user_id),
// Wanted compatibili (wanted_ads attive sulle macro dei propri annunci).
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// etichette macro
$macro_name = $pdo->query('SELECT slug, name FROM `product_macros`')->fetchAll(PDO::FETCH_KEY_PAIR);

// 1) Annunci dell'utente + download + n. documenti
$st = $pdo->prepare(
    "SELECT x.id_ads, x.ad_table, x.title, x.product_macro, x.status,
            COALESCE(s.pdf_downloads, 0) AS downloads,
            (SELECT COUNT(*) FROM `ads_documents` d WHERE d.id_ads = x.id_ads AND d.ad_table = x.ad_table) AS docs
       FROM (
            SELECT id_ads, '03_ads' AS ad_table, title, product_macro, status FROM `03_ads`      WHERE id_user = :u1
            UNION ALL
            SELECT id_ads, '02_free_ads' AS ad_table, title, product_macro, status FROM `02_free_ads` WHERE id_user = :u2
       ) x
       LEFT JOIN `seller_statistics` s ON s.id_ads = x.id_ads AND s.ad_table = x.ad_table
      ORDER BY downloads DESC, x.id_ads DESC"
);
$st->execute([':u1' => $id_user, ':u2' => $id_user]);
$listings = $st->fetchAll(PDO::FETCH_ASSOC);

$tot_downloads = 0;
$my_macros = [];
foreach ($listings as $l) {
    $tot_downloads += (int)$l['downloads'];
    if (!empty($l['product_macro'])) { $my_macros[$l['product_macro']] = true; }
}
$my_macros = array_keys($my_macros);

// 2) RFQ ricevute dalle aziende dell'utente (lead distribuiti)
$leads = [];
$lst = $pdo->prepare(
    "SELECT q.id, q.buyer_name, q.contact_name, q.company_name, q.macro, q.vehicle_type,
            q.message, q.status, q.created_at, q.country_code
       FROM `quote_request_recipients` r
       JOIN `06_company` c ON c.id = r.company_id
       JOIN `quote_requests` q ON q.id = r.request_id
      WHERE c.user_id = :u
        AND (r.deliver_at IS NULL OR r.deliver_at <= NOW())
      ORDER BY q.created_at DESC
      LIMIT 50"
);
$lst->execute([':u' => $id_user]);
$leads = $lst->fetchAll(PDO::FETCH_ASSOC);
$open_leads = 0;
foreach ($leads as $ld) {
    if (!in_array(strtolower((string)$ld['status']), ['won', 'lost', 'closed'], true)) { $open_leads++; }
}

// 3) Wanted compatibili (attive, sulle macro dei propri annunci, escluse le proprie)
$wanted = [];
if ($my_macros) {
    $in = implode(',', array_fill(0, count($my_macros), '?'));
    $wq = $pdo->prepare(
        "SELECT w.id, w.title, w.macro, w.created_at, u.username
           FROM `wanted_ads` w JOIN `users` u ON u.id_user = w.id_user
          WHERE w.status = 'active' AND w.id_user <> ? AND w.macro IN ($in)
          ORDER BY w.created_at DESC LIMIT 50"
    );
    $wq->execute(array_merge([$id_user], $my_macros));
    $wanted = $wq->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Azienda registrata dall'utente (1:1). Se presente, la mostriamo in dashboard.
$cstmt = $pdo->prepare('SELECT id, ragione_sociale, citta, provincia, partita_iva, attiva FROM `06_company` WHERE user_id = :u ORDER BY id LIMIT 1');
$cstmt->execute([':u' => $id_user]);
$company = $cstmt->fetch(PDO::FETCH_ASSOC) ?: null;

// Contatore social (quota per piano; le pubblicazioni le inserisce l'IA esterna)
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';
$aow_tier = UserTier::getTier($pdo, $id_user);
$aow_social_quota = PlanPolicy::socialQuota($aow_tier);
$aow_social_used = 0;
try {
    $ss = $pdo->prepare("SELECT COUNT(*) FROM `social_posts` WHERE user_id = :u AND YEAR(posted_at) = YEAR(CURDATE())");
    $ss->execute([':u' => $id_user]);
    $aow_social_used = (int)$ss->fetchColumn();
} catch (PDOException $e) { /* tabella non ancora migrata */ }

// Wanted riservato a Premium/Gold: per i Free azzero l'elenco (conteggio e sezione)
if (!PlanPolicy::canWanted($aow_tier)) { $wanted = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Seller dashboard</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>

<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include('../header.php'); ?>
  </div>

  <div id="content_top">
    <div id="page_title">Seller dashboard</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

 <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Overview</h2>
      <p>
        <strong><?php echo count($listings); ?></strong> listing<?php echo count($listings) === 1 ? '' : 's'; ?>
        &nbsp;|&nbsp; <strong><?php echo (int)$tot_downloads; ?></strong> document download<?php echo $tot_downloads === 1 ? '' : 's'; ?>
        &nbsp;|&nbsp; <strong><?php echo (int)$open_leads; ?></strong> open RFQ<?php echo $open_leads === 1 ? '' : 's'; ?>
        &nbsp;|&nbsp; <strong><?php echo count($wanted); ?></strong> matching wanted request<?php echo count($wanted) === 1 ? '' : 's'; ?>
        &nbsp;|&nbsp; <strong><?php echo (int)$aow_social_used; ?><?php echo $aow_social_quota > 0 ? '/' . (int)$aow_social_quota : ''; ?></strong> social post<?php echo $aow_social_used === 1 ? '' : 's'; ?> this year
      </p>
    </div>

    <div class="post_box">
      <h2>My company</h2>
      <?php if ($company): ?>
        <p>
          <strong><?php echo $e($company['ragione_sociale']); ?></strong>
          <?php if (!empty($company['citta'])): ?> &middot; <?php echo $e($company['citta']); ?><?php if (!empty($company['provincia'])): ?> (<?php echo $e($company['provincia']); ?>)<?php endif; ?><?php endif; ?>
          <?php if (!empty($company['partita_iva'])): ?> &middot; VAT <?php echo $e($company['partita_iva']); ?><?php endif; ?>
          <?php if ((int)$company['attiva'] === 1): ?><span class="badge badge_approved">Active</span><?php else: ?><span class="badge badge_pending">Hidden</span><?php endif; ?>
        </p>
        <p>
          <a class="more" href="../06_company/06_02_view_company.php?id=<?php echo (int)$company['id']; ?>">View public page</a>
          &nbsp;|&nbsp;&nbsp;<a class="more" href="../06_company/06_20_modify_company.php">Edit</a>
          &nbsp;|&nbsp;&nbsp;<a class="more" href="../06_company/06_40_my_leads.php">Leads</a>
        </p>
      <?php else: ?>
        <p><em>You haven't registered a company yet. Register one to appear in the suppliers directory and receive RFQ broadcasts.</em></p>
        <p><a class="more" href="../06_company/06_10_register_company.php">Register a company</a></p>
      <?php endif; ?>
    </div>

    <div class="post_box">
      <h2>Open RFQ (received leads)</h2>
      <?php if ($leads): ?>
        <?php foreach ($leads as $ld): $closed = in_array(strtolower((string)$ld['status']), ['won','lost','closed'], true); ?>
          <div class="post_box">
            <h3><?php echo $e($ld['buyer_name']); ?>
              <?php if (!empty($ld['company_name'])): ?>&mdash; <?php echo $e($ld['company_name']); ?><?php endif; ?>
              <em>(<?php echo $e($ld['status']); ?>)</em></h3>
            <p class="post_meta">
              <?php echo $e(date('d/m/Y H:i', strtotime((string)$ld['created_at']))); ?>
              <?php if (!empty($ld['macro'])): ?> &middot; <?php echo $e($macro_name[$ld['macro']] ?? $ld['macro']); ?><?php endif; ?>
              <?php if (!empty($ld['vehicle_type'])): ?> &middot; <?php echo $e($ld['vehicle_type']); ?><?php endif; ?>
              <?php if (!empty($ld['country_code'])): ?> &middot; <?php echo $e($ld['country_code']); ?><?php endif; ?>
            </p>
            <p><?php echo $e(mb_substr((string)$ld['message'], 0, 200)); ?><?php echo mb_strlen((string)$ld['message']) > 200 ? '&hellip;' : ''; ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p><em>No leads yet.<?php echo $company ? ' RFQ broadcasts will appear here when a buyer requests a quotation.' : ' Register a company to receive RFQ broadcasts, or wait for buyers to request a quotation.'; ?></em></p>
      <?php endif; ?>
    </div>

    <div class="post_box">
      <h2>Matching wanted requests</h2>
      <?php if ($wanted): ?>
        <ul>
          <?php foreach ($wanted as $w): ?>
            <li><a href="../05_wanted/wanted_view.php?id=<?php echo (int)$w['id']; ?>"><?php echo $e($w['title']); ?></a>
                &mdash; <?php echo $e($macro_name[$w['macro']] ?? $w['macro']); ?>
                <em>(<?php echo $e(date('d/m/Y', strtotime((string)$w['created_at']))); ?>)</em></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p><em>No active wanted requests match your listings right now.</em></p>
      <?php endif; ?>
    </div>

    <div class="post_box">
      <h2>My listings</h2>
      <span style="text-align: left"></span>
      <span style="text-align: left">
      <?php if ($listings): ?>
      </span>
      <ul>
        <span style="text-align: left">
        <?php foreach ($listings as $l): ?>
        </span>
        <li>
          <?php
                $aow_st = strtolower(trim((string)($l['status'] ?? '')));
                $aow_stmap = ['approved' => ['badge_approved', 'Approved'], 'pending' => ['badge_pending', 'Pending review'], 'rejected' => ['badge_rejected', 'Rejected']];
                [$aow_stcls, $aow_stlbl] = $aow_stmap[$aow_st] ?? ['badge', ($aow_st !== '' ? ucfirst($aow_st) : '-')];
                $aow_prem = ($l['ad_table'] === '03_ads');
              ?>
          <a href="../<?php echo $l['ad_table'] === '03_ads' ? '03_ads/03_view_ad.php' : '02_free_ads/02_view_ad.php'; ?>?id_ads=<?php echo (int)$l['id_ads']; ?>"><?php echo $e($l['title']); ?></a>
          <?php // Solo Premium (16 lug 2026): niente badge "Free". L'assenza
                    // del badge dice gia' che l'annuncio e' standard. ?>
          <?php if ($aow_prem): ?>
            <span class="badge badge_premium">Premium</span>
          <?php endif; ?>
          <span class="badge <?php echo $aow_stcls; ?>"><?php echo $e($aow_stlbl); ?></span></span>
          <span style="text-align: left">&mdash; <strong><?php echo (int)$l['downloads']; ?></strong> download<?php echo (int)$l['downloads'] === 1 ? '' : 's'; ?>,
          <?php echo (int)$l['docs']; ?> document<?php echo (int)$l['docs'] === 1 ? '' : 's'; ?>
          <?php if ($l['ad_table'] === '03_ads'): ?>
            &middot; <a href="../03_ads/03_documents.php?id_ads=<?php echo (int)$l['id_ads']; ?>&amp;ad_table=03_ads">manage</a>
          <?php else: ?>
            &middot; <a href="../03_ads/03_documents.php?id_ads=<?php echo (int)$l['id_ads']; ?>&amp;ad_table=02_free_ads">manage</a>
          <?php endif; ?>
          </span></li>
        <span style="text-align: left">
        <?php endforeach; ?>
      </ul>
      <span style="text-align: left">
      <?php else: ?>
      </span>
      <p><em>You have no listings yet.</em></p>
      <span style="text-align: left">
      <?php endif; ?>
    </span></div>

  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <?php include('../footer.php'); ?>

</div><!-- end templatemo_wrapper -->
</body>
</html>
