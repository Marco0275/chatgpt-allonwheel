<?php
// ============================================================
// /_admin/moderate_blog.php — Moderazione articoli del blog.
// Approve (-> published), Reject (-> rejected), Delete (rimuove anche le
// immagini). Audit via UserTier::logAdminAction (colonne corrette).
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/blog.class.php';

$admin_id = AdminAuth::requireAdminSession();
$blog = new BlogManager($pdo);

// ----- Azioni POST -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $blog_id = (int)($_POST['blog_id'] ?? 0);
    $action  = in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)
                 ? $_POST['action'] : '';

    if ($blog_id > 0 && $action !== '') {
        if ($action === 'delete') {
            $image = $blog->deleteArticle($blog_id);
            // Cleanup file con protezione path-traversal
            if ($image) {
                $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/blog/';
                $clean = static function (string $dir, string $filename): void {
                    $filename = basename($filename);
                    if ($filename === '') { return; }
                    $full = realpath($dir . $filename);
                    $root = realpath($dir);
                    if ($full === false || $root === false) { return; }
                    if (strpos($full, $root . DIRECTORY_SEPARATOR) !== 0) { return; }
                    if (is_file($full)) { @unlink($full); }
                };
                $clean($base . 'original/',  $image);
                $clean($base . 'thumbnail/', $image);
            }
            $_SESSION['admin_success'] = "Article #{$blog_id} deleted permanently.";
            UserTier::logAdminAction($pdo, $admin_id, 'delete_blog', null,
                'blog #' . $blog_id . ' deleted', $_SERVER['REMOTE_ADDR'] ?? '');
        } else {
            $new_status = $action === 'approve' ? 'published' : 'rejected';
            $blog->setStatus($blog_id, $new_status);
            $_SESSION['admin_success'] = "Article #{$blog_id} marked as {$new_status}.";
            UserTier::logAdminAction($pdo, $admin_id, 'moderate_blog', null,
                'blog #' . $blog_id . ' -> ' . $new_status, $_SERVER['REMOTE_ADDR'] ?? '');
        }
    }
    header('Location: /_admin/moderate_blog.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// ----- Filtro -----
$allowed_filters = ['all', 'pending', 'published', 'rejected'];
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'all';
$articles = $blog->listForModeration($filter);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';
$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Blog Moderation';
$admin_active = 'blog';
require __DIR__ . '/admin_header.php';
?>

    </div>

    <div class="post_box">
      <h2>Articles</h2>
      <p>
        <a href="?filter=all">All</a> &nbsp;|&nbsp;
        <a href="?filter=pending">Pending</a> &nbsp;|&nbsp;
        <a href="?filter=published">Published</a> &nbsp;|&nbsp;
        <a href="?filter=rejected">Rejected</a>
      </p>

      <?php if (empty($articles)): ?>
        <p>No articles<?php echo $filter !== 'all' ? ' with status "' . htmlspecialchars($filter) . '"' : ''; ?>.</p>
      <?php else: ?>
      <table border="0" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row">
          <th align="left" style="text-align: center">#</th>
          <th align="left" style="text-align: center">Title</th>
          <th align="left" style="text-align: center">Author</th>
          <th align="left" style="text-align: center">Status</th>
          <th align="left" style="text-align: center">Date</th>
          <th align="left" style="text-align: center">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
        <tr>
          <td style="text-align: center"><?php echo (int)$a['id']; ?></td>
          <td style="text-align: center"><a href="/blog_post.php?id=<?php echo (int)$a['id']; ?>" target="_blank"><?php echo htmlspecialchars($a['title']); ?></a></td>
          <td style="text-align: center"><?php echo htmlspecialchars((string)($a['username'] ?? '—')); ?></td>
          <td style="text-align: center"><strong><?php echo htmlspecialchars($a['status']); ?></strong></td>
          <td style="text-align: center"><?php echo htmlspecialchars(date('j M Y', strtotime((string)$a['created_at']))); ?></td>
          <td style="text-align: center">
            <?php if ($a['status'] !== 'published'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" class="admin_inline_form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="approve" />
              <button type="submit" class="more">Publish</button>
            </form>
            <?php endif; ?>
            <?php if ($a['status'] !== 'rejected'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" >
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" >
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="delete" />
              <button type="submit" class="more">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

  
<?php require __DIR__ . '/admin_footer.php'; ?>
