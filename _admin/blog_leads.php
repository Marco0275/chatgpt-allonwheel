<?php
// ============================================================
// /_admin/blog_leads.php
// Pannello lead B2B raccolti dai form a fine articolo (tabella `blog_leads`).
// Elenca i lead con articolo di provenienza, intento e categoria; consente di
// aggiornare lo status. Accesso: solo AdminAuth::requireAdminSession().
//
// PRE-REQUISITO: sql/Changelog/2026-08-02_blog_expert_hub.sql (tabella blog_leads).
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();
$allowed_status = ['new', 'contacted', 'qualified', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $lead_id    = (int)($_POST['lead_id'] ?? 0);
    $new_status = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : '';
    if ($lead_id > 0 && $new_status !== '') {
        $pdo->prepare("UPDATE `blog_leads` SET `status` = :s WHERE `id` = :id LIMIT 1")
            ->execute([':s' => $new_status, ':id' => $lead_id]);
        $_SESSION['admin_success'] = "Blog lead #{$lead_id} updated to {$new_status}.";
        UserTier::logAdminAction($pdo, $admin_id, 'update_blog_lead_status', $lead_id,
            'blog_lead #' . $lead_id . ' -> ' . $new_status, $_SERVER['REMOTE_ADDR'] ?? '');
    }
    header('Location: blog_leads.php');
    exit;
}

$filter = in_array($_GET['status'] ?? '', $allowed_status, true) ? $_GET['status'] : '';
$sql = "SELECT l.*, b.title AS blog_title
        FROM `blog_leads` l
        LEFT JOIN `blog` b ON b.id = l.id_blog";
if ($filter !== '') { $sql .= " WHERE l.status = " . $pdo->quote($filter); }
$sql .= " ORDER BY l.created_at DESC";
try { $leads = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
catch (Throwable $e) { $leads = []; }

$flash = $_SESSION['admin_success'] ?? '';
unset($_SESSION['admin_success']);

$admin_active = 'blogleads';
$admin_title  = 'Blog leads';
include __DIR__ . '/admin_header.php';
?>
<div id="main"></div><div id="no_sidebar">
  <div class="post_box">
    <h2>Blog leads<?php echo $filter !== '' ? ' — ' . htmlspecialchars($filter) : ''; ?></h2>
    <?php if ($flash !== ''): ?><p class="flash_ok"><strong><?php echo htmlspecialchars($flash); ?></strong></p><?php endif; ?>
    <p class="muted_small">
      Filter:
      <a href="blog_leads.php">all</a>
      <?php foreach ($allowed_status as $s): ?>
        | <a href="blog_leads.php?status=<?php echo $s; ?>"><?php echo $s; ?></a>
      <?php endforeach; ?>
    </p>

    <?php if (empty($leads)): ?>
      <p>No leads yet.</p>
    <?php else: ?>
    <table class="tbl_collapse admin_table">
      <tr class="thead_row">
        <td>Date</td><td>Name</td><td>Email</td><td>Company</td>
        <td>Intent</td><td>Category</td><td>Article</td><td>Status</td><td>Update</td>
      </tr>
      <?php foreach ($leads as $l): ?>
      <tr>
        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime((string)$l['created_at']))); ?></td>
        <td><?php echo htmlspecialchars((string)$l['name']); ?></td>
        <td><a href="mailto:<?php echo htmlspecialchars((string)$l['email'], ENT_QUOTES); ?>"><?php echo htmlspecialchars((string)$l['email']); ?></a></td>
        <td><?php echo htmlspecialchars((string)($l['company'] ?? '')); ?></td>
        <td><?php echo htmlspecialchars((string)($l['intent'] ?? '')); ?></td>
        <td><?php echo htmlspecialchars((string)($l['category'] ?? '')); ?></td>
        <td><?php echo $l['id_blog']
              ? '<a href="../blog_post.php?id=' . (int)$l['id_blog'] . '">' . htmlspecialchars((string)($l['blog_title'] ?? ('#' . (int)$l['id_blog']))) . '</a>'
              : '&mdash;'; ?></td>
        <td><span class="badge badge_type"><?php echo htmlspecialchars((string)$l['status']); ?></span></td>
        <td>
          <form method="post" action="blog_leads.php">
            <?php echo csrf_generate(); ?>
            <input type="hidden" name="lead_id" value="<?php echo (int)$l['id']; ?>" />
            <select name="status">
              <?php foreach ($allowed_status as $s): ?>
              <option value="<?php echo $s; ?>"<?php echo $s === $l['status'] ? ' selected' : ''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="more">Save</button>
          </form>
        </td>
      </tr>
      <?php if (trim((string)($l['message'] ?? '')) !== ''): ?>
      <tr><td colspan="9" class="row_sep"><em><?php echo nl2br(htmlspecialchars((string)$l['message'])); ?></em></td></tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/admin_footer.php'; ?>
