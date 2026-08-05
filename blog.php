<?php
// ============================================================
// blog.php — Hub editoriale "Ask the Experts" (DINAMICO da tabella `blog`).
// Elenca gli articoli 'published' con filtro per categoria (chip-bar) e
// paginazione. Markup/classi invariati (post_box, badge, templatemo_paging);
// nessuno stile nuovo (dir. 8). Il filtro categoria e' DB-driven.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';

$blog = new BlogManager($pdo);

// Categoria selezionata (validata contro l'elenco reale)
$categories = $blog->categories();
$valid_slugs = array_column($categories, 'slug');
$cat = isset($_GET['cat']) ? (string)$_GET['cat'] : '';
if ($cat !== '' && !in_array($cat, $valid_slugs, true)) { $cat = ''; }
$cat_param = $cat !== '' ? $cat : null;

$per_page = 6;
$page     = max(1, (int)($_GET['page'] ?? 1));
$aow_lang = function_exists('aow_locale') ? aow_locale() : 'en';
$total    = $blog->countPublishedByLang($cat_param, $aow_lang);
$pages    = max(1, (int)ceil($total / $per_page));
if ($page > $pages) { $page = $pages; }
$offset   = ($page - 1) * $per_page;
$articles = $blog->listPublishedByLang($cat_param, $aow_lang, $per_page, $offset);

$flash_ok  = $_SESSION['blog_success'] ?? '';
$flash_err = $_SESSION['blog_error'] ?? '';
unset($_SESSION['blog_success'], $_SESSION['blog_error']);

// Helper per conservare il filtro nei link di paginazione
$qs = static function (array $extra) use ($cat): string {
    $p = array_filter(array_merge(['cat' => $cat], $extra), static fn($v) => $v !== '' && $v !== null);
    return $p ? '?' . http_build_query($p) : '';
};

// Primo paragrafo per l'anteprima
$first_paragraph = static function (string $body): string {
    $parts = preg_split('/\R{1,}/', trim($body));
    $p = $parts[0] ?? '';
    if (mb_strlen($p) > 300) { $p = mb_substr($p, 0, 300) . '…'; }
    return $p;
};
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Ask the Experts — All on Wheel Knowledge Base</title>
<meta name="description" content="Expert answers to technical B2B questions on special vehicles: EU type-approval, insulation for mobile clinics, buy-vs-rent cost analysis, registration and design. Ask the All on Wheel experts." />
<meta name="keywords" content="special vehicle experts, EU type-approval, mobile clinic insulation, buy vs rent trucks, vehicle registration, feasibility study, All on Wheel knowledge base" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php $seo_canonical = 'blog.php' . ($cat !== '' ? '?cat=' . rawurlencode($cat) : ''); include __DIR__ . '/includes/seo_head.php'; ?>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>

 <div id="content_top">
  <div id="page_title">Ask the Experts</div>
  <div id="search_box">
 <form action="<?php echo $base_url; ?>browse.php" method="get">
  <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="main"></div><div id="templatemo_content">

  <?php // ===== Chip-bar filtro categorie (classi badge esistenti) ===== ?>
  <div class="post_box">
    <p><strong>Knowledge base</strong> — expert answers to the technical questions B2B buyers ask before investing in a special vehicle.</p>
    <div class="badges" role="navigation" aria-label="Filter articles by category">
      <a class="badge <?php echo $cat === '' ? 'badge_premium' : 'badge_type'; ?>" href="blog.php">All topics</a>
      <?php foreach ($categories as $c):
        $active = ($cat === $c['slug']); ?>
      <a class="badge <?php echo $active ? 'badge_premium' : 'badge_type'; ?>"
         href="blog.php?cat=<?php echo rawurlencode($c['slug']); ?>"<?php echo $active ? ' aria-current="true"' : ''; ?>>
        <?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($flash_ok !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>
  <?php if ($flash_err !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>

  <?php if (empty($articles)): ?>
    <div class="post_box">
      <h2>No articles yet</h2>
      <p>There are no published articles in this topic yet.<?php echo is_user_logged_in() ? ' Be the first to write one!' : ''; ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($articles as $a):
      $img = trim((string)($a['image'] ?? ''));
      $img_url = $img !== '' ? '/upload_image/blog/original/' . rawurlencode($img) : 'images/templatemo_image_06.jpg';
      $author  = trim((string)($a['username'] ?? '')) !== '' ? $a['username'] : 'All on Wheel';
      $cat_name = $blog->categoryName($a['category'] ?? '');
      $slug_or_id = trim((string)($a['slug'] ?? '')) !== '' ? 'slug=' . rawurlencode($a['slug']) : 'id=' . (int)$a['id'];
    ?>
    <div class="post_box">
      <?php if ($cat_name !== ''): ?>
      <div class="badges"><span class="badge badge_type"><?php echo htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8'); ?></span></div>
      <?php endif; ?>
      <h2><?php echo htmlspecialchars($a['title']); ?></h2>
      <ul class="gallery">
      <li><a class="pirobox" href="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?>">
            <img src="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>"
           alt="<?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?>"
           width="220" height="150" border="0" loading="lazy" decoding="async" />
     </a></li>
    </ul>
      <?php if (trim((string)($a['question'] ?? '')) !== ''): ?>
      <p><strong>Q:</strong> <em><?php echo htmlspecialchars($a['question']); ?></em></p>
      <?php elseif (trim((string)($a['excerpt'] ?? '')) !== ''): ?>
      <p><em><?php echo htmlspecialchars($a['excerpt']); ?></em></p>
      <?php endif; ?>
      <p><?php echo htmlspecialchars($first_paragraph((string)$a['body'])); ?></p>
      <div class="post_meta">
        <span class="cat"><strong>Posted by:</strong> <?php echo htmlspecialchars($author); ?>
        | <strong>Date:</strong> <?php echo htmlspecialchars(date('j F Y', strtotime((string)($a['published_at'] ?? $a['created_at'])))); ?></span>
        <div class="float_r"><a href="blog_post.php?<?php echo $slug_or_id; ?>" class="more">Read the answer</a></div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($pages > 1): ?>
    <div class="templatemo_paging">
      <ul>
        <li><a href="<?php echo $page > 1 ? $qs(['page' => $page - 1]) : '#'; ?>">Previous</a></li>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li><a href="<?php echo $qs(['page' => $i]); ?>"<?php echo $i === $page ? ' class="active"' : ''; ?>><?php echo $i; ?></a></li>
        <?php endfor; ?>
        <li><a href="<?php echo $page < $pages ? $qs(['page' => $page + 1]) : '#'; ?>">Next</a></li>
      </ul>
      <div class="cleaner"></div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

</div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<?php include "footer.php"; ?>
</body>
</html>
