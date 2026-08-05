<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_user_logged_in();

$summary = $pdo->query(

    "SELECT

        COUNT(*) requests,

        SUM(prompt_tokens) prompt_tokens,

        SUM(completion_tokens) completion_tokens,

        SUM(total_tokens) total_tokens

     FROM ai_tokens"

)->fetch(PDO::FETCH_ASSOC);

$daily = $pdo->query(

    "SELECT

        DATE(created_at) day,

        COUNT(*) requests,

        SUM(prompt_tokens) prompt,

        SUM(completion_tokens) completion,

        SUM(total_tokens) total

     FROM ai_tokens

     GROUP BY DATE(created_at)

     ORDER BY day DESC

     LIMIT 90"

)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Token Usage</title>

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

Token Usage

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Summary</h2>

<table width="100%" cellpadding="6">

<tr>

<td>Total Requests</td>

<td><?= (int)$summary['requests'] ?></td>

</tr>

<tr>

<td>Prompt Tokens</td>

<td><?= (int)$summary['prompt_tokens'] ?></td>

</tr>

<tr>

<td>Completion Tokens</td>

<td><?= (int)$summary['completion_tokens'] ?></td>

</tr>

<tr>

<td>Total Tokens</td>

<td><?= (int)$summary['total_tokens'] ?></td>

</tr>

</table>

</div>

<div class="post_box">

<h2>Daily Consumption</h2>

<table width="100%" cellpadding="6">

<tr>

<th>Date</th>

<th>Requests</th>

<th>Prompt</th>

<th>Completion</th>

<th>Total</th>

</tr>

<?php foreach($daily as $row): ?>

<tr>

<td>

<?= htmlspecialchars($row['day']) ?>

</td>

<td>

<?= (int)$row['requests'] ?>

</td>

<td>

<?= (int)$row['prompt'] ?>

</td>

<td>

<?= (int)$row['completion'] ?>

</td>

<td>

<?= (int)$row['total'] ?>

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