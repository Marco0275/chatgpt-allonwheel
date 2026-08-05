<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_user_logged_in();

$stats = [];

$stats['articles'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_articles"
)
->fetchColumn();

$stats['draft'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_articles
     WHERE status='draft'"
)
->fetchColumn();

$stats['processing'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_articles
     WHERE status='processing'"
)
->fetchColumn();

$stats['review'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_articles
     WHERE status='review'"
)
->fetchColumn();

$stats['published'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_articles
     WHERE status='published'"
)
->fetchColumn();

$stats['queued'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_workflow_jobs
     WHERE status='queued'"
)
->fetchColumn();

$stats['running'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_workflow_jobs
     WHERE status='running'"
)
->fetchColumn();

$stats['completed'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_workflow_jobs
     WHERE status='completed'"
)
->fetchColumn();

$stats['failed'] = (int)$pdo
->query(
    "SELECT COUNT(*) FROM ai_workflow_jobs
     WHERE status='failed'"
)
->fetchColumn();

$tokens = $pdo
->query(

    "SELECT

        SUM(prompt_tokens) prompt,

        SUM(completion_tokens) completion,

        SUM(total_tokens) total

     FROM ai_tokens"

)
->fetch(PDO::FETCH_ASSOC);

$lastRuns = $pdo
->query(

    "SELECT

        DATE(created_at) day,

        COUNT(*) jobs

     FROM ai_workflow_jobs

     GROUP BY DATE(created_at)

     ORDER BY day DESC

     LIMIT 30"

)
->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Statistics</title>

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

AI Statistics

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Articles</h2>

<table width="100%" cellpadding="6">

<tr>

<td>Total</td>

<td><?= $stats['articles'] ?></td>

</tr>

<tr>

<td>Draft</td>

<td><?= $stats['draft'] ?></td>

</tr>

<tr>

<td>Processing</td>

<td><?= $stats['processing'] ?></td>

</tr>

<tr>

<td>Review</td>

<td><?= $stats['review'] ?></td>

</tr>

<tr>

<td>Published</td>

<td><?= $stats['published'] ?></td>

</tr>

</table>

</div>

<div class="post_box">

<h2>Workflow</h2>

<table width="100%" cellpadding="6">

<tr>

<td>Queued</td>

<td><?= $stats['queued'] ?></td>

</tr>

<tr>

<td>Running</td>

<td><?= $stats['running'] ?></td>

</tr>

<tr>

<td>Completed</td>

<td><?= $stats['completed'] ?></td>

</tr>

<tr>

<td>Failed</td>

<td><?= $stats['failed'] ?></td>

</tr>

</table>

</div>

<div class="post_box">

<h2>Token Usage</h2>

<table width="100%" cellpadding="6">

<tr>

<td>Prompt Tokens</td>

<td><?= (int)$tokens['prompt'] ?></td>

</tr>

<tr>

<td>Completion Tokens</td>

<td><?= (int)$tokens['completion'] ?></td>

</tr>

<tr>

<td>Total Tokens</td>

<td><?= (int)$tokens['total'] ?></td>

</tr>

</table>

</div>

<div class="post_box">

<h2>Daily Activity</h2>

<table width="100%" cellpadding="6">

<tr>

<th>Date</th>

<th>Jobs</th>

</tr>

<?php foreach($lastRuns as $row): ?>

<tr>

<td>

<?= htmlspecialchars($row['day']) ?>

</td>

<td>

<?= (int)$row['jobs'] ?>

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