<?php
// _admin/admin_macros.php — Gestione hero image e intro IT delle 5 macro.
// Upload salvato in /upload_image/macros/ (NON in images/, dir. 15).
// Accessibile solo ad admin. Solo classi CSS esistenti (dir. 8).
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $slug = trim((string)($_POST['slug'] ?? ''));
    // la macro deve esistere
    $chk = $pdo->prepare('SELECT id FROM `product_macros` WHERE slug = :s LIMIT 1');
    $chk->execute([':s' => $slug]);
    if ($chk->fetchColumn()) {
        // 1) intro IT (sempre aggiornabile)
        if (isset($_POST['intro_text_it'])) {
            $u = $pdo->prepare('UPDATE `product_macros` SET intro_text_it = :t WHERE slug = :s');
            $u->execute([':t' => trim((string)$_POST['intro_text_it']), ':s' => $slug]);
        }
        // 2) upload hero (opzionale)
        if (!empty($_FILES['hero']['name']) && is_uploaded_file($_FILES['hero']['tmp_name'])) {
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            $ext = strtolower(pathinfo($_FILES['hero']['name'], PATHINFO_EXTENSION));
            $info = @getimagesize($_FILES['hero']['tmp_name']);
            if (!isset($allowed[$ext]) || $info === false) {
                $msg = 'Invalid image (allowed: jpg, png, webp).';
            } elseif ($_FILES['hero']['size'] > 6 * 1024 * 1024) {
                $msg = 'Image too large (max 6 MB).';
            } else {
                $dir = __DIR__ . '/../upload_image/macros';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $fname = $slug . '-' . time() . '.' . $ext;
                if (@move_uploaded_file($_FILES['hero']['tmp_name'], $dir . '/' . $fname)) {
                    $rel = '/upload_image/macros/' . $fname;
                    $u = $pdo->prepare('UPDATE `product_macros` SET hero_image = :h WHERE slug = :s');
                    $u->execute([':h' => $rel, ':s' => $slug]);
                    $msg = 'Saved.';
                } else {
                    $msg = 'Upload failed (check folder permissions on /upload_image/macros).';
                }
            }
        } elseif ($msg === '') {
            $msg = 'Saved.';
        }
    } else {
        $msg = 'Unknown macro.';
    }
}

$rows = $pdo->query('SELECT slug, name, hero_image, intro_text_it FROM `product_macros` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';

$admin_title  = 'Hero images';
$admin_active = 'macros';
include __DIR__ . '/admin_header.php';
?>
<div class="post_box">
  <h2>Macro hero images &amp; Italian intro</h2>
  <?php if ($msg !== ''): ?><p><em><?php echo htmlspecialchars($msg); ?></em></p><?php endif; ?>
  <p>Upload a hero image for each family (saved in <code>/upload_image/macros/</code>) and optionally edit the Italian intro shown on the marketplace.</p>

  <?php foreach ($rows as $r): ?>
  <div class="post_box">
    <h3><?php echo htmlspecialchars($r['name']); ?> <small>(<?php echo htmlspecialchars($r['slug']); ?>)</small></h3>
    <?php if (!empty($r['hero_image'])): ?>
      <p><img src="<?php echo htmlspecialchars($r['hero_image']); ?>" alt="<?php echo htmlspecialchars($r['name']); ?>" width="220" loading="lazy" decoding="async" /></p>
      <p><em>Current: <?php echo htmlspecialchars($r['hero_image']); ?></em></p>
    <?php else: ?>
      <p><em>No hero image yet.</em></p>
    <?php endif; ?>
    <form method="post" action="admin_macros.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>" />
      <input type="hidden" name="slug" value="<?php echo htmlspecialchars($r['slug']); ?>" />
      <p><label><strong>New hero image:</strong> <input type="file" name="hero" accept="image/jpeg,image/png,image/webp" /></label></p>
      <p><label><strong>Italian intro:</strong></label><br />
        <textarea name="intro_text_it" rows="4" cols="70"><?php echo htmlspecialchars((string)($r['intro_text_it'] ?? '')); ?></textarea></p>
      <div class="post_meta"><input type="submit" class="more float_r" value="Save" /></div>
      <div class="cleaner"></div>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/admin_footer.php'; ?>
