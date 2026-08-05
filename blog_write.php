<?php
// ============================================================
// blog_write.php — Form di pubblicazione articolo (utenti registrati).
// Login obbligatorio. Invia a blog_save.php (CSRF + upload immagine).
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';

$id_user = require_user_logged_in();

require_once __DIR__ . '/libs/user_tier.class.php';
require_once __DIR__ . '/libs/plan_policy.class.php';
if (!PlanPolicy::canBlogPublish(UserTier::getTier($pdo, $id_user))) {
    $_SESSION['blog_error'] = 'Publishing blog articles is a Gold plan feature.';
    header('Location: /blog.php');
    exit;
}

$flash_err = $_SESSION['blog_error'] ?? '';
unset($_SESSION['blog_error']);
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Write an article</title>
<meta name="robots" content="noindex, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>
 <div id="content_top">
  <div id="page_title">Write an article</div>
  <div id="search_box">
 <form action="<?php echo $base_url; ?>browse.php" method="get">
  <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="main"></div><div id="templatemo_content">
  <?php if ($flash_err !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>

  <div id="contact_form">
   <form name="blogform" method="post" action="blog_save.php" enctype="multipart/form-data">
    <?php echo csrf_generate(); ?>

    <label for="title">Title (* required)</label>
    <input type="text" id="title" name="title" class="required input_field" maxlength="200" />

    <div class="cleaner h10"></div>
    <label for="excerpt">Short summary (optional)</label>
    <input type="text" id="excerpt" name="excerpt" class="input_field" maxlength="255" />

    <div class="cleaner h10"></div>
    <label for="body">Article (* required)</label>
    <textarea id="body" name="body" rows="12" cols="0" class="required"></textarea>

    <div class="cleaner h10"></div>
    <label for="image">Cover image (optional, JPG/PNG/GIF)</label>
    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" />

    <div class="cleaner h20"></div>
    <button type="submit" name="submit" id="submit" value="Publish" class="more float_r">Publish</button>
    <a href="blog.php" class="more float_l">Cancel</a>
   </form>
  </div>
 </div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
