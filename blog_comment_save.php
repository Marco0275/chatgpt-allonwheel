<?php
// ============================================================
// blog_comment_save.php — Handler commenti del blog.
// Azioni: 'add' (utente registrato scrive un commento di solo testo) e
// 'delete' (l'autore del commento o un admin lo cancella).
// Login + CSRF obbligatori. Nessuna immagine: il corpo e' solo testo.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';

$me_id = require_user_logged_in();

require_once __DIR__ . '/libs/user_tier.class.php';
require_once __DIR__ . '/libs/plan_policy.class.php';
if (!PlanPolicy::canBlogReply(UserTier::getTier($pdo, $me_id))) {
    $_SESSION['error_message'] = 'Replying on the blog is available to Premium and Gold plans.';
    header('Location: /blog.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /blog.php');
    exit;
}

csrf_verify();

$action  = in_array($_POST['action'] ?? '', ['add', 'delete'], true) ? $_POST['action'] : '';
$id_blog = (int)($_POST['id_blog'] ?? 0);

$back = '/blog_post.php?id=' . $id_blog . '#comment_section';

$blog = new BlogManager($pdo);

if ($action === 'add') {
    // Solo testo: niente HTML/immagini. strip_tags + escape in output.
    $body = trim((string)($_POST['body'] ?? ''));
    $body = strip_tags($body);

    if ($id_blog <= 0 || $blog->getById($id_blog, $me_id, true) === null) {
        $_SESSION['comment_error'] = 'Article not found.';
        header('Location: /blog.php');
        exit;
    }
    if ($body === '') {
        $_SESSION['comment_error'] = 'The comment cannot be empty.';
        header('Location: ' . $back);
        exit;
    }
    if (mb_strlen($body) > 5000) {
        $body = mb_substr($body, 0, 5000);
    }

    try {
        $blog->insertComment($id_blog, $me_id, $body);
        $_SESSION['comment_success'] = 'Comment posted.';
        // Notifica i partecipanti del thread (autore + commentatori), one-to-one (1 destinatario/mail).
        try {
            require_once __DIR__ . '/libs/mailer.class.php';
            $art   = $blog->getById($id_blog, $me_id, true);
            $title = trim((string)($art['title'] ?? 'a discussion'));
            $base  = defined('BASE_URL') ? rtrim(BASE_URL, '/')
                   : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
                      . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.allonwheel.com'));
            $url = $base . '/blog_post.php?id=' . $id_blog . '#comment_section';
            foreach ($blog->getThreadParticipantEmails($id_blog, (int)$me_id) as $p) {
                $u   = htmlspecialchars((string)($p['username'] ?? ''), ENT_QUOTES, 'UTF-8');
                $tt  = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $lnk = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $body_html = '<p>Hi ' . $u . ',</p>'
                    . '<p>There is a new reply in the discussion <strong>' . $tt . '</strong> on All on Wheel.</p>'
                    . '<p><a href="' . $lnk . '">Open the conversation</a></p>';
                Mailer::send((string)$p['email'], 'New reply: ' . $title, $body_html, '', (string)($p['username'] ?? ''));
            }
        } catch (\Throwable $e) {
            error_log('[Allonwheel] forum notify error: ' . $e->getMessage());
        }
    } catch (PDOException $e) {
        error_log('[Allonwheel] comment insert error: ' . $e->getMessage());
        $_SESSION['comment_error'] = 'Database error while posting the comment.';
    }
    header('Location: ' . $back);
    exit;
}

if ($action === 'delete') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    $comment    = $comment_id > 0 ? $blog->getComment($comment_id) : null;
    $is_admin   = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

    if ($comment === null) {
        $_SESSION['comment_error'] = 'Comment not found.';
        header('Location: ' . $back);
        exit;
    }
    // Isolamento: solo l'autore del commento o un admin possono cancellare.
    if (!$is_admin && (int)$comment['id_user'] !== $me_id) {
        $_SESSION['comment_error'] = 'You can only delete your own comments.';
        header('Location: /blog_post.php?id=' . (int)$comment['id_blog'] . '#comment_section');
        exit;
    }
    $blog->deleteComment($comment_id);
    $_SESSION['comment_success'] = 'Comment deleted.';
    header('Location: /blog_post.php?id=' . (int)$comment['id_blog'] . '#comment_section');
    exit;
}

header('Location: ' . $back);
exit;
