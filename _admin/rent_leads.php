<?php
// ============================================================
// /_admin/rent_leads.php
// Pannello richieste di NOLEGGIO (07_rent_requests) trattate come le RFQ:
// elenco con conteggio destinatari, aggiornamento status, drill-down (?id=N).
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$admin_id = AdminAuth::requireAdminSession();
$allowed_status = ['new', 'distributed', 'quoted', 'won', 'lost'];

// ---- POST: aggiornamento status ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $new_status = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : '';
    if ($lead_id > 0 && $new_status !== '') {
        $stmt = $pdo->prepare("UPDATE `07_rent_requests` SET `status` = :s WHERE `id` = :id LIMIT 1");
        $stmt->execute([':s' => $new_status, ':id' => $lead_id]);
        $_SESSION['admin_success'] = "Rental request #{$lead_id} updated to {$new_status}.";
        UserTier::logAdminAction($pdo, $admin_id, 'update_rent_lead_status', $lead_id,
            '07_rent_requests #' . $lead_id . ' -> ' . $new_status, $_SERVER['REMOTE_ADDR'] ?? '');
    }
    header('Location: /_admin/rent_leads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// ---- Filtro status + conteggi ----
$allowed_filters = array_merge(['all'], $allowed_status);
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'all';
$counts = ['all' => 0]; foreach ($allowed_status as $s) { $counts[$s] = 0; }
try {
    foreach ($pdo->query("SELECT status, COUNT(*) c FROM `07_rent_requests` GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $st = (string)$r['status']; if (isset($counts[$st])) { $counts[$st] = (int)$r['c']; } $counts['all'] += (int)$r['c'];
    }
} catch (PDOException $e) { error_log('[Allonwheel] rent_leads counts: ' . $e->getMessage()); }

// ---- Elenco richieste (con conteggio destinatari) ----
$where = $filter === 'all' ? '' : 'WHERE q.status = :st';
$sql = "SELECT q.id, u.username AS buyer_name, u.email AS buyer_email, q.vehicle_types,
               q.status, q.created_at,
               COUNT(r.id) AS rec_total,
               COALESCE(SUM(CASE WHEN r.emailed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS rec_sent
        FROM `07_rent_requests` q
        JOIN `users` u ON u.id_user = q.id_user
        LEFT JOIN `07_rent_request_recipients` r ON r.request_id = q.id
        {$where} GROUP BY q.id ORDER BY q.created_at DESC, q.id DESC";
$leads = [];
try { $stmt = $pdo->prepare($sql); if ($filter !== 'all') { $stmt->bindValue(':st', $filter); } $stmt->execute();
      $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('[Allonwheel] rent_leads list: ' . $e->getMessage()); }

// ---- Drill-down destinatari ----
$detail = null; $detail_recipients = []; $detail_id = (int)($_GET['id'] ?? 0);
if ($detail_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT q.*, u.username, u.email AS requester_email FROM `07_rent_requests` q JOIN `users` u ON u.id_user = q.id_user WHERE q.id = :id LIMIT 1");
        $stmt->execute([':id' => $detail_id]); $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($detail) {
            $stmt = $pdo->prepare("SELECT r.emailed_at, r.claimed_at, r.tier, u.username, u.email, c.ragione_sociale
                FROM `07_rent_request_recipients` r JOIN `users` u ON u.id_user = r.id_user
                LEFT JOIN `06_company` c ON c.id = r.company_id WHERE r.request_id = :id ORDER BY r.rank_pos");
            $stmt->execute([':id' => $detail_id]); $detail_recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { error_log('[Allonwheel] rent_lead detail: ' . $e->getMessage()); }
}

$labels = function (string $csv) use ($pdo) {
    $out = []; foreach (array_filter(explode(',', $csv)) as $slug) { $out[] = VehicleTaxonomy::label($slug, $pdo); }
    return implode(', ', $out);
};
csrf_generate(); $csrf_token = $_SESSION['csrf_token'] ?? '';
$success = $_SESSION['admin_success'] ?? ''; $error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$admin_title = 'Rental leads'; $admin_active = 'rentleads';
require __DIR__ . '/admin_header.php';
?>

    </div>
    <?php if ($success !== ''): ?><div class="post_box"><p class="admin_ok"><?php echo $e($success); ?></p></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="post_box"><p class="admin_bad"><?php echo $e($error); ?></p></div><?php endif; ?>

    <div class="post_box">
      <h2>Rental requests overview</h2>
      <div class="post_meta">
        <?php foreach ($allowed_filters as $f): ?>
        <a href="?filter=<?php echo $e($f); ?>"<?php echo $filter === $f ? ' class="admin_nav_active"' : ''; ?>><?php echo ucfirst($f); ?> (<?php echo (int)$counts[$f]; ?>)</a>&nbsp;|&nbsp;
        <?php endforeach; ?>
        <div class="cleaner"></div>
      </div>
    </div>

    <div class="post_box">
      <h2><?php echo ucfirst($filter); ?> rental requests<?php if (empty($leads)): ?> -- none found<?php endif; ?></h2>
      <?php if (!empty($leads)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead><tr class="admin_thead_row"><th>ID</th><th>Date</th><th>Requester</th><th>Vehicle types</th><th>Sent/Tot</th><th>Status</th><th>Update</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($leads as $lead): ?>
        <tr>
          <td>#<?php echo (int)$lead['id']; ?></td>
          <td><?php echo $e($lead['created_at']); ?></td>
          <td><?php echo $e($lead['buyer_name']); ?><br /><small><?php echo $e($lead['buyer_email']); ?></small></td>
          <td><?php echo $e($labels((string)$lead['vehicle_types'])); ?></td>
          <td><?php echo (int)$lead['rec_sent']; ?> / <?php echo (int)$lead['rec_total']; ?></td>
          <td><?php echo $e($lead['status']); ?></td>
          <td>
            <form method="post" action="rent_leads.php<?php echo $filter !== 'all' ? '?filter=' . $e($filter) : ''; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo $e($csrf_token); ?>" />
              <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>" />
              <select name="status"><?php foreach ($allowed_status as $s): ?><option value="<?php echo $s; ?>"<?php echo $lead['status'] === $s ? ' selected' : ''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select>
              <input type="submit" class="more" value="Update" />
            </form>
          </td>
          <td><a href="?id=<?php echo (int)$lead['id']; ?><?php echo $filter !== 'all' ? '&amp;filter=' . $e($filter) : ''; ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php if ($detail): ?>
    <div class="post_box">
      <h2>Rental request #<?php echo (int)$detail['id']; ?> -- recipients</h2>
      <p><strong>Requester:</strong> <?php echo $e($detail['username']); ?> (<?php echo $e($detail['requester_email']); ?>)<br />
      <strong>Vehicle types:</strong> <?php echo $e($labels((string)$detail['vehicle_types'])); ?>&nbsp;|&nbsp;<strong>Status:</strong> <?php echo $e($detail['status']); ?></p>
      <?php $msg = trim((string)($detail['description'] ?? '')); if ($msg !== ''): ?><p><strong>Message:</strong><br /><?php echo nl2br($e($msg)); ?></p><?php endif; ?>
      <?php if (!empty($detail_recipients)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead><tr class="admin_thead_row"><th>Recipient</th><th>Email</th><th>Tier</th><th>Emailed</th><th>Claimed</th></tr></thead>
        <tbody>
        <?php foreach ($detail_recipients as $r): ?>
        <tr><td><?php echo $e($r['ragione_sociale'] ?: $r['username']); ?></td><td><?php echo $e($r['email']); ?></td><td><?php echo $e($r['tier']); ?></td><td><?php echo $r['emailed_at'] ? 'yes' : 'no'; ?></td><td><?php echo $r['claimed_at'] ? 'yes' : 'no'; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p>No recipients recorded.</p><?php endif; ?>
    </div>
    <?php endif; ?>

   <div class="cleaner"></div>
  <div id="templatemo_footer"><p class="admin_footer_note">&copy; All on Wheel Ltd. &mdash; Restricted area</p><div class="cleaner h20"></div></div>
</div>
</body>
</html>
