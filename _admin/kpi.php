<?php
// ============================================================
// /_admin/kpi.php — M5: dashboard KPI del lancio (piano v1.1).
// Solo dati reali dal DB (dir. 14): registrazioni, annunci, RFQ,
// funnel per status, famiglie, sorgenti lead, ricerche salvate,
// SLA lead aperti. Baseline "settimana 0" per il piano dei 30 giorni.
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

AdminAuth::requireAdminSession();

$page_title = 'KPI dashboard';

// ---------- helper: query -> array [chiave => valore] con fallback ----------
function aow_kv(PDO $pdo, string $sql, array $p = []): array {
    try { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_KEY_PAIR) ?: []; }
    catch (Throwable $e) { return []; }
}
function aow_one(PDO $pdo, string $sql, array $p = []) {
    try { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); }
    catch (Throwable $e) { return null; }
}

// ---------- 1) Serie settimanali (ultime 8 settimane ISO) ----------
$W = 8;
$wk_users = aow_kv($pdo, "SELECT YEARWEEK(created_at,3) wk, COUNT(*) FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$W} WEEK) GROUP BY wk");
$wk_ads = aow_kv($pdo, "SELECT wk, SUM(n) FROM (
      SELECT YEARWEEK(created_at,3) wk, COUNT(*) n FROM `02_free_ads`
        WHERE status='approved' AND created_at >= DATE_SUB(NOW(), INTERVAL {$W} WEEK) GROUP BY wk
      UNION ALL
      SELECT YEARWEEK(created_at,3) wk, COUNT(*) n FROM `03_ads`
        WHERE status='approved' AND created_at >= DATE_SUB(NOW(), INTERVAL {$W} WEEK) GROUP BY wk
    ) t GROUP BY wk");
$wk_rfq = aow_kv($pdo, "SELECT YEARWEEK(created_at,3) wk, COUNT(*) FROM `quote_requests`
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$W} WEEK) GROUP BY wk");
$wk_ss = aow_kv($pdo, "SELECT YEARWEEK(created_at,3) wk, COUNT(*) FROM `saved_searches`
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$W} WEEK) GROUP BY wk");

// elenco settimane (dalla piu' recente)
$weeks = [];
for ($i = 0; $i < $W; $i++) {
    $ts = strtotime("-{$i} week");
    $weeks[] = (int)date('o', $ts) * 100 + (int)date('W', $ts); // YEARWEEK(,3) = oW
}

