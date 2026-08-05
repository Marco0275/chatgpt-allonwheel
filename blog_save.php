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

try {
    $blog = new BlogManager($pdo);
    $new_id = $blog->insertArticle([
        'id_user' => $id_user,
        'title'   => $title,
        'excerpt' => $excerpt,
        'body'    => $body,
        'image'   => $image_filename,
        'status'  => $status,
    ]);
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
