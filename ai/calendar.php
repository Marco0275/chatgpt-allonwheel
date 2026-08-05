<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_user_logged_in();

$message='';

if($_SERVER['REQUEST_METHOD']==='POST'){

    csrf_verify();

    $stmt=$pdo->prepare(

        "INSERT INTO ai_calendar
        (
            title,
            language,
            publish_date,
            status,
            created_at
        )
        VALUES
        (
            :title,
            :language,
            :publish_date,
            'planned',
            NOW()
        )"

    );

    $stmt->execute([

        ':title'=>trim($_POST['title']),

        ':language'=>trim($_POST['language']),

        ':publish_date'=>$_POST['publish_date']

    ]);

    $message='Article scheduled.';

}

$rows=$pdo->query(

    "SELECT
        id,
        title,
        language,
        publish_date,
        status
     FROM ai_calendar
     ORDER BY publish_date ASC"

)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES):'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Editorial Calendar</title>

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css"/>

<link rel="icon" href="../images/favicon.ico"/>

<link rel="stylesheet" href="../ddsmoothmenu.css"/>

<link href="../css_pirobox/white/style.css" rel="stylesheet" type="text/css"/>

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

Editorial Calendar

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<?php if($message!=''): ?>

<div class="post_box">

<p><strong><?= htmlspecialchars($message) ?></strong></p>

</div>

<?php endif; ?>

<div id="contact_form">

<form method="post">

<?= csrf_generate(); ?>

<label>Article Title</label>

<input
class="input_field"
type="text"
name="title"
required>

<div class="cleaner h10"></div>

<label>Language</label>

<input
class="input_field"
type="text"
name="language"
value="en">

<div class="cleaner h10"></div>

<label>Publish Date</label>

<input
class="input_field"
type="date"
name="publish_date"
required>

<div class="cleaner h20"></div>

<button
class="more float_r"
type="submit">

Schedule

</button>

</form>

</div>

<div class="post_box">

<h2>Scheduled Articles</h2>

<table width="100%" cellpadding="6">

<tr>

<th>ID</th>

<th align="left">Title</th>

<th>Language</th>

<th>Date</th>

<th>Status</th>

</tr>

<?php foreach($rows as $row): ?>

<tr>

<td>

<?= (int)$row['id'] ?>

</td>

<td>

<?= htmlspecialchars($row['title']) ?>

</td>

<td>

<?= htmlspecialchars($row['language']) ?>

</td>

<td>

<?= htmlspecialchars($row['publish_date']) ?>

</td>

<td>

<?= htmlspecialchars($row['status']) ?>

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