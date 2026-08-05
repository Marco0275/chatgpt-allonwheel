<?php
// _admin/admin_hero.php  Gestione dell'immagine hero della home (index.php).
//
// 20 lug 2026. L'hero dell'index aveva un'immagine hardcoded
// (images/project.png). Qui l'admin la cambia caricandone una nuova.
//
// Upload salvato in /upload_image/hero/ (NON in images/, dir. 15: il codice
// non tocca images/). Percorso salvato in site_settings['hero_image'].
// Stesso pattern di admin_macros.php: AdminAuth + CSRF + validazione immagine.
// Solo classi CSS esistenti (dir. 8).
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/site_settings.class.php';

$admin_id = AdminAuth::requireAdminSession();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // A) Scelta di un'immagine gia' presente (nessun upload): il campo
    //    "existing" contiene un percorso relativo che l'admin ha selezionato
    //    fra quelle gia' caricate in upload_image/hero/.
    $existing = trim((string)($_POST['existing'] ?? ''));
    if ($existing !== '') {
        // Sicurezza: si accetta SOLO un percorso dentro upload_image/hero/,
        // niente traversal. L'utente e' admin, ma la validazione resta.
        if (preg_match('#^upload_image/hero/[A-Za-z0-9._-]+$#', $existing) === 1
            && is_file(__DIR__ . '/../' . $existing)) {
            SiteSettings::set($pdo, 'hero_image', $existing, (int)$admin_id);
            $msg = 'Hero image updated.';
        } else {
            $msg = 'Invalid image selection.';
        }
    }

    // B) Upload di una nuova immagine (opzionale). Vince sull'eventuale scelta.
    if (!empty($_FILES['hero']['name']) && is_uploaded_file($_FILES['hero']['tmp_name'])) {
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext  = strtolower(pathinfo($_FILES['hero']['name'], PATHINFO_EXTENSION));
        $info = @getimagesize($_FILES['hero']['tmp_name']); // magic bytes: e' un'immagine vera?
        if (!isset($allowed[$ext]) || $info === false) {
            $msg = 'Invalid image (allowed: jpg, png, webp).';
        } elseif ($_FILES['hero']['size'] > 6 * 1024 * 1024) {
            $msg = 'Image too large (max 6 MB).';
        } else {
            $dir = __DIR__ . '/../upload_image/hero';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $fname = 'hero-' . time() . '.' . $ext;
            if (@move_uploaded_file($_FILES['hero']['tmp_name'], $dir . '/' . $fname)) {
                $rel = 'upload_image/hero/' . $fname;
                SiteSettings::set($pdo, 'hero_image', $rel, (int)$admin_id);
                $msg = 'Hero image uploaded and set.';
            } else {
                $msg = 'Upload failed (check folder permissions on /upload_image/hero).';
            }
        }
    } elseif ($msg === '') {
        $msg = 'Nothing to change.';
    }
}

// Valore attuale
$current = SiteSettings::get($pdo, 'hero_image', 'images/project.png');

// Immagini gia' disponibili in upload_image/hero/ (per riusarle senza
// ricaricare). Solo lettura della cartella: nessuna scrittura in images/.
$gallery = [];
$hero_dir = __DIR__ . '/../upload_image/hero';
if (is_dir($hero_dir)) {
    foreach (scandir($hero_dir) ?: [] as $f) {
        if (preg_match('/\.(jpe?g|png|webp)$/i', $f)) {
            $gallery[] = 'upload_image/hero/' . $f;
        }
    }
}

// csrf_generate() restituisce l'INPUT HTML gia' pronto, non il token:
// infilarlo dentro un value="" produceva un token corrotto e il submit
// veniva respinto con "Request not allowed". Si legge il token dalla sessione.
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
require __DIR__ . '/admin_header.php';
?>
<div class="post_box">
  <h2>Home hero image</h2>
  <p>This image is the background of the hero banner on the homepage
     (<code>index.php</code>). Upload a new one, or pick one you already uploaded.</p>

  <p><strong>Current:</strong> <?php echo htmlspecialchars($current, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php
  // 27 lug 2026: il pannello mostrava il valore salvato anche quando il file
  // non era presente sul server (e' successo: la home ha aperto per giorni su
  // un'immagine 404 senza che nulla lo segnalasse). Ora il caso e' esplicito.
  $hero_on_disk = is_file(__DIR__ . '/../' . ltrim($current, '/'));
  $hero_bytes   = $hero_on_disk ? (int)filesize(__DIR__ . '/../' . ltrim($current, '/')) : 0;
  ?>
  <?php if (!$hero_on_disk): ?>
    <p class="error"><strong>Warning:</strong> this file does not exist on the server, so the homepage
       is currently showing the fallback image. Upload the hero again, or pick one below.</p>
  <?php else: ?>
    <p><img src="../<?php echo htmlspecialchars($current, ENT_QUOTES, 'UTF-8'); ?>" alt="Current hero" width="420" loading="lazy" decoding="async" /></p>
    <?php if ($hero_bytes > 400 * 1024): ?>
    <p class="post_meta"><strong>Heads-up:</strong> this image weighs
       <?php echo number_format($hero_bytes / 1024, 0, ',', '.'); ?> KB. It is the first thing every
       visitor downloads: keep it under 400 KB (run <code>tools/optimize_images.sh</code> to
       generate a WebP version, which the homepage picks up automatically).</p>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($msg !== ''): ?>
    <p class="post_meta"><strong><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></strong></p>
  <?php endif; ?>

  <form action="admin_hero.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
    <p><label><strong>Upload a new image:</strong>
      <input type="file" name="hero" accept="image/jpeg,image/png,image/webp" /></label></p>
    <p><em>JPG, PNG or WebP, max 6 MB. Landscape works best (the banner is wide).</em></p>
    <div class="post_meta"><input type="submit" class="more float_r" value="Save" /></div>
    <div class="cleaner"></div>
  </form>

  <?php if (!empty($gallery)): ?>
  <h3>Reuse an image you already uploaded</h3>
  <form action="admin_hero.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
    <ul class="gallery">
    <?php foreach ($gallery as $g): ?>
      <li>
        <label>
          <input type="radio" name="existing" value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $g === $current ? ' checked' : ''; ?> />
          <img src="../<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="150" loading="lazy" decoding="async" />
        </label>
      </li>
    <?php endforeach; ?>
    </ul>
    <div class="cleaner"></div>
    <div class="post_meta"><input type="submit" class="more float_r" value="Use selected" /></div>
    <div class="cleaner"></div>
  </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/admin_footer.php'; ?>
