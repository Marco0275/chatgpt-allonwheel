<?php
// ============================================================
// /_admin/leads.php
// Pannello lead: elenca le richieste di offerta (quote_requests)
// con conteggio destinatari e permette di aggiornare lo status.
// Drill-down (?id=N) mostra i fornitori destinatari con esito invio.
//
// PRE-REQUISITO: eseguire sql/Changelog/quote_requests.sql e
// sql/Changelog/quote_request_consent.sql prima di attivare questo file.
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/product_macro.class.php';

$admin_id = AdminAuth::requireAdminSession();

$allowed_status = ['new', 'distributed', 'quoted', 'won', 'lost'];

// -------------------------------------------------------------------
// POST: aggiornamento status del lead
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $lead_id    = (int)($_POST['lead_id'] ?? 0);
    $new_status = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : '';

    if ($lead_id > 0 && $new_status !== '') {
        $stmt = $pdo->prepare("UPDATE `quote_requests` SET `status` = :s WHERE `id` = :id LIMIT 1");
        $stmt->execute([':s' => $new_status, ':id' => $lead_id]);

        $_SESSION['admin_success'] = "Lead #{$lead_id} updated to {$new_status}.";
        UserTier::logAdminAction(
            $pdo, $admin_id, 'update_lead_status', $lead_id,
            'quote_request #' . $lead_id . ' -> ' . $new_status,
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }

    header('Location: /_admin/leads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// -------------------------------------------------------------------
// Filtro status + conteggi
// -------------------------------------------------------------------
$allowed_filters = array_merge(['all'], $allowed_status);
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'all';

$counts = ['all' => 0];
foreach ($allowed_status as $s) { $counts[$s] = 0; }
try {
    $rows = $pdo->query("SELECT status, COUNT(*) AS c FROM `quote_requests` GROUP BY status")
                ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $st = (string)$r['status'];
        if (isset($counts[$st])) { $counts[$st] = (int)$r['c']; }
        $counts['all'] += (int)$r['c'];
    }
} catch (PDOException $e) {
    error_log('[Allonwheel] leads counts error: ' . $e->getMessage());
}

// -------------------------------------------------------------------
// Elenco lead (con conteggio destinatari)
// -------------------------------------------------------------------
$where = $filter === 'all' ? '' : 'WHERE q.status = :st';
$sql = "
  SELECT q.id, q.buyer_name, q.buyer_email, q.macro, q.status, q.created_at,
         q.consent_given,
         COUNT(r.id) AS rec_total,
         COALESCE(SUM(r.sent_ok), 0) AS rec_sent
  FROM `quote_requests` q
  LEFT JOIN `quote_request_recipients` r ON r.request_id = q.id
  {$where}
  GROUP BY q.id
  ORDER BY q.created_at DESC, q.id DESC
";
$leads = [];
try {
    $stmt = $pdo->prepare($sql);
    if ($filter !== 'all') { $stmt->bindValue(':st', $filter); }
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Allonwheel] leads list error: ' . $e->getMessage());
}

