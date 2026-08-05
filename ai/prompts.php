<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_once __DIR__ . '/../repositories/PromptRepository.php';

require_user_logged_in();

$repository = new PromptRepository($pdo);

$category = trim($_GET['category'] ?? '');

if ($category === '') {

    $prompts = $repository->findAll();

} else {

    $prompts = $repository->findByCategory(
        $category
    );

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Prompt Manager</title>

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css"/>

<link rel="icon" href="../images/favicon.ico"/>

<link rel="stylesheet" href="../ddsmoothmenu.css"/>

<link href="../css_pirobox/white/style.css"
rel="stylesheet"
type="text/css"/>

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

Prompt Manager

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Categories</h2>

<p>

<a class="more" href="prompts.php">

All

</a>

<a class="more" href="prompts.php?category=content">

Content

</a>

<a class="more" href="prompts.php?category=seo">

SEO

</a>

<a class="more" href="prompts.php?category=faq">

FAQ

</a>

<a class="more" href="prompts.php?category=schema">

Schema

</a>

<a class="more" href="prompts.php?category=image">

Image

</a>

<a class="more" href="prompts.php?category=translation">

Translation

</a>

</p>

</div>

<div class="post_box">

<table width="100%" cellpadding="6">

<tr>

<th align="left">Name</th>

<th>Category</th>

<th>Model</th>

<th>Temperature</th>

<th></th>

</tr>

<?php foreach($prompts as $prompt): ?>

<tr>

<td>

<?= htmlspecialchars(
$prompt->getName()
) ?>

</td>

<td>

<?= htmlspecialchars(
$prompt->getCategory()
) ?>

</td>

<td>

<?= htmlspecialchars(
$prompt->getModel()
) ?>

</td>

<td>

<?= htmlspecialchars(
(string)$prompt->getTemperature()
) ?>

</td>

<td>

<a
class="more"
href="prompt_edit.php?id=<?= $prompt->getId() ?>">

Edit

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

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