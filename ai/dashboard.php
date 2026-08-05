<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../repositories/WorkflowJobRepository.php';

require_user_logged_in();

$articles = new ArticleRepository($pdo);
$jobs = new WorkflowJobRepository($pdo);

$totalArticles = $articles->count();

$draftArticles = $articles->countByStatus('draft');

$reviewArticles = $articles->countByStatus('review');

$publishedArticles = $articles->countByStatus('published');

$queuedJobs = count(
    $jobs->findByStatus('queued')
);

$runningJobs = count(
    $jobs->findByStatus('running')
);

$failedJobs = count(
    $jobs->findByStatus('failed')
);

$lastArticles = $articles->latest(10);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Dashboard</title>

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

AI Dashboard

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Overview</h2>

<p><strong>Total Articles:</strong> <?= $totalArticles ?></p>

<p><strong>Draft:</strong> <?= $draftArticles ?></p>

<p><strong>Review:</strong> <?= $reviewArticles ?></p>

<p><strong>Published:</strong> <?= $publishedArticles ?></p>

<p><strong>Queued Jobs:</strong> <?= $queuedJobs ?></p>

<p><strong>Running Jobs:</strong> <?= $runningJobs ?></p>

<p><strong>Failed Jobs:</strong> <?= $failedJobs ?></p>

</div>

<div class="post_box">

<h2>Content</h2>

<p>

<a class="more" href="articles.php">

Articles

</a>

<a class="more" href="article_create.php">

New Article

</a>

<a class="more" href="jobs.php">

Workflow Jobs

</a>

<a class="more" href="prompts.php">

Prompts

</a>

</p>

</div>

<div class="post_box">

<h2>Automation</h2>

<p>

<a class="more" href="statistics.php">

Statistics

</a>

<a class="more" href="token_usage.php">

Token Usage

</a>

<a class="more" href="logs.php">

Logs

</a>

<a class="more" href="calendar.php">

Calendar

</a>

</p>

</div>

<div class="post_box">

<h2>Latest Articles</h2>

<table width="100%" cellpadding="5">

<tr>

<th align="left">Title</th>

<th>Status</th>

<th>Date</th>

<th></th>

</tr>

<?php foreach($lastArticles as $article): ?>

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

</div>

<div id="templatemo_sidebar">

<?php include('../include_sidebar.php'); ?>

</div>

<div class="cleaner"></div>

<?php include('../footer.php'); ?>

</div>

</body>

</html>