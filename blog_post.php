<?php
// ============================================================
// blog_post.php — Articolo singolo "Ask the Experts" (DINAMICO).
// Accetta ?id= oppure ?slug=. Rende: categoria, domanda (Q), Expert Answer,
// scaletta (outlines), FAQ (accordion nativo <details> + schema FAQPage) e un
// FORM di conversione a fine articolo (lead B2B). Markup/classi esistenti.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/libs/antispam.php';
require_once __DIR__ . '/includes/form_consent.php';
require_once __DIR__ . '/libs/blog.class.php';
require_once __DIR__ . '/libs/user_roles.class.php';

$blog = new BlogManager($pdo);

$id        = (int)($_GET['id'] ?? 0);
$slug      = trim((string)($_GET['slug'] ?? ''));
$viewer_id = is_user_logged_in() ? current_user_id() : null;
$is_admin  = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

$article = null;
if ($slug !== '') {
    $article = $blog->getPublishedBySlug($slug);
} elseif ($id > 0) {
    $article = $blog->getById($id, $viewer_id, $is_admin);
}
$aid = $article ? (int)$article['id'] : 0;

// Flash del form lead
$lead_ok  = !empty($_SESSION['blog_lead_ok']);
$lead_err = !empty($_SESSION['blog_lead_err']);
unset($_SESSION['blog_lead_ok'], $_SESSION['blog_lead_err']);

$page_title = $article ? $article['title'] : 'Blog post';
$aow_bp_desc = $article
    ? mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($article['excerpt'] ?? $article['question'] ?? $article['body'] ?? '')))), 0, 160)
    : 'Article from the All on Wheel expert knowledge base on special vehicles.';

$faq_items = $article ? BlogManager::faqItems($article['faq_json'] ?? null) : [];
$outline_items = $article ? BlogManager::outlineItems($article['outlines'] ?? null) : [];
$cat_name = $article ? $blog->categoryName($article['category'] ?? '') : '';
$aow_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$canon = 'blog_post.php?' . ($slug !== '' ? 'slug=' . rawurlencode($slug) : 'id=' . $aid);
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords" content="All on Wheel expert answer, <?php echo htmlspecialchars($cat_name, ENT_QUOTES); ?>" />
<meta name="description" content="<?php echo htmlspecialchars($aow_bp_desc, ENT_QUOTES); ?>" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<?php if ($article): ?>
<meta property="og:type" content="article" />
<meta property="og:title" content="<?php echo htmlspecialchars($page_title, ENT_QUOTES); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($aow_bp_desc, ENT_QUOTES); ?>" />
<meta property="og:url" content="<?php echo htmlspecialchars($aow_base . '/' . $canon, ENT_QUOTES); ?>" />
<?php
  // ---- Schema.org: Article (+ FAQPage se ci sono FAQ) ----
  $graph = [[
      '@type' => 'Article',
      'headline' => (string)$article['title'],
      'datePublished' => date('c', strtotime((string)($article['published_at'] ?? $article['created_at']))),
      'author' => ['@type' => 'Organization', 'name' => 'All on Wheel Ltd'],
      'publisher' => ['@type' => 'Organization', 'name' => 'All on Wheel Ltd'],
      'mainEntityOfPage' => $aow_base . '/' . $canon,
  ]];
  if ($faq_items) {
      $graph[] = [
          '@type' => 'FAQPage',
          'mainEntity' => array_map(static fn($it) => [
              '@type' => 'Question',
              'name' => $it['q'],
              'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']],
          ], $faq_items),
      ];
  }
  echo '<script type="application/ld+json">'
     . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
     . '</script>' . "\n";
