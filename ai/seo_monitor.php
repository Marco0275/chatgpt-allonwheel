<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

require_user_logged_in();

$rows = $pdo->query(

    "SELECT

        id,
        title,
        seo_score,
        readability_score,
        keyword,
        meta_title,
        meta_description,
        internal_links,
        external_links,
        updated_at

     FROM ai_articles

     ORDER BY seo_score ASC,
              updated_at DESC"

)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>SEO Monitor</title>

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

SEO Monitor

</div>

<div class="cleaner"></div>

</div>

<div id="main"></div>

<div id="templatemo_content">

<div class="post_box">

<h2>SEO Overview</h2>

<table width="100%" cellpadding="6">

<tr>

<th align="left">Article</th>

<th>SEO</th>

<th>Readability</th>

<th>Keyword</th>

<th>Links</th>

<th></th>

</tr>

<?php foreach($rows as $row): ?>

<tr>

<td>

<?= htmlspecialchars($row['title']) ?>

</td>

<td>

<?= (int)$row['seo_score'] ?>/100

</td>

<td>

<?= (int)$row['readability_score'] ?>/100

</td>

<td>

<?= htmlspecialchars($row['keyword']) ?>

</td>

<td>

<?= (int)$row['internal_links'] ?>

 /

<?= (int)$row['external_links'] ?>

</td>

<td>

<a
class="more"
href="article_review.php?id=<?= (int)$row['id'] ?>">

Open

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<div class="post_box">

<h2>Metadata</h2>

<table width="100%" cellpadding="6">

<tr>

<th align="left">Article</th>

<th align="left">Meta Title</th>

<th align="left">Meta Description</th>

</tr>

<?php foreach($rows as $row): ?>

<tr>

<td>

<?= htmlspecialchars($row['title']) ?>

</td>

<td>

<?= htmlspecialchars($row['meta_title']) ?>

</td>

<td>

<?= htmlspecialchars($row['meta_description']) ?>

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