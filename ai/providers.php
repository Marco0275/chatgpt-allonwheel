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

        "REPLACE INTO ai_providers
        (
            provider,
            api_key,
            endpoint,
            organization,
            enabled,
            updated_at
        )
        VALUES
        (
            :provider,
            :api_key,
            :endpoint,
            :organization,
            :enabled,
            NOW()
        )"

    );

    $stmt->execute([

        ':provider'=>trim($_POST['provider']),

        ':api_key'=>trim($_POST['api_key']),

        ':endpoint'=>trim($_POST['endpoint']),

        ':organization'=>trim($_POST['organization']),

        ':enabled'=>isset($_POST['enabled'])?1:0

    ]);

    $message='Provider saved.';

}

$rows=$pdo->query(

    "SELECT
        id,
        provider,
        endpoint,
        organization,
        enabled,
        updated_at
     FROM ai_providers
     ORDER BY provider"

)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES):'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Providers</title>

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

AI Providers

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

<label>Provider</label>

<input
class="input_field"
type="text"
name="provider"
value="OpenAI"
required>

<div class="cleaner h10"></div>

<label>API Key</label>

<input
class="input_field"
type="password"
name="api_key"
autocomplete="off">

<div class="cleaner h10"></div>

<label>Endpoint</label>

<input
class="input_field"
type="text"
name="endpoint"
value="https://api.openai.com/v1/chat/completions">

<div class="cleaner h10"></div>

<label>Organization</label>

<input
class="input_field"
type="text"
name="organization">

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="enabled"
checked>

Enabled

</label>

<div class="cleaner h20"></div>

<button
type="submit"
class="more float_r">

Save

</button>

</form>

</div>

<div class="post_box">

<h2>Configured Providers</h2>

<table width="100%" cellpadding="6">

<tr>

<th align="left">Provider</th>

<th align="left">Endpoint</th>

<th>Organization</th>

<th>Enabled</th>

<th>Updated</th>

</tr>

<?php foreach($rows as $row): ?>

<tr>

<td>

<?= htmlspecialchars($row['provider']) ?>

</td>

<td>

<?= htmlspecialchars($row['endpoint']) ?>

</td>

<td>

<?= htmlspecialchars($row['organization']) ?>

</td>

<td>

<?= $row['enabled'] ? 'Yes' : 'No' ?>

</td>

<td>

<?= htmlspecialchars($row['updated_at']) ?>

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