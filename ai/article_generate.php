<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../libs/ContentGenerator.php';

require_user_logged_in();

$repository = new ArticleRepository($pdo);

$message = $_SESSION['ai_success'] ?? '';
$error   = $_SESSION['ai_error'] ?? '';

unset(
    $_SESSION['ai_success'],
    $_SESSION['ai_error']
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    try {

        $article = new Article();

        $article->setTitle('');

        $article->setExcerpt('');

        $article->setBody('');

        $article->setLanguage(
            trim($_POST['language'])
        );

        $article->setCategory(
            trim($_POST['category'])
        );

        $article->setStatus('draft');

        $article->setMeta([

            'topic' =>
                trim($_POST['topic']),

            'keywords' =>
                trim($_POST['keywords']),

            'audience' =>
                trim($_POST['audience']),

            'country' =>
                trim($_POST['country']),

            'tone' =>
                trim($_POST['tone']),

            'length' =>
                trim($_POST['length']),

            'purpose' =>
                trim($_POST['purpose']),

            'references' =>
                trim($_POST['references'])

        ]);

        $generator = new ContentGenerator($pdo);

        $article = $generator->generate($article);

        $repository->save($article);

        $_SESSION['ai_success'] =
            'Article generated successfully.';

        header(
            'Location: article_edit.php?id=' .
            $article->getId()
        );

        exit;

    } catch(Throwable $e) {

        $error = $e->getMessage();

    }

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1" />

<title>Generate AI Article</title>

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

Generate AI Article

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

<?php if($error!=''): ?>

<div class="post_box">

<p>

<strong>

<?= htmlspecialchars($error) ?>

</strong>

</p>

</div>

<?php endif; ?>

<div id="contact_form">

<form
method="post">

<?= csrf_generate() ?>

<label>

Topic

</label>

<input
class="required input_field"
type="text"
name="topic">

<div class="cleaner h10"></div>

<label>

Keywords

</label>

<input
class="input_field"
type="text"
name="keywords">

<div class="cleaner h10"></div>

<label>

Audience

</label>

<input
class="input_field"
type="text"
name="audience">

<div class="cleaner h10"></div>

<label>

Country

</label>

<input
class="input_field"
type="text"
name="country"
value="Europe">

<div class="cleaner h10"></div>

<label>

Language

</label>

<select
class="input_field"
name="language">

<option value="en">English</option>
<option value="it">Italiano</option>
<option value="fr">Français</option>
<option value="de">Deutsch</option>
<option value="es">Español</option>

</select>

<div class="cleaner h10"></div>

<label>

Category

</label>

<input
class="input_field"
type="text"
name="category">

<div class="cleaner h10"></div>

<label>

Tone

</label>

<select
class="input_field"
name="tone">

<option>Professional</option>
<option>Technical</option>
<option>Commercial</option>
<option>Institutional</option>

</select>

<div class="cleaner h10"></div>

<label>

Length

</label>

<select
class="input_field"
name="length">

<option>Short</option>
<option selected>Medium</option>
<option>Long</option>

</select>

<div class="cleaner h10"></div>

<label>

Purpose

</label>

<select
class="input_field"
name="purpose">

<option>SEO</option>
<option>Lead Generation</option>
<option>Knowledge Base</option>
<option>Landing Page</option>

</select>

<div class="cleaner h10"></div>

<label>

References

</label>

<textarea
name="references"
rows="6"></textarea>

<div class="cleaner h20"></div>

<button
class="more float_r"
type="submit">

Generate Article

</button>

<a
class="more float_l"
href="articles.php">

Cancel

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