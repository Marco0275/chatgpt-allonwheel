<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';

require_user_logged_in();

$repository = new ArticleRepository($pdo);

$id = (int)($_GET['id'] ?? 0);

$article = $repository->findById($id);

if ($article === null) {

    die('Article not found');

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1" />

<title>Edit AI Article</title>

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />

<link rel="icon" href="../images/favicon.ico" />

<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />

<link href="../css_pirobox/white/style.css"
media="screen"
title="shadow"
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

Edit AI Article

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>

<?= htmlspecialchars($article->getTitle()) ?>

</h2>

<p>

ID:
<strong><?= $article->getId() ?></strong>

|

Status:
<strong><?= htmlspecialchars($article->getStatus()) ?></strong>

|

Language:
<strong><?= htmlspecialchars($article->getLanguage()) ?></strong>

</p>

</div>

<div id="contact_form">

<form
method="post"
action="article_save.php">

<?= csrf_generate(); ?>

<input
type="hidden"
name="id"
value="<?= $article->getId() ?>">

<label>

Title

</label>

<input
class="required input_field"
type="text"
name="title"
value="<?= htmlspecialchars($article->getTitle()) ?>">

<div class="cleaner h10"></div>

<label>

Slug

</label>

<input
class="input_field"
type="text"
name="slug"
value="<?= htmlspecialchars($article->getSlug()) ?>">

<div class="cleaner h10"></div>

<label>

Excerpt

</label>

<textarea
class="required"
name="excerpt"
rows="5"><?= htmlspecialchars($article->getExcerpt()) ?></textarea>

<div class="cleaner h10"></div>

<label>

Body

</label>

<textarea
class="required"
name="body"
rows="25"><?= htmlspecialchars($article->getBody()) ?></textarea>

<div class="cleaner h10"></div>

<label>

Category

</label>

<input
class="input_field"
type="text"
name="category"
value="<?= htmlspecialchars($article->getCategory()) ?>">

<div class="cleaner h10"></div>

<label>

Language

</label>

<input
class="input_field"
type="text"
name="language"
value="<?= htmlspecialchars($article->getLanguage()) ?>">

<div class="cleaner h10"></div>

<label>

SEO Title

</label>

<input
class="input_field"
type="text"
name="seo_title"
value="<?= htmlspecialchars($article->getSeoTitle()) ?>">

<div class="cleaner h10"></div>

<label>

SEO Description

</label>

<textarea
name="seo_description"
rows="4"><?= htmlspecialchars($article->getSeoDescription()) ?></textarea>

<div class="cleaner h10"></div>

<label>

Canonical URL

</label>

<input
class="input_field"
type="text"
name="canonical"
value="<?= htmlspecialchars($article->getCanonical()) ?>">

<div class="cleaner h20"></div>

<button
class="more float_r"
type="submit">

Save

</button>

<a
class="more float_l"
href="articles.php">

Cancel

</a>

</form>

</div>

<div class="post_box">

<h2>

Artificial Intelligence

</h2>

<p>

<a
class="more"
href="article_generate.php?id=<?= $article->getId() ?>">

Generate Again

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
href="article_images.php?id=<?= $article->getId() ?>">

Images

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