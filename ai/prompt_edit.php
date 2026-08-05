<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/PromptRepository.php';

require_user_logged_in();

$repository = new PromptRepository($pdo);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$prompt = $repository->find($id);

if ($prompt === null) {

    header('Location: prompts.php');
    exit;

}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $prompt->setName(
        trim($_POST['name'])
    );

    $prompt->setCategory(
        trim($_POST['category'])
    );

    $prompt->setModel(
        trim($_POST['model'])
    );

    $prompt->setTemperature(
        (float)$_POST['temperature']
    );

    $prompt->setPrompt(
        trim($_POST['prompt'])
    );

    $repository->save($prompt);

    $message = 'Prompt updated.';

}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(),ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>Prompt Editor</title>

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

Prompt Editor

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

<input
type="hidden"
name="id"
value="<?= $prompt->getId() ?>">

<label>Name</label>

<input
type="text"
name="name"
class="input_field"
value="<?= htmlspecialchars($prompt->getName()) ?>">

<div class="cleaner h10"></div>

<label>Category</label>

<input
type="text"
name="category"
class="input_field"
value="<?= htmlspecialchars($prompt->getCategory()) ?>">

<div class="cleaner h10"></div>

<label>Model</label>

<input
type="text"
name="model"
class="input_field"
value="<?= htmlspecialchars($prompt->getModel()) ?>">

<div class="cleaner h10"></div>

<label>Temperature</label>

<input
type="text"
name="temperature"
class="input_field"
value="<?= htmlspecialchars((string)$prompt->getTemperature()) ?>">

<div class="cleaner h10"></div>

<label>Prompt</label>

<textarea
name="prompt"
rows="20"
cols="0"
class="required"><?= htmlspecialchars($prompt->getPrompt()) ?></textarea>

<div class="cleaner h20"></div>

<button
class="more float_r"
type="submit">

Save

</button>

<a
class="more float_l"
href="prompts.php">

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