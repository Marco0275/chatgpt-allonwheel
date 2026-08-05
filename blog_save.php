<?php
// ============================================================
// blog_save.php — Handler pubblicazione articolo (utenti registrati).
// Login + CSRF + validazione + upload immagine opzionale. Inserisce in
// `blog`. Stato di default 'published' (immediato): per attivare la
// moderazione preventiva, impostare $status = 'pending' qui sotto.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';
require_once __DIR__ . '/libs/upload_helper.class.php';

$id_user = require_user_logged_in();

require_once __DIR__ . '/libs/user_tier.class.php';
require_once __DIR__ . '/libs/plan_policy.class.php';
if (!PlanPolicy::canBlogPublish(UserTier::getTier($pdo, $id_user))) {
    $_SESSION['blog_error'] = 'Publishing blog articles is a Gold plan feature. Upgrade your plan to publish.';
    header('Location: /blog_write.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: /blog_write.php');
    exit;
}

csrf_verify();

$title   = trim($_POST['title'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$body    = trim($_POST['body'] ?? '');

if ($title === '' || $body === '') {
    $_SESSION['blog_error'] = 'Title and article text are required.';
    header('Location: /blog_write.php');
    exit;
}

// Upload immagine di copertina (opzionale)
$image_filename = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $res = UploadHelper::handleImageUpload($_FILES['image'], [
        'target_dir_original'  => '/upload_image/blog/original/',
        'target_dir_thumbnail' => '/upload_image/blog/thumbnail/',
        'thumb_width'          => 220,
        'thumb_height'         => 150,
        'thumb_crop'           => true,
        'max_size_bytes'       => 5 * 1024 * 1024,
        'filename_prefix'      => 'blog_' . $id_user,
    ]);
    if (!$res['ok']) {
        $_SESSION['blog_error'] = 'Image upload failed: ' . $res['error'];
        header('Location: /blog_write.php');
        exit;
    }
    $image_filename = (string)$res['filename'];
}

// Stato iniziale. Cambiare in 'pending' per moderazione preventiva admin.
$status = 'published';
// Lingua sorgente = lingua del footer scelta dall'utente (dir. i18n).
$aow_src_lang = function_exists('aow_locale') ? aow_locale() : 'en';
$aow_tx_group = bin2hex(random_bytes(16));

try {
    $blog = new BlogManager($pdo);
    $new_id = $blog->insertLocalized([
        'id_user'           => $id_user,
        'title'             => $title,
        'excerpt'           => $excerpt,
        'body'              => $body,
        'image'             => $image_filename,
        'status'            => $status,
        'language'          => $aow_src_lang,
        'translation_group' => $aow_tx_group,
        'source'            => 'human',
    ]);
    // Accoda la traduzione automatica nelle altre lingue (la esegue il cron).
    if ($new_id > 0) {
        try {
            $pdo->prepare("INSERT IGNORE INTO blog_translation_jobs (blog_id, from_lang, translation_group, status) VALUES (:b,:l,:g,'pending')")
                ->execute([':b'=>$new_id, ':l'=>$aow_src_lang, ':g'=>$aow_tx_group]);
        } catch (Throwable $e) { error_log('[blog_save] enqueue traduzione: '.$e->getMessage()); }
    }
} catch (PDOException $e) {
    error_log('[Allonwheel] blog insert error: ' . $e->getMessage());
    // Cleanup immagine se l'insert fallisce
    if ($image_filename !== '') {
        $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/blog/';
        foreach (['original/', 'thumbnail/'] as $sub) {
            $f = $base . $sub . basename($image_filename);
            if (is_file($f)) { @unlink($f); }
        }
    }
    $_SESSION['blog_error'] = 'Database error while publishing the article. Please try again.';
    header('Location: /blog_write.php');
    exit;
}

if ($status === 'published' && $new_id > 0) {
    $_SESSION['blog_success'] = 'Article published successfully.';
    header('Location: /blog_post.php?id=' . $new_id);
} else {
    $_SESSION['blog_success'] = 'Article submitted and awaiting review.';
    header('Location: /blog.php');
}
exit;
