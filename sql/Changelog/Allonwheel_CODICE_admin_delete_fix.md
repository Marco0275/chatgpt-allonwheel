# Allonwheel — FIX audit log admin (colonne corrette) + Delete
*Rev. 2 giu 2026 — risolve il Fatal `Unknown column 'admin_id'` e usa il logger centralizzato.*

## Causa del Fatal error

La tabella reale `admin_audit_log` ha le colonne **`admin_user_id`, `action`, `target_user_id`, `details`, `ip_address`**.
Sia `manage_companies.php` sia `moderate_ads.php` (codice preesistente) inserivano invece su `admin_id, action, target_type, target_id, detail, ip` → colonne inesistenti. Era un bug latente: activate/deactivate e approve/reject avrebbero dato lo stesso errore; la nuova azione **Delete** l'ha fatto emergere.

## Fix applicato

- Tutti gli `INSERT INTO admin_audit_log` grezzi (delete **e** activate/deactivate/approve/reject) sostituiti con il logger centralizzato **`UserTier::logAdminAction($pdo, $admin_user_id, $action, $target_user_id, $details, $ip)`**, che usa le colonne corrette e — bonus — **non fa fallire l'azione** se il log va in errore.
- Aggiunto `require_once .../libs/user_tier.class.php` in entrambe le pagine.
- `target_user_id` impostato a `null` (la colonna è pensata per utenti); l'entità (es. `06_company #42`, `03_ads #15`) e l'esito finiscono in `details`.

Nessun'altra parte del progetto inserisce in `admin_audit_log` se non `libs/user_tier.class.php` (verificato).

---

## `_admin/moderate_ads.php`

```php
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
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin — Ad Moderation</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <div id="site_title"><h1><a href="/index.php"></a></h1></div>
  </div>

  <div id="content_top">
    <div id="page_title">Ad Moderation</div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" style="width:100%;">

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <!-- Navigazione admin -->
    <div class="post_box">
      <div class="post_meta">
        <a href="dashboard.php">Users</a>
        &nbsp;|&nbsp;
        <strong>Ad moderation</strong>
        &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a>
        &nbsp;
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
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
        <a href="?filter=<?php echo $f; ?>"<?php echo $filter === $f ? ' style="font-weight:bold;"' : ''; ?>>
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
      <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
        <tr style="background:#1D275A; color:#fff;">
          <th>ID</th>
          <th>Type</th>
          <th>Title</th>
          <th>User</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad):
          $is_pending  = $ad['status'] === 'pending';
          $is_approved = $ad['status'] === 'approved';
          $is_rejected = $ad['status'] === 'rejected';
          $row_bg = $is_pending ? '#FFF8DC' : ($is_rejected ? '#FDE8E8' : '');
        ?>
        <tr<?php echo $row_bg ? ' style="background:' . $row_bg . ';"' : ''; ?>>
          <td><?php echo (int)$ad['id_ads']; ?></td>
          <td><?php echo htmlspecialchars($ad['type_label'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars(mb_strtolower(mb_substr($ad['title'], 0, 60)) . (mb_strlen($ad['title']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php echo htmlspecialchars($ad['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?><br />
            <small><?php echo htmlspecialchars($ad['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($ad['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if ($is_approved): ?><strong style="color:#1D275A;">approved</strong>
            <?php elseif ($is_rejected): ?><em style="color:#c00;">rejected</em>
            <?php else: ?><strong>pending</strong><?php endif; ?>
          </td>
          <td>
            <?php if (!$is_approved): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" style="display:inline; margin:0;">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="approve" />
              <button type="submit" class="more">Approve</button>
            </form>
            <?php endif; ?>
            <?php if (!$is_rejected): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  style="display:inline; margin:0;"
                  data-confirm="Reject ad #<?php echo (int)$ad['id_ads']; ?>?">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  style="display:inline; margin:0;"
                  data-confirm="Permanently delete ad #<?php echo (int)$ad['id_ads']; ?> and all its images? This cannot be undone.">
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
    </div>

    <div class="post_box">
      <h3>Notes</h3>
      <p>
        Every approve/reject action is logged in <code>admin_audit_log</code>.<br />
        To activate pre-publication moderation (new ads default to <em>pending</em>), run
        <code>UPDATE `02_free_ads` SET status='pending' WHERE ...</code> and set
        <code>DEFAULT 'pending'</code> in the schema after running the SQL migration.
      </p>
    </div>

  </div>

  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. — Restricted area</p>
  </div>

</div>
</body>
</html>
```

## `_admin/manage_companies.php`

