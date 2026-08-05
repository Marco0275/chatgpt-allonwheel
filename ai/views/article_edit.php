<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">

<title>AI Editor</title>

<link rel="stylesheet" href="../allonwheel_style.css">

<script src="../js/jquery.min.js"></script>

<script src="assets/js/article_edit.js"></script>

</head>

<body>

<?php include '../header.php'; ?>

<div id="templatemo_wrapper">

<div id="content_top">

<div id="page_title">

AI Article Editor

</div>

</div>

<div id="templatemo_content">

<div class="post_box">

<input
type="text"
id="title"
class="input_field"
value="<?= htmlspecialchars($article->getTitle()); ?>"
>

<div class="cleaner h10"></div>

<input
type="text"
id="excerpt"
class="input_field"
value="<?= htmlspecialchars($article->getExcerpt()); ?>"
>

<div class="cleaner h10"></div>

<textarea
id="body"
rows="24"
><?= htmlspecialchars($article->getBody()); ?></textarea>

<div class="cleaner h20"></div>

<a
href="#"
class="more"
id="rewrite"
>Rewrite</a>

<a
href="#"
class="more"
id="improve"
>Improve</a>

<a
href="#"
class="more"
id="seo"
>SEO</a>

<a
href="#"
class="more"
id="translate"
>Translate</a>

<a
href="#"
class="more"
id="publish"
>Publish</a>

</div>

</div>

<div id="templatemo_sidebar">

<?php include '../include_sidebar.php'; ?>

</div>

<div class="cleaner"></div>

<?php include '../footer.php'; ?>

</body>

</html>