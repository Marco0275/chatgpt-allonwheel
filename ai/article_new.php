<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/controllers/ArticleNewController.php';

$controller = new ArticleNewController();

$form = $controller->defaults();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">

<title>Generate AI Article</title>

<link
rel="stylesheet"
href="../allonwheel_style.css"
>

<script
src="../js/jquery.min.js"
defer
></script>

<script
src="assets/js/article_new.js"
defer
></script>

</head>

<body>

<?php include '../header.php'; ?>

<div id="templatemo_wrapper">

<div id="content_top">

<div id="page_title">

Generate AI Article

</div>

</div>

<div id="templatemo_content">

<?php include __DIR__.'/views/article_new.php'; ?>

</div>

<div id="templatemo_sidebar">

<?php include '../include_sidebar.php'; ?>

</div>

<div class="cleaner"></div>

<?php include '../footer.php'; ?>

</body>

</html>