```php
<?php
// ============================================================
// /_admin/manage_companies.php
// Pannello gestione aziende: elenco completo con possibilità di
// attivare/disattivare la visibilità pubblica (campo `attiva`).
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// Nessuna migrazione DB necessaria: il campo `attiva` esiste già
// nella tabella `06_company`.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

// -------------------------------------------------------------------
// Gestione azioni POST (activate / deactivate)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $company_id = (int)($_POST['company_id'] ?? 0);
    $action = in_array($_POST['action'] ?? '', ['activate', 'deactivate', 'delete'], true)
        ? $_POST['action']
        : '';

    if ($company_id > 0 && $action !== '') {

        if ($action === 'delete') {
            // ----- Cancellazione completa azienda + tabelle figlie + file -----
            $upload_base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/';

            // Logo + immagini gallery PRIMA del DELETE
            $stmt = $pdo->prepare("SELECT logo FROM `06_company` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $company_id]);
            $company_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmt = $pdo->prepare("SELECT immagine FROM `06_company_gallery` WHERE company_id = :id");
            $stmt->execute([':id' => $company_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            try {
                $pdo->beginTransaction();
                // Tabelle figlie (associazioni e gallery)
                $pdo->prepare("DELETE FROM `06_company_gallery` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products_special` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_services` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company` WHERE id = :id LIMIT 1")->execute([':id' => $company_id]);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[Allonwheel] admin delete company error (id=' . $company_id . '): ' . $e->getMessage());
                $_SESSION['admin_error'] = 'Database error while deleting the company.';
                header('Location: /_admin/manage_companies.php');
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
            $logo = (string)($company_row['logo'] ?? '');
            foreach (['original/', 'thumbnail/'] as $sub) {
                $delete_file($upload_base . $sub, $logo);
                foreach ($gallery_images as $g) {
                    $delete_file($upload_base . $sub, $g['immagine'] ?? '');
                }
            }
            // Compatibilita' vecchi upload flat (pre-refactoring)
            $delete_file($upload_base, $logo);
            foreach ($gallery_images as $g) { $delete_file($upload_base, $g['immagine'] ?? ''); }

            $_SESSION['admin_success'] = "Company #{$company_id} deleted permanently.";
            UserTier::logAdminAction(
                $pdo, $admin_id, 'delete_company', null,
                '06_company #' . $company_id . ' deleted (gallery, products, special, services)',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

        } else {
            // ----- Attiva / disattiva visibilita' -----
            $new_attiva = $action === 'activate' ? 1 : 0;

            $stmt = $pdo->prepare("
                UPDATE `06_company`
                SET `attiva` = :a
                WHERE `id` = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':a'  => $new_attiva,
                ':id' => $company_id
            ]);

            $label = $action === 'activate' ? 'activated' : 'deactivated';

            $_SESSION['admin_success'] = "Company #{$company_id} {$label}.";

            UserTier::logAdminAction(
                $pdo, $admin_id, 'manage_company', null,
                '06_company #' . $company_id . ' ' . $label,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
        }
    }

    header('Location: /_admin/manage_companies.php');
    exit;
}

// -------------------------------------------------------------------
// Filtro attiva
// -------------------------------------------------------------------
$filter = in_array($_GET['filter'] ?? '', ['all', 'active', 'inactive'], true)
    ? $_GET['filter']
    : 'all';

$where = '';

switch ($filter) {
    case 'active':
        $where = 'WHERE c.attiva = 1';
        break;

    case 'inactive':
        $where = 'WHERE c.attiva = 0';
        break;

    default:
        $where = '';
        break;
}

// -------------------------------------------------------------------
// Recupero aziende
// -------------------------------------------------------------------
$sql = "
    SELECT
        c.id,
        c.ragione_sociale,
        c.citta,
        c.provincia,
        c.nazione,
        c.email,
        c.attiva,
        c.data_inserimento,
        c.logo,
        u.username,
        u.email AS user_email,

        (
            SELECT COUNT(*)
            FROM `06_company_gallery` g
            WHERE g.company_id = c.id
        ) AS gallery_count,

        (
            SELECT COUNT(*)
            FROM `06_company_products` p
            WHERE p.company_id = c.id
        ) AS products_count

    FROM `06_company` c

    LEFT JOIN `users` u
        ON u.id_user = c.user_id

    {$where}

    ORDER BY c.data_inserimento DESC
    LIMIT 500
";

