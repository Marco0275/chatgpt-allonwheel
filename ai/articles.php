<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';

require_user_logged_in();

$repository = new ArticleRepository($pdo);

$status = trim($_GET['status'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 20;

$total = $repository->count(
    $status
);

$pages = max(
    1,
    (int)ceil($total / $perPage)
);

$page = min(
    $page,
    $pages
);

$offset = ($page - 1) * $perPage;

$list = $repository->findAll(

    $status,

    $perPage,

    $offset

);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Articles</title>

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

AI Articles

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Filters</h2>

<p>

<a class="more" href="articles.php">

All

</a>

<a class="more" href="articles.php?status=draft">

Draft

</a>

<a class="more" href="articles.php?status=processing">

Processing

</a>

<a class="more" href="articles.php?status=review">

Review

</a>

<a class="more" href="articles.php?status=published">

Published

</a>

<a class="more float_r"
href="article_create.php">

New Article

</a>

</p>

</div>

<div class="post_box">

<table width="100%" cellpadding="6">

<tr>

<th align="left">Title</th>

<th>Status</th>

<th>Language</th>

<th>Date</th>

<th></th>

</tr>

<?php foreach($list as $article): ?>

<tr>

<td>

<?= htmlspecialchars(
$article->getTitle()
) ?>

</td>

<td>

<?= htmlspecialchars(
$article->getStatus()
) ?>

</td>

<td>

<?= htmlspecialchars(
$article->getLanguage()
) ?>

</td>

<td>

<?= htmlspecialchars(

$article
->getCreatedAt()
?->format('Y-m-d')

) ?>

</td>

<td>

<a
class="more"
href="article_review.php?id=<?= $article->getId() ?>">

Open

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php if($pages>1): ?>

<div class="templatemo_paging">

<ul>

<?php for($i=1;$i<=$pages;$i++): ?>

<li>

<a

<?php if($i==$page): ?>

class="active"

<?php endif; ?>

href="?page=<?= $i ?>&status=<?= urlencode($status) ?>">

<?= $i ?>

</a>

</li>

<?php endfor; ?>

</ul>

<div class="cleaner"></div>

</div>

<?php endif; ?>

</div>

<div id="templatemo_sidebar">

<?php include('../include_sidebar.php'); ?>

</div>

<div class="cleaner"></div>

<?php include('../footer.php'); ?>

</div>

</body>

</html>