// -------------------------------------------------------------------
// Drill-down: destinatari di un singolo lead
// -------------------------------------------------------------------
$detail = null;
$detail_recipients = [];
$detail_id = (int)($_GET['id'] ?? 0);
if ($detail_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `quote_requests` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $detail_id]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($detail) {
            $stmt = $pdo->prepare(
                "SELECT r.sent_ok, c.ragione_sociale, c.email
                 FROM `quote_request_recipients` r
                 LEFT JOIN `06_company` c ON c.id = r.company_id
                 WHERE r.request_id = :id
                 ORDER BY c.ragione_sociale"
            );
            $stmt->execute([':id' => $detail_id]);
            $detail_recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('[Allonwheel] lead detail error: ' . $e->getMessage());
    }
}

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Leads';
$admin_active = 'leads';
require __DIR__ . '/admin_header.php';
?>

    </div>

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="admin_ok"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="admin_bad"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <!-- Riepilogo e filtri -->
    <div class="post_box">
      <h2>Leads overview</h2>
      <div class="post_meta">
        <?php foreach ($allowed_filters as $f): ?>
        <a href="?filter=<?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filter === $f ? ' class="admin_nav_active"' : ''; ?>>
          <?php echo ucfirst($f); ?> (<?php echo (int)$counts[$f]; ?>)
        </a>&nbsp;|&nbsp;
        <?php endforeach; ?>
        <div class="cleaner"></div>
      </div>
    </div>

    <!-- Tabella lead -->
    <div class="post_box">
      <h2><?php echo ucfirst($filter); ?> leads<?php if (empty($leads)): ?> — none found<?php endif; ?></h2>

      <?php if (!empty($leads)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row">
          <th>ID</th>
          <th>Date</th>
          <th>Buyer</th>
          <th>Macro</th>
          <th>Sent/Tot</th>
          <th>Status</th>
          <th>Update</th>
          <th>Details</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $lead): ?>
        <tr>
          <td>#<?php echo (int)$lead['id']; ?></td>
          <td><?php
            // Data + eta' del lead: evidenzia i lead che aspettano da troppo (SLA visivo).
            $aow_ts  = strtotime((string)$lead['created_at']);
            $aow_age = $aow_ts ? (int)floor((time() - $aow_ts) / 86400) : null;
            echo htmlspecialchars((string)$lead['created_at'], ENT_QUOTES, 'UTF-8');
            if ($aow_age !== null) {
                $aow_lbl = $aow_age === 0 ? 'today' : ($aow_age === 1 ? '1 day ago' : $aow_age . ' days ago');
                $aow_warn = ($aow_age >= 3 && $lead['status'] === 'new');
                // dir. 8: nessuno stile inline — riuso la classe badge esistente per il warning
                if ($aow_warn) {
                    echo '<br /><span class="badge badge_rejected">&#9888; ' . $aow_lbl . '</span>';
                } else {
                    echo '<br /><small>' . $aow_lbl . '</small>';
                }
            }
          ?></td>
          <td>
            <?php echo htmlspecialchars((string)$lead['buyer_name'], ENT_QUOTES, 'UTF-8'); ?><br />
            <small><?php echo htmlspecialchars((string)$lead['buyer_email'], ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td><?php echo $lead['macro'] ? htmlspecialchars(ProductMacro::label((string)$lead['macro'], $pdo), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
          <td><?php echo (int)$lead['rec_sent']; ?> / <?php echo (int)$lead['rec_total']; ?></td>
          <td><?php echo htmlspecialchars((string)$lead['status'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <form method="post" action="leads.php<?php echo $filter !== 'all' ? '?filter=' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') : ''; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>" />
              <select name="status">
                <?php foreach ($allowed_status as $s): ?>
                <option value="<?php echo $s; ?>"<?php echo $lead['status'] === $s ? ' selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="submit" class="more" value="Update" />
            </form>
          </td>
          <td><a href="?id=<?php echo (int)$lead['id']; ?><?php echo $filter !== 'all' ? '&amp;filter=' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') : ''; ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php if ($detail): ?>
    <!-- Dettaglio destinatari -->
    <div class="post_box">
      <h2>Lead #<?php echo (int)$detail['id']; ?> — recipients</h2>
      <p>
        <strong>Buyer:</strong>
        <?php echo htmlspecialchars((string)$detail['buyer_name'], ENT_QUOTES, 'UTF-8'); ?>
        (<?php echo htmlspecialchars((string)$detail['buyer_email'], ENT_QUOTES, 'UTF-8'); ?>)<br />
        <strong>Macro:</strong>
        <?php echo $detail['macro'] ? htmlspecialchars(ProductMacro::label((string)$detail['macro'], $pdo), ENT_QUOTES, 'UTF-8') : '—'; ?>
        &nbsp;|&nbsp;
        <strong>Consent:</strong> <?php echo !empty($detail['consent_given']) ? 'yes' : 'no'; ?>
        &nbsp;|&nbsp;
        <strong>Status:</strong> <?php echo htmlspecialchars((string)$detail['status'], ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php $msg = trim((string)($detail['message'] ?? '')); if ($msg !== ''): ?>
      <p><strong>Message:</strong><br /><?php echo nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')); ?></p>
      <?php endif; ?>

      <?php if (!empty($detail_recipients)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row"><th>Company</th><th>Email</th><th>Sent</th></tr>
        </thead>
        <tbody>
        <?php foreach ($detail_recipients as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars((string)($r['ragione_sociale'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars((string)($r['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo !empty($r['sent_ok']) ? 'yes' : 'no'; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p>No recipients recorded for this lead.</p>
      <?php endif; ?>
    <?php endif; ?>

   <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p class="admin_footer_note">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
	  <div class="cleaner h20"></div>
  </div>
</div>
</body>
</html>
