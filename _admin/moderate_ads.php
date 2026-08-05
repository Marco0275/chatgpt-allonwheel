<?php
// ============================================================
// /_admin/moderate_ads.php
// Pannello moderazione annunci: visualizza tutti gli annunci
// (free e premium) con possibilità di approve/reject per l'admin.
//
// PRE-REQUISITO: eseguire sql/patch_v2_ads_status.sql prima di
// attivare questo file (aggiunge la colonna `status` alle tabelle).
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

// -------------------------------------------------------------------
// Gestione azioni POST (approve / reject)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $ad_id   = (int)($_POST['ad_id']   ?? 0);
  $ad_type = in_array($_POST['ad_type'] ?? '', ['free', 'premium'], true)
               ? $_POST['ad_type'] : '';
  $action  = in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)
               ? $_POST['action'] : '';

  if ($ad_id > 0 && $ad_type !== '' && $action !== '') {
    $table = $ad_type === 'free' ? '02_free_ads' : '03_ads';
    $label = $ad_type === 'free' ? 'Free Ad' : 'Premium Ad';

    if ($action === 'delete') {
      // ----- Cancellazione completa: annuncio + figli + file fisici -----
      $gallery_table = $table . '_gallery';
      $upload_base   = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/' . $table . '/';

      // Immagini di copertina (record principale) e gallery, PRIMA del DELETE
      $stmt = $pdo->prepare("SELECT image_original, image_thumbnail FROM `{$table}` WHERE id_ads = :id LIMIT 1");
      $stmt->execute([':id' => $ad_id]);
      $cover = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

      $stmt = $pdo->prepare("SELECT image_original, image_thumbnail FROM `{$gallery_table}` WHERE id_ads = :id");
      $stmt->execute([':id' => $ad_id]);
      $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

      try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM `{$gallery_table}` WHERE id_ads = :id")->execute([':id' => $ad_id]);
        if ($ad_type === 'premium') {
          // Dettagli tecnici (solo premium)
          $pdo->prepare("DELETE FROM `03_ads_tech_details` WHERE id_ads = :id")->execute([':id' => $ad_id]);
        }
        $pdo->prepare("DELETE FROM `{$table}` WHERE id_ads = :id LIMIT 1")->execute([':id' => $ad_id]);
        $pdo->commit();
      } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[Allonwheel] admin delete ad error (id=' . $ad_id . '): ' . $e->getMessage());
        $_SESSION['admin_error'] = 'Database error while deleting the ad.';
        header('Location: /_admin/moderate_ads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
        exit;
      }

      // Cleanup file DOPO il commit, con protezione path-traversal
      $delete_file = static function (string $dir, ?string $filename): void {
        $filename = basename((string)$filename);
        if ($filename === '' || $filename === 'no_image.jpg') { return; }
        $full = realpath($dir . $filename);
        $base = realpath($dir);
        if ($full === false || $base === false) { return; }
        if (strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) { return; }
        if (is_file($full)) { @unlink($full); }
      };
      $delete_file($upload_base . 'original/',  $cover['image_original']  ?? '');
      $delete_file($upload_base . 'thumbnail/', $cover['image_thumbnail'] ?? '');
      foreach ($gallery_images as $g) {
        $delete_file($upload_base . 'original/',  $g['image_original']  ?? '');
        $delete_file($upload_base . 'thumbnail/', $g['image_thumbnail'] ?? '');
      }

      $_SESSION['admin_success'] = "#{$ad_id} ({$label}) deleted permanently.";
      UserTier::logAdminAction(
        $pdo, $admin_id, 'delete_ad', null,
        $table . ' #' . $ad_id . ' deleted (with gallery' . ($ad_type === 'premium' ? ' + tech details' : '') . ')',
        $_SERVER['REMOTE_ADDR'] ?? ''
      );

    } else {
      // ----- Moderazione (approve / reject) -----
      $new_status = $action === 'approve' ? 'approved' : 'rejected';
      $stmt = $pdo->prepare("UPDATE `{$table}` SET `status` = :s WHERE `id_ads` = :id LIMIT 1");
      $stmt->execute([':s' => $new_status, ':id' => $ad_id]);

      // Matching: se approvato, avvisa i buyer con wanted attive sulla stessa macro.
      if ($action === 'approve') {
        try {
          require_once __DIR__ . '/../libs/wanted_ads.class.php';
          $adRow = $pdo->prepare("SELECT product_macro, vehicle_type, id_user, title FROM `{$table}` WHERE id_ads = :id LIMIT 1");
          $adRow->execute([':id' => $ad_id]);
          $adData = $adRow->fetch(PDO::FETCH_ASSOC);
          if ($adData && !empty($adData['product_macro'])) {
            (new WantedAds($pdo))->notifyBuyers((string)$adData['product_macro'], $table, $ad_id, (int)$adData['id_user'], (string)$adData['title'], (string)($adData['vehicle_type'] ?? ''));
          }
        } catch (Throwable $ex) {
          error_log('[Allonwheel] notifyBuyers on approve: ' . $ex->getMessage());
        }
      }

      $_SESSION['admin_success'] = "#{$ad_id} ({$label}) marked as {$new_status}.";
      UserTier::logAdminAction(
        $pdo, $admin_id, 'moderate_ad', null,
        $table . ' #' . $ad_id . ' marked as ' . $new_status,
        $_SERVER['REMOTE_ADDR'] ?? ''
      );
    }
  }

  header('Location: /_admin/moderate_ads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
  exit;
}