?>
<?php endif; ?>

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php $seo_canonical = $canon; include __DIR__ . '/includes/seo_head.php'; ?>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>
 <div id="content_top">
  <div id="page_title">Expert Answer</div>
  <div id="search_box">
 <form action="<?php echo $base_url; ?>browse.php" method="get">
  <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="main"></div><div id="templatemo_content">
 <?php if (!$article): ?>
   <div class="post_box">
     <h2>Article not found</h2>
     <p>The article you are looking for does not exist or is not available.</p>
     <a href="blog.php" class="more">Back to Ask the Experts</a>
   </div>
 <?php else:
   $img    = trim((string)($article['image'] ?? ''));
   $author = trim((string)($article['username'] ?? '')) !== '' ? $article['username'] : 'All on Wheel';
   $author_expert = UserRoles::hasRolePdo($pdo, (int)($article['id_user'] ?? 0), 'expert');
   $paragraphs = preg_split('/\R{1,}/', trim((string)$article['body']));
 ?>
   <div class="post_box">
     <?php if ($cat_name !== ''): ?>
     <div class="badges"><a class="badge badge_type" href="blog.php?cat=<?php echo rawurlencode((string)$article['category']); ?>"><?php echo htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8'); ?></a></div>
     <?php endif; ?>
     <h2><?php echo htmlspecialchars($article['title']); ?></h2>
     <?php if ($article['status'] !== 'published'): ?>
       <p><em>(<?php echo htmlspecialchars($article['status']); ?> — visible only to you / the admin)</em></p>
     <?php endif; ?>
     <?php if (trim((string)($article['question'] ?? '')) !== ''): ?>
       <p><strong>The question:</strong></p>
       <p><em><?php echo htmlspecialchars($article['question']); ?></em></p>
     <?php endif; ?>
     <?php if ($img !== ''):
       $orig = '/upload_image/blog/original/' . rawurlencode($img);
     ?>
      <ul class="gallery">
      <li><a class="pirobox" href="<?php echo htmlspecialchars($orig, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
       <img src="<?php echo htmlspecialchars($orig, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>"
            width="220" height="150" border="0" loading="lazy" decoding="async" />
     </a></li>
    </ul>
     <?php endif; ?>

     <?php if ($outline_items): ?>
       <h3>In this answer</h3>
       <ul>
         <?php foreach ($outline_items as $o): ?>
         <li><?php echo htmlspecialchars($o, ENT_QUOTES, 'UTF-8'); ?></li>
         <?php endforeach; ?>
       </ul>
     <?php endif; ?>

     <?php if (trim((string)($article['excerpt'] ?? '')) !== ''): ?>
     <p><em><?php echo htmlspecialchars($article['excerpt']); ?></em></p>
     <?php endif; ?>

     <?php foreach ($paragraphs as $p): if (trim($p) === '') continue; ?>
     <p align="justify"><?php echo nl2br(htmlspecialchars($p)); ?></p>
     <?php endforeach; ?>

     <div class="post_meta">
       <span class="cat">
		   <strong>Posted by:</strong> <?php echo htmlspecialchars($author); ?><?php if ($author_expert): ?> <span class="badge">Expert</span><?php endif; ?>
       | <strong>Date: </strong><?php echo htmlspecialchars(date('j F Y', strtotime((string)($article['published_at'] ?? $article['created_at'])))); ?></span>
       <div class="float_r"><a href="blog.php" class="more">Back</a></div>
     </div>
   </div>

   <?php // ===== FAQ (schema + accordion nativo, nessun CSS nuovo) ===== ?>
   <?php if ($faq_items): ?>
   <div class="post_box">
     <h2>FAQ</h2>
     <?php foreach ($faq_items as $it): ?>
     <details>
       <summary><strong><?php echo htmlspecialchars($it['q'], ENT_QUOTES, 'UTF-8'); ?></strong></summary>
       <p align="justify"><?php echo nl2br(htmlspecialchars($it['a'], ENT_QUOTES, 'UTF-8')); ?></p>
     </details>
     <?php endforeach; ?>
   </div>
   <?php endif; ?>

   <?php // ===== Lead form B2B a fine articolo ===== ?>
   <div class="post_box" id="ask-the-experts">
     <h2>Still have questions? Talk to our engineers</h2>
     <p>Tell us about your project and get a tailored answer from the All on Wheel technical team.</p>
     <?php if ($lead_ok): ?>
       <p class="flash_ok"><strong>Thank you — your request has been sent. Our team will get back to you shortly.</strong></p>
     <?php elseif ($lead_err): ?>
       <p class="flash_err"><strong>We could not process your request. Please check the fields and the privacy consent, then try again.</strong></p>
     <?php endif; ?>
     <div id="contact_form">
       <form action="blog_lead_save.php" method="post" accept-charset="UTF-8">
         <?php echo csrf_generate(); ?>
         <?php echo aow_spam_fields(); ?>
         <input type="hidden" name="id_blog" value="<?php echo $aid; ?>" />
         <input type="hidden" name="category" value="<?php echo htmlspecialchars((string)($article['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />

         <p><label for="lead_intent">I would like to</label>
           <select name="intent" id="lead_intent">
             <option value="feasibility_study">Request a Feasibility Study</option>
             <option value="custom_quote">Get a Custom Quote</option>
             <option value="question" selected>Ask a technical question</option>
           </select>
         </p>
         <p><label for="lead_name">Full name *</label>
           <input type="text" name="name" id="lead_name" required /></p>
         <p><label for="lead_email">Business email *</label>
           <input type="email" name="email" id="lead_email" required /></p>
         <p><label for="lead_company">Company</label>
           <input type="text" name="company" id="lead_company" /></p>
         <p><label for="lead_phone">Phone</label>
           <input type="tel" name="phone" id="lead_phone" /></p>
         <p><label for="lead_message">Your project / question</label>
           <textarea name="message" id="lead_message" rows="4"></textarea></p>
         <?php echo aow_privacy_consent_field('/privacy.php'); ?>
         <p><button type="submit" name="submit" class="more btn_accent">Send request</button></p>
       </form>
     </div>
   </div>

   <div class="cleaner"></div>
<?php $id = $aid; include __DIR__ . '/blog_comments.php'; ?>
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