// ---------- 2) Funnel RFQ per status (totale + 30 giorni) ----------
$funnel_all = aow_kv($pdo, "SELECT status, COUNT(*) FROM `quote_requests` GROUP BY status");
$funnel_30  = aow_kv($pdo, "SELECT status, COUNT(*) FROM `quote_requests`
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY status");
$statuses = ['new', 'distributed', 'quoted', 'won', 'lost'];
$tot_all = array_sum($funnel_all);
// tasso risposta fornitori = lead arrivati a quoted/won/lost sul distribuito
$den = (int)($funnel_all['distributed'] ?? 0) + (int)($funnel_all['quoted'] ?? 0)
     + (int)($funnel_all['won'] ?? 0) + (int)($funnel_all['lost'] ?? 0);
$num = (int)($funnel_all['quoted'] ?? 0) + (int)($funnel_all['won'] ?? 0) + (int)($funnel_all['lost'] ?? 0);
$resp_rate = $den > 0 ? round(100 * $num / $den) : null;

// SLA: lead ancora new/distributed da 3+ giorni (da sollecitare)
$sla_late = (int)aow_one($pdo, "SELECT COUNT(*) FROM `quote_requests`
    WHERE status IN ('new','distributed') AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)");

// ---------- 3) RFQ per famiglia ----------
$by_macro = aow_kv($pdo, "SELECT COALESCE(NULLIF(macro,''),'(none)') m, COUNT(*) FROM `quote_requests`
    GROUP BY m ORDER BY COUNT(*) DESC LIMIT 8");

// ---------- 4) Sorgenti lead (richiede patch M3 source_page) ----------
$by_source = aow_kv($pdo, "SELECT COALESCE(NULLIF(source_page,''),'(direct/unknown)') s, COUNT(*)
    FROM `quote_requests` GROUP BY s ORDER BY COUNT(*) DESC LIMIT 8");
$source_ok = $by_source !== [] || ((int)aow_one($pdo, "SELECT COUNT(*) FROM `quote_requests`")) === 0;

// ---------- 5) Totali di contesto ----------
$tot_users     = (int)aow_one($pdo, "SELECT COUNT(*) FROM users");
$tot_verified  = (int)aow_one($pdo, "SELECT COUNT(*) FROM users WHERE is_verified = 1");
$tot_companies = (int)aow_one($pdo, "SELECT COUNT(*) FROM `06_company` WHERE attiva = 1");
$tot_founding  = (int)aow_one($pdo, "SELECT COUNT(*) FROM `06_company` WHERE founding_partner = 1");
$tot_ads       = (int)aow_one($pdo, "SELECT (SELECT COUNT(*) FROM `02_free_ads` WHERE status='approved')
                                   + (SELECT COUNT(*) FROM `03_ads` WHERE status='approved')");
$tot_ss_active = (int)aow_one($pdo, "SELECT COUNT(*) FROM `saved_searches` WHERE active = 1");

require __DIR__ . '/admin_header.php';
?>

    </div>

    <div class="post_box">
      <h2>Launch KPI &mdash; overview</h2>
      <p>
        Registered users: <strong><?php echo $tot_users; ?></strong> (verified: <?php echo $tot_verified; ?>) &middot;
        Active suppliers: <strong><?php echo $tot_companies; ?></strong> (founding: <?php echo $tot_founding; ?>) &middot;
        Approved listings: <strong><?php echo $tot_ads; ?></strong> &middot;
        Active saved searches: <strong><?php echo $tot_ss_active; ?></strong> &middot;
        Total RFQ: <strong><?php echo (int)$tot_all; ?></strong>
      </p>
      <?php if ($sla_late > 0): ?>
      <p><span class="badge badge_rejected">&#9888; <?php echo $sla_late; ?> open leads waiting 3+ days</span>
         &mdash; <a href="leads.php?filter=new">follow up in Leads</a></p>
      <?php endif; ?>
    </div>

    <div class="post_box">
      <h2>Weekly trend (last <?php echo $W; ?> ISO weeks)</h2>
      <table width="100%" border="1" cellpadding="4" cellspacing="0" class="admin_table">
        <tr><th style="text-align: center">Week</th><th style="text-align: center">New users</th><th style="text-align: center">Approved listings</th><th style="text-align: center">RFQ</th><th style="text-align: center">Saved searches</th></tr>
        <?php foreach ($weeks as $wk): ?>
        <tr>
          <td style="text-align: center"><?php echo (int)($wk / 100), ' w', str_pad($wk % 100, 2, '0', STR_PAD_LEFT); ?></td>
          <td style="text-align: center"><?php echo (int)($wk_users[$wk] ?? 0); ?></td>
          <td style="text-align: center"><?php echo (int)($wk_ads[$wk] ?? 0); ?></td>
          <td style="text-align: center"><?php echo (int)($wk_rfq[$wk] ?? 0); ?></td>
          <td style="text-align: center"><?php echo (int)($wk_ss[$wk] ?? 0); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <p><small>Week 0 of the launch plan = the first full row before day 1: this is the baseline.</small></p>
    </div>

    <div class="post_box">
      <h2>RFQ funnel</h2>
      <table width="100%" border="1" cellpadding="4" cellspacing="0" class="admin_table">
        <tr><th style="text-align: center">Status</th><th style="text-align: center">All time</th><th style="text-align: center">Last 30 days</th></tr>
        <?php foreach ($statuses as $s): ?>
        <tr>
          <td style="text-align: center"><?php echo ucfirst($s); ?></td>
          <td style="text-align: center"><?php echo (int)($funnel_all[$s] ?? 0); ?></td>
          <td style="text-align: center"><?php echo (int)($funnel_30[$s] ?? 0); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <p>Supplier response rate (quoted+won+lost over distributed):
        <strong><?php echo $resp_rate === null ? 'n/a (no distributed leads yet)' : $resp_rate . '%'; ?></strong>
        <br /><small>Response TIME is not tracked yet (no status timestamp column): the plan's
        "typical reply in X days" stays neutral copy until this baseline exists.</small></p>
    </div>

    <div class="post_box">
      <h2>RFQ by family</h2>
      <?php if ($by_macro): ?>
      <table width="100%" border="1" cellpadding="4" cellspacing="0" class="admin_table">
        <tr><th style="text-align: center">Family</th><th style="text-align: center">RFQ</th></tr>
        <?php foreach ($by_macro as $m => $n): ?>
        <tr><td style="text-align: center"><?php echo htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8'); ?></td><td style="text-align: center"><?php echo (int)$n; ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?><p>No RFQ yet.</p><?php endif; ?>
    </div>

    <div class="post_box">
      <h2>RFQ sources</h2>
      <?php if (!$source_ok): ?>
      <p><span class="badge badge_pending">Run sql/Changelog/2026-07-06_rfq_source_page.sql to enable source attribution.</span></p>
      <?php elseif ($by_source): ?>
      <table width="100%" border="1" cellpadding="4" cellspacing="0" class="admin_table">
        <tr><th style="text-align: center">Source page (referer)</th><th style="text-align: center">RFQ</th></tr>
        <?php foreach ($by_source as $s => $n): ?>
        <tr><td style="text-align: center"><?php echo htmlspecialchars(mb_substr((string)$s, 0, 90), ENT_QUOTES, 'UTF-8'); ?></td><td style="text-align: center"><?php echo (int)$n; ?></td></tr>
        <?php endforeach; ?>
      </table>
      <p><small>UTM convention for every outbound link (emails, LinkedIn, ads):
        <code>?utm_source=&lt;canale&gt;&amp;utm_medium=&lt;mezzo&gt;&amp;utm_campaign=launch30</code>
        &mdash; the referer lands here.</small></p>
      <?php else: ?><p>No RFQ yet.</p><?php endif; ?>
    </div>

   <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p class="admin_footer_note">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
	  <div class="cleaner h20"></div>
  </div>
</div>
</body>
</html>
