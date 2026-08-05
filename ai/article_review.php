<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';

require_user_logged_in();

$repository = new ArticleRepository($pdo);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$article = $repository->findById($id);

if ($article === null) {

    header('Location: articles.php');
    exit;

}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'approve':

            $article->setStatus('approved');

            $repository->save($article);

            $message = 'Article approved.';

        break;

        case 'review':

            $article->setStatus('review');

            $repository->save($article);

            $message = 'Article sent back to review.';

        break;

        case 'draft':

            $article->setStatus('draft');

            $repository->save($article);

            $message = 'Article moved to draft.';

        break;

    }

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1" />

<title>Review AI Article</title>

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />

<link rel="icon" href="../images/favicon.ico" />

<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />

<link href="../css_pirobox/white/style.css"
rel="stylesheet"
type="text/css" />

<script src="../js/jquery.min.js" defer></script>
<script src="../js/piroBox.1_2.js" defer></script>
<script src="../js/site_init.js" defer></script>

</head>

<body>

<div id="templatemo_wrapper">

<div id="templatemo_header">

<?php include('../header.php'); ?>

</div>

<div id="content_top">

<div id="page_title">

Review Article

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<?php if($message!=''): ?>

<div class="post_box">

<p>

<strong>

<?= htmlspecialchars($message) ?>

</strong>

</p>

</div>

<?php endif; ?>

<div class="post_box">

<h2>

<?= htmlspecialchars($article->getTitle()) ?>

</h2>

<p>

<strong>Status:</strong>

<?= htmlspecialchars($article->getStatus()) ?>

</p>

</div>

<div class="post_box">

<h2>

Excerpt

</h2>

<p>

<?= nl2br(htmlspecialchars($article->getExcerpt())) ?>

</p>

</div>

<div class="post_box">

<h2>

Article

</h2>

<p>

<?= nl2br(htmlspecialchars($article->getBody())) ?>

</p>

</div>

<div id="contact_form">

<form method="post">

<?= csrf_generate() ?>

<input
type="hidden"
name="id"
value="<?= $article->getId() ?>">

<button
class="more"
name="action"
value="approve">

Approve

</button>

<button
class="more"
name="action"
value="review">

Review Again

</button>

<button
class="more"
name="action"
value="draft">

Move to Draft

</button>

<a
class="more float_r"
href="article_edit.php?id=<?= $article->getId() ?>">

Edit

</a>

</form>

</div>

<div class="post_box">

<h2>

AI Tools

</h2>

<p>

<a
class="more"
href="article_generate.php?id=<?= $article->getId() ?>">

Regenerate

</a>

<a
class="more"
href="article_seo.php?id=<?= $article->getId() ?>">

SEO

</a>

<a
class="more"
href="article_translate.php?id=<?= $article->getId() ?>">

Translate

</a>

<a
class="more"
href="article_faq.php?id=<?= $article->getId() ?>">

FAQ

</a>

<a
class="more"
href="article_schema.php?id=<?= $article->getId() ?>">

Schema

</a>

<a
class="more"
href="article_publish.php?id=<?= $article->getId() ?>">

Publish

</a>

</p>

</div>

</div>

<div id="templatemo_sidebar">

<?php include('../include_sidebar.php'); ?>

</div>

<div class="cleaner"></div>

<?php include('../footer.php'); ?>

</div>

</body>

</html>