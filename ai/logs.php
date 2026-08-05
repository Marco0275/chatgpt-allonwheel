<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_user_logged_in();

$rows = $pdo->query(

    "SELECT

        id,
        level,
        message,
        context,
        created_at

     FROM ai_logs

     ORDER BY id DESC

     LIMIT 500"

)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Logs</title>

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

AI Logs

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>Latest Events</h2>

<table width="100%" cellpadding="6">

<tr>

<th width="70">ID</th>

<th width="120">Level</th>

<th>Message</th>

<th width="170">Date</th>

</tr>

<?php foreach($rows as $row): ?>

<tr>

<td>

<?= (int)$row['id'] ?>

</td>

<td>

<?= htmlspecialchars($row['level']) ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['message']) ?>

</strong>

<?php if(!empty($row['context'])): ?>

<br><br>

<pre style="white-space:pre-wrap;"><?=
htmlspecialchars(
$row['context']
)
?></pre>

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars($row['created_at']) ?>

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