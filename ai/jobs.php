<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_once __DIR__ . '/../repositories/WorkflowJobRepository.php';

require_user_logged_in();

$repository = new WorkflowJobRepository($pdo);

$status = trim($_GET['status'] ?? '');

if ($status === '') {

    $jobs = array_merge(

        $repository->findByStatus('queued'),

        $repository->findByStatus('running'),

        $repository->findByStatus('failed'),

        $repository->findByStatus('completed')

    );

} else {

    $jobs = $repository->findByStatus($status);

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Workflow Jobs</title>

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

Workflow Jobs

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Filters</h2>

<p>

<a class="more" href="jobs.php">

All

</a>

<a class="more" href="jobs.php?status=queued">

Queued

</a>

<a class="more" href="jobs.php?status=running">

Running

</a>

<a class="more" href="jobs.php?status=failed">

Failed

</a>

<a class="more" href="jobs.php?status=completed">

Completed

</a>

</p>

</div>

<div class="post_box">

<table width="100%" cellpadding="6">

<tr>

<th>ID</th>

<th>Article</th>

<th>Type</th>

<th>Status</th>

<th>Priority</th>

<th>Attempts</th>

<th></th>

</tr>

<?php foreach($jobs as $job): ?>

<tr>

<td>

<?= $job->getId() ?>

</td>

<td>

<?= $job->getArticleId() ?>

</td>

<td>

<?= htmlspecialchars(
$job->getType()
) ?>

</td>

<td>

<?= htmlspecialchars(
$job->getStatus()
) ?>

</td>

<td>

<?= $job->getPriority() ?>

</td>

<td>

<?= $job->getAttempts() ?>

/

<?= $job->getMaxAttempts() ?>

</td>

<td>

<a
class="more"
href="job_detail.php?id=<?= $job->getId() ?>">

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