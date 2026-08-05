<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/WorkflowJobRepository.php';

require_user_logged_in();

$repository = new WorkflowJobRepository($pdo);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$job = $repository->find($id);

if ($job === null) {

    header('Location: jobs.php');
    exit;

}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'retry':

            if ($job->canRetry()) {

                $job->increaseAttempts();

                $job->setStatus('queued');

                $job->setStartedAt(null);

                $job->setFinishedAt(null);

                $job->setError(null);

                $repository->update($job);

                $message = 'Job queued again.';

            }

        break;

        case 'cancel':

            $job->setStatus('cancelled');

            $repository->update($job);

            $message = 'Job cancelled.';

        break;

    }

    $job = $repository->find($id);

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Workflow Job</title>

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

Workflow Job

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

<div class="post_box">

<h2>

Job #<?= $job->getId() ?>

</h2>

<p>

<strong>Article:</strong>

<?= $job->getArticleId() ?>

</p>

<p>

<strong>Type:</strong>

<?= htmlspecialchars($job->getType()) ?>

</p>

<p>

<strong>Status:</strong>

<?= htmlspecialchars($job->getStatus()) ?>

</p>

<p>

<strong>Priority:</strong>

<?= $job->getPriority() ?>

</p>

<p>

<strong>Attempts:</strong>

<?= $job->getAttempts() ?>

/

<?= $job->getMaxAttempts() ?>

</p>

<p>

<strong>Created:</strong>

<?= $job->getCreatedAt()?->format('Y-m-d H:i:s') ?>

</p>

<p>

<strong>Started:</strong>

<?= $job->getStartedAt()?->format('Y-m-d H:i:s') ?>

</p>

<p>

<strong>Finished:</strong>

<?= $job->getFinishedAt()?->format('Y-m-d H:i:s') ?>

</p>

<?php if($job->getError()): ?>

<p>

<strong>Error:</strong>

<br>

<?= nl2br(htmlspecialchars($job->getError())) ?>

</p>

<?php endif; ?>

</div>

<div class="post_box">

<h2>

Payload

</h2>

<pre><?=
htmlspecialchars(

json_encode(
    $job->getPayload(),
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE
)

)
?></pre>

</div>

<div id="contact_form">

<form method="post">

<?= csrf_generate() ?>

<input
type="hidden"
name="id"
value="<?= $job->getId() ?>">

<?php if($job->canRetry()): ?>

<button
class="more"
name="action"
value="retry">

Retry

</button>

<?php endif; ?>

<?php if(!$job->isCompleted()): ?>

<button
class="more"
name="action"
value="cancel">

Cancel

</button>

<?php endif; ?>

<a
class="more float_r"
href="jobs.php">

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