// -------------------------------------------------------------------
// Filtro status
// -------------------------------------------------------------------
$allowed_filters = ['all', 'pending', 'approved', 'rejected'];
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'pending';
$where  = $filter === 'all' ? '' : "WHERE a.`status` = " . $pdo->quote($filter);

// -------------------------------------------------------------------
// Recupero annunci da entrambe le tabelle
// -------------------------------------------------------------------
$ads = [];

foreach ([['02_free_ads', 'free', 'Free Ad'], ['03_ads', 'premium', 'Premium Ad']] as [$table, $type, $label]) {
  $sql = "SELECT a.id_ads, a.title, a.status, a.created_at,
                 u.username, u.email AS user_email
          FROM `{$table}` a
          LEFT JOIN `users` u ON u.id_user = a.id_user
          {$where}
          ORDER BY a.created_at DESC
          LIMIT 200";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $ads[] = array_merge($row, ['ad_type' => $type, 'type_label' => $label, 'table' => $table]);
  }
}

// Ordina per data decrescente (le due tabelle arrivano già ordinate, ma mescolate)
usort($ads, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

// Contatori per filtro rapido
$count_sql = "
  SELECT
    SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected_count,
    COUNT(*) AS total
  FROM (
    SELECT status FROM `02_free_ads`
    UNION ALL
    SELECT status FROM `03_ads`
  ) AS combined
";
$counts = $pdo->query($count_sql)->fetch(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Ad Moderation';
$admin_active = 'ads';
require __DIR__ . '/admin_header.php';
?>

    </div>

    <!-- Riepilogo e filtri -->
    <div class="post_box">
      <h2>Ads overview</h2>
      <p>
        <strong>Pending:</strong> <?php echo (int)$counts['pending_count']; ?> &nbsp;|&nbsp;
        <strong>Approved:</strong> <?php echo (int)$counts['approved_count']; ?> &nbsp;|&nbsp;
        <strong>Rejected:</strong> <?php echo (int)$counts['rejected_count']; ?> &nbsp;|&nbsp;
        <strong>Total:</strong> <?php echo (int)$counts['total']; ?>
      </p>
      <div class="post_meta">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $f => $fl): ?>
        <a href="?filter=<?php echo $f; ?>"<?php echo $filter === $f ? ' class="admin_nav_active"' : ''; ?>>
          <?php echo $fl; ?> (<?php echo $f === 'all' ? (int)$counts['total'] : (int)($counts[$f . '_count'] ?? 0); ?>)
        </a>&nbsp;|&nbsp;
        <?php endforeach; ?>
        <div class="cleaner"></div>
      </div>
    </div>

    <!-- Tabella annunci -->
    <div class="post_box">
      <h2>
        <?php echo ucfirst($filter); ?> ads
        <?php if (empty($ads)): ?>— none found<?php endif; ?>
      </h2>

      <?php if (!empty($ads)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row">
          <th style="text-align: center">ID</th>
          <th style="text-align: center">Type</th>
          <th style="text-align: center">Title</th>
          <th style="text-align: center">User</th>
          <th style="text-align: center">Date</th>
          <th style="text-align: center">Status</th>
          <th style="text-align: center">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad):
          $is_pending  = $ad['status'] === 'pending';
          $is_approved = $ad['status'] === 'approved';
          $is_rejected = $ad['status'] === 'rejected';
          $row_class = $is_pending ? 'admin_row_pending' : ($is_rejected ? 'admin_row_rejected' : '');
        ?>
        <tr<?php echo $row_class ? ' class="' . $row_class . '"' : ''; ?>>
          <td style="text-align: center"><?php echo (int)$ad['id_ads']; ?></td>
          <td style="text-align: center"><?php echo htmlspecialchars($ad['type_label'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td style="text-align: center"><?php echo htmlspecialchars(mb_strtolower(mb_substr($ad['title'], 0, 60)) . (mb_strlen($ad['title']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td style="text-align: center">
            <?php echo htmlspecialchars($ad['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?><br />
            <small><?php echo htmlspecialchars($ad['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td style="text-align: center"><?php echo htmlspecialchars(date('Y-m-d', strtotime($ad['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
          <td style="text-align: center">
            <?php if ($is_approved): ?><strong class="admin_ok">approved</strong>
            <?php elseif ($is_rejected): ?><em class="admin_bad">rejected</em>
            <?php else: ?><strong>pending</strong><?php endif; ?>
          </td>
          <td style="text-align: center">
            <?php if (!$is_approved): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" class="admin_inline_form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="approve" />
              <button type="submit" class="more">Approve</button>
            </form>
            <?php endif; ?>
            <?php if (!$is_rejected): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" >
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" >
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="delete" />
              <button type="submit" class="more">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
 

    <div class="post_box">
      <h3>Notes</h3>
      <p>
        Every approve/reject action is logged in <code>admin_audit_log</code>.<br />
        To activate pre-publication moderation (new ads default to <em>pending</em>), run
        <code>UPDATE `02_free_ads` SET status='pending' WHERE ...</code> and set
        <code>DEFAULT 'pending'</code> in the schema after running the SQL migration.
      </p>
    </div>

  
<?php require __DIR__ . '/admin_footer.php'; ?>