$companies = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query("
    SELECT
        SUM(attiva = 1) AS active_count,
        SUM(attiva = 0) AS inactive_count,
        COUNT(*)        AS total
    FROM `06_company`
")->fetch(PDO::FETCH_ASSOC);

csrf_generate();

$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error'] ?? '';

unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin — Manage Companies</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>

<body>

<div id="templatemo_wrapper">

    <div id="templatemo_header">
        <div id="site_title">
            <h1><a href="/index.php"></a></h1>
        </div>
    </div>

    <div id="content_top">
        <div id="page_title">Manage Companies</div>
        <div class="cleaner"></div>
    </div>

    <div id="templatemo_content" style="width:100%;">

        <?php if ($success !== ''): ?>
            <div class="post_box">
                <p class="done">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="post_box">
                <p class="error-msg">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Navigazione admin -->
        <div class="post_box">
            <div class="post_meta">
                <a href="dashboard.php">Users</a>
                &nbsp;|&nbsp;

                <a href="moderate_ads.php">Ad moderation</a>
                &nbsp;|&nbsp;

                <strong>Companies</strong>
                &nbsp;

                <a href="logout.php" class="more float_r">Sign out</a>

                <div class="cleaner"></div>
            </div>
        </div>

        <!-- Riepilogo e filtri -->
        <div class="post_box">

            <h2>Companies overview</h2>

            <p>
                <strong>Active:</strong>
                <?php echo (int)$counts['active_count']; ?>

                &nbsp;|&nbsp;

                <strong>Inactive:</strong>
                <?php echo (int)$counts['inactive_count']; ?>

                &nbsp;|&nbsp;

                <strong>Total:</strong>
                <?php echo (int)$counts['total']; ?>
            </p>

            <div class="post_meta">

                <a href="?filter=all"<?php echo $filter === 'all' ? ' style="font-weight:bold;"' : ''; ?>>
                    All (<?php echo (int)$counts['total']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=active"<?php echo $filter === 'active' ? ' style="font-weight:bold;"' : ''; ?>>
                    Active (<?php echo (int)$counts['active_count']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=inactive"<?php echo $filter === 'inactive' ? ' style="font-weight:bold;"' : ''; ?>>
                    Inactive (<?php echo (int)$counts['inactive_count']; ?>)
                </a>

                <div class="cleaner"></div>
            </div>
        </div>

        <!-- Tabella aziende -->
        <div class="post_box">

            <h2><?php echo ucfirst($filter); ?> companies</h2>

            <?php if (empty($companies)): ?>

                <p><em>No companies found.</em></p>

            <?php else: ?>

                <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">

                    <thead>
                        <tr style="background:#1D275A; color:#fff;">
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Owner</th>
                            <th>Registered</th>
                            <th>Content</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($companies as $co): ?>

                        <?php $is_active = (int)$co['attiva'] === 1; ?>

                        <tr<?php echo !$is_active ? ' style="background:#FDE8E8;"' : ''; ?>>

                            <td><?php echo (int)$co['id']; ?></td>

                            <td>

                                <?php if (!empty($co['logo'])): ?>

                                    <a class="pirobox" href="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($co['ragione_sociale'] ?? 'Company logo', ENT_QUOTES, 'UTF-8'); ?>">
                                        <img
                                            src="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt=""
                                            style="max-width:60px; max-height:45px;"
                                        />
                                    </a>

                                <?php else: ?>

                                    <em style="color:#999;">—</em>

                                <?php endif; ?>

                            </td>

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars($co['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['email'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>

                                <?php echo htmlspecialchars($co['citta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                                <?php if (!empty($co['provincia'])): ?>
                                    (<?php echo htmlspecialchars($co['provincia'], ENT_QUOTES, 'UTF-8'); ?>)
                                <?php endif; ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['nazione'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>

                                <?php echo htmlspecialchars($co['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    date('Y-m-d', strtotime($co['data_inserimento'])),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>

                            <td>

                                <?php echo (int)$co['products_count']; ?> products

                                <br />

                                <small>
                                    <?php echo (int)$co['gallery_count']; ?> gallery images
                                </small>

                            </td>

                            <td>

                                <?php if ($is_active): ?>

                                    <strong style="color:#1D275A;">active</strong>

                                <?php else: ?>

                                    <em style="color:#c00;">inactive</em>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a href="/06_company/06_02_view_company.php?id=<?php echo (int)$co['id']; ?>" target="_blank">
                                    View
                                </a>

                                &nbsp;

                                <?php if ($is_active): ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        style="display:inline; margin:0;"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="deactivate"
                                        />

                                        <button type="submit" class="more">
                                            Deactivate
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        style="display:inline; margin:0;"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="activate"
                                        />

                                        <button type="submit" class="more">
                                            Activate
                                        </button>

                                    </form>

                                <?php endif; ?>

                                &nbsp;

                                <form
                                    method="post"
                                    action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                    style="display:inline; margin:0;"
                                    data-confirm="Permanently delete &quot;<?php echo htmlspecialchars($co['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>&quot; (#<?php echo (int)$co['id']; ?>) with its logo, gallery, products and services? This cannot be undone."
                                >
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
                                    <input type="hidden" name="company_id" value="<?php echo (int)$co['id']; ?>" />
                                    <input type="hidden" name="action" value="delete" />
                                    <button type="submit" class="more">Delete</button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <div class="post_box">

            <h3>Notes</h3>

            <p>
                Inactive companies are hidden from the public Supplier Directory
                but their data is preserved.
                <br />
                Every activate/deactivate action is logged in
                <code>admin_audit_log</code>.
            </p>

        </div>

    </div>

    <div class="cleaner"></div>

    <div id="templatemo_footer">
        <p style="text-align:center; padding:10px 0;">
            &copy; All on Wheel Ltd. — Restricted area
        </p>
    </div>

</div>

</body>
</html>
```

