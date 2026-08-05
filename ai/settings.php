<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_user_logged_in();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $settings = [

        'default_model',

        'temperature',

        'max_tokens',

        'auto_publish',

        'auto_translate',

        'auto_faq',

        'auto_schema',

        'auto_internal_links',

        'auto_image',

        'scheduler_enabled',

        'max_jobs',

        'default_language'

    ];

    $stmt = $pdo->prepare(

        "REPLACE INTO ai_settings
        (
            setting_key,
            setting_value
        )
        VALUES
        (
            :k,
            :v
        )"

    );

    foreach ($settings as $key) {

        $value = $_POST[$key] ?? '';

        $stmt->execute([

            ':k' => $key,

            ':v' => is_array($value)
                ? json_encode($value)
                : (string)$value

        ]);

    }

    $message = 'Settings saved.';

}

$rows = $pdo->query(

    "SELECT
        setting_key,
        setting_value
     FROM ai_settings"

)->fetchAll(PDO::FETCH_KEY_PAIR);

function setting(array $rows,string $key,string $default=''):string
{
    return $rows[$key] ?? $default;
}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>AI Settings</title>

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

AI Settings

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

<?= csrf_generate() ?>

<label>Default Model</label>

<input
class="input_field"
type="text"
name="default_model"
value="<?= htmlspecialchars(setting($rows,'default_model','gpt-5.5')) ?>">

<div class="cleaner h10"></div>

<label>Temperature</label>

<input
class="input_field"
type="text"
name="temperature"
value="<?= htmlspecialchars(setting($rows,'temperature','0.7')) ?>">

<div class="cleaner h10"></div>

<label>Max Tokens</label>

<input
class="input_field"
type="text"
name="max_tokens"
value="<?= htmlspecialchars(setting($rows,'max_tokens','4096')) ?>">

<div class="cleaner h10"></div>

<label>Default Language</label>

<input
class="input_field"
type="text"
name="default_language"
value="<?= htmlspecialchars(setting($rows,'default_language','en')) ?>">

<div class="cleaner h20"></div>

<label>

<input
type="checkbox"
name="auto_publish"
value="1"
<?= setting($rows,'auto_publish')=='1'?'checked':''; ?>>

Automatic Publish

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="auto_translate"
value="1"
<?= setting($rows,'auto_translate')=='1'?'checked':''; ?>>

Automatic Translation

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="auto_faq"
value="1"
<?= setting($rows,'auto_faq')=='1'?'checked':''; ?>>

Generate FAQ

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="auto_schema"
value="1"
<?= setting($rows,'auto_schema')=='1'?'checked':''; ?>>

Generate Schema.org

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="auto_internal_links"
value="1"
<?= setting($rows,'auto_internal_links')=='1'?'checked':''; ?>>

Generate Internal Links

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="auto_image"
value="1"
<?= setting($rows,'auto_image')=='1'?'checked':''; ?>>

Generate Images

</label>

<div class="cleaner h10"></div>

<label>

<input
type="checkbox"
name="scheduler_enabled"
value="1"
<?= setting($rows,'scheduler_enabled')=='1'?'checked':''; ?>>

Enable Scheduler

</label>

<div class="cleaner h20"></div>

<label>Maximum Concurrent Jobs</label>

<input
class="input_field"
type="text"
name="max_jobs"
value="<?= htmlspecialchars(setting($rows,'max_jobs','2')) ?>">

<div class="cleaner h20"></div>

<button
type="submit"
class="more float_r">

Save

</button>

<a
class="more float_l"
href="dashboard.php">

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