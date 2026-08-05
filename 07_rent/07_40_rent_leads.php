<?php
// 07_rent/07_40_rent_leads.php -- Area destinatario: richieste di noleggio ricevute + claim.
// Base: 06_company/06_40_my_leads.php (pattern lead + claim).
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/rent.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$id_user = require_user_logged_in();
$rent = new RentAds($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $rid = (int)($_POST['claim_request_id'] ?? 0);
    if ($rid > 0) { $rent->claimLead($rid, $id_user); $_SESSION['success_message'] = 'Lead claimed.'; }
    header('Location: /07_rent/07_40_rent_leads.php'); exit;
}

$leads = $rent->leadsForUser($id_user);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$labels = function (string $csv) use ($pdo) {
    $out = [];
    foreach (array_filter(explode(',', $csv)) as $slug) { $out[] = VehicleTaxonomy::label($slug, $pdo); }
    return implode(', ', $out);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Rental leads</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header"><?php include('../header.php'); ?></div>
  <div id="content_top"><div id="page_title">Rental leads</div><div class="cleaner"></div></div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Rental requests received</h2>
      <p>Requests from users looking to rent vehicle types matching your published rental listings.</p>
      <?php if (!empty($_SESSION['success_message'])): ?><p class="ok"><strong><?php echo $e($_SESSION['success_message']); unset($_SESSION['success_message']); ?></strong></p><?php endif; ?>
      <?php if (empty($leads)): ?>
        <p>No rental requests yet.</p>
      <?php else: ?>
      <table width="100%" border="0" cellpadding="6" class="tbl_collapse">
        <thead><tr class="thead_row"><th>Date</th><th>Types</th><th>Budget</th><th>Requester</th><th>Status</th><th>&nbsp;</th></tr></thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
        <tr class="row_sep">
          <td><?php echo $e($l['created_at']); ?></td>
          <td><?php echo $e($labels((string)$l['vehicle_types'])); ?><br><small><?php echo $e(mb_substr((string)$l['description'], 0, 120)); ?></small></td>
          <td><?php echo $l['budget'] !== null ? number_format((float)$l['budget'], 2) . ' &euro;/day' : '-'; ?></td>
          <td><?php echo $e($l['requester']); ?><br><small><?php echo $e($l['requester_email']); ?></small></td>
          <td><?php echo $l['claimed_at'] ? 'Claimed' : ($l['emailed_at'] ? 'Emailed' : 'In your leads'); ?></td>
          <td>
            <?php if (!$l['claimed_at']): ?>
            <form method="post" action="07_40_rent_leads.php" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
              <input type="hidden" name="claim_request_id" value="<?php echo (int)$l['request_id']; ?>" />
              <button type="submit" class="more">I take this</button>
            </form>
            <?php else: ?>&#10003;<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div><!-- content -->
  <div id="templatemo_sidebar"><?php include __DIR__ . '/../include_sidebar.php'; ?></div>
  <div class="cleaner"></div>
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
