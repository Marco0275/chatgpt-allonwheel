<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../libs/ArticlePublisher.php';

require_user_logged_in();

$repository = new ArticleRepository($pdo);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$article = $repository->findById($id);

if ($article === null) {

    header('Location: articles.php');
    exit;

}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    try {

        $publisher = new ArticlePublisher($pdo);

        $publisher->publish($article);

        $message = 'Article published successfully.';

        $article = $repository->findById($id);

    } catch (Throwable $e) {

        $error = $e->getMessage();

    }

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1" />

<title>Publish Article</title>

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

Publish AI Article

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<?php if ($message !== ''): ?>

<div class="post_box">

<p><strong><?= htmlspecialchars($message) ?></strong></p>

</div>

<?php endif; ?>

<?php if ($error !== ''): ?>

<div class="post_box">

<p><strong><?= htmlspecialchars($error) ?></strong></p>

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

<p>

<strong>Language:</strong>

<?= htmlspecialchars($article->getLanguage()) ?>

</p>

<p>

<strong>Category:</strong>

<?= htmlspecialchars($article->getCategory()) ?>

</p>

</div>

<div class="post_box">

<h2>

SEO

</h2>

<p>

<strong>SEO Title</strong>

<br>

<?= htmlspecialchars($article->getSeoTitle()) ?>

</p>

<p>

<strong>SEO Description</strong>

<br>

<?= nl2br(htmlspecialchars($article->getSeoDescription())) ?>

</p>

</div>

<div class="post_box">

<h2>

Publication Checklist

</h2>

<p>

✔ Title

<br>

✔ Excerpt

<br>

✔ Article

<br>

✔ SEO

<br>

✔ Slug

<br>

✔ Canonical

<br>

✔ Metadata

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
class="more float_r"
type="submit">

Publish Article

</button>

<a
class="more float_l"
href="article_review.php?id=<?= $article->getId() ?>">

Back

</a>

</form>

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