<?php
// ============================================================
// index.php — Homepage All on Wheel
//
// Revisione UX:
//  - Rimosso session_start() manuale + check $_SESSION['session_id']
//    diretto: sostituiti con session_helper (is_user_logged_in).
//  - Aggiunta sezione hero con CTA chiare per il visitatore:
//    "Browse ads" e "Find a supplier".
//  - Per gli utenti loggati: saluto personalizzato + CTA "My posts".
//  - Rimossi i placeholder Lorem Ipsum nella sezione News.
//  - v0.0.12: home riallineata al brand V0.0.11 — 70% motorsport (5 macro
//    ufficiali product_macros) / 30% B2B veicoli commerciali e speciali.
//  - CTA verso pagine reali (browse.php?macro=, road/special); rimossi i
//    link a 00_first/ (legacy in dismissione). Nessun asset in images/ toccato.
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/config/database.php';

// Gallery homepage: ultime 4 immagini caricate dalle ultime 4 ads
// (free + premium unificati, ordinate per data di creazione decrescente;
// escluse le voci placeholder). Degrada a vuoto in caso di errore DB.
$gallery_ads = [];
try {
    $sql = "
      SELECT id_ads, title, image_original, image_thumbnail, created_at,
             '/upload_image/02_free_ads/' AS upload_path
      FROM `02_free_ads`
      WHERE status = 'approved' AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
      UNION ALL
      SELECT id_ads, title, image_original, image_thumbnail, created_at,
             '/upload_image/03_ads/' AS upload_path
      FROM `03_ads`
      WHERE status = 'approved' AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
      ORDER BY created_at DESC, id_ads DESC
      LIMIT 4
    ";
    $gallery_ads = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] index gallery query error: ' . $e->getMessage());
    $gallery_ads = [];
}

// Immagine hero dell'index: gestita dall'admin via site_settings (20 lug 2026).
// 27 lug 2026: il valore salvato in site_settings veniva stampato senza
// verificare il file. In produzione l'upload non era stato caricato e la home
// apriva su un blocco vuoto (404 sull'immagine di apertura). Ora il path viene
// risolto con fallback a un asset versionato nel repository.
require_once __DIR__ . '/libs/site_settings.class.php';
require_once __DIR__ . '/includes/aow_media.php';
$aow_hero_img = aow_img_src(
    SiteSettings::get($pdo, 'hero_image', 'images/project.png'),
    'images/00_first/race_trailer.jpg'
);
$aow_hero_bg = aow_css_bg($aow_hero_img);

// Hero delle macro: usa product_macros.hero_image se valorizzata, con fallback.
$macro_hero = [];
try {
    foreach ($pdo->query('SELECT slug, hero_image FROM `product_macros`')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (!empty($r['hero_image'])) { $macro_hero[$r['slug']] = $r['hero_image']; }
    }
} catch (Throwable $e) { $macro_hero = []; }
$hero = static function (string $slug, string $fallback) use ($macro_hero): string {
    return $macro_hero[$slug] ?? $fallback;
};

$is_logged_in     = is_user_logged_in();
$current_username = $is_logged_in ? current_username() : '';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — <?php te('home.meta_title','Premium motorsport paddock vehicles & special bodies'); ?></title>
<meta name="title" content="All on Wheel — Premium motorsport paddock vehicles &amp; special bodies" />
<meta name="description" content="Premium marketplace for the motorsport paddock: race trailers, hospitality units, mobile clinics, shelters &amp; containers and custom projects. Plus a B2B directory of commercial and special vehicle bodies and specialist bodybuilders. Browse listings or request a quotation." />
<?php $aow_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<link rel="canonical" href="<?php echo $aow_base; ?>/" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="All on Wheel" />
<meta property="og:title" content="All on Wheel &mdash; Motorsport paddock &amp; special vehicles" />
<meta property="og:description" content="Race trailers, hospitality, mobile clinics, shelters and custom projects, plus a B2B directory of specialist bodybuilders." />
<meta property="og:url" content="<?php echo $aow_base; ?>/" />
<meta property="og:image" content="<?php echo $aow_base; ?>/images/00_first/race_trailer.jpg" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="keywords" content="race trailer, paddock trailer, motorsport hospitality unit, mobile clinic, shelter container, custom motorsport projects, commercial vehicle bodies, special vehicles, bodybuilder, supplier directory, request a quotation" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="7" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"Organization","@id":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/#org","name":"All on Wheel Ltd","url":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/","logo":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/images/brand/logo.png","sameAs":["https://www.facebook.com/profile.php?id=61590545821976","https://www.instagram.com/allonwheel/"]},{"@type":"WebSite","@id":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/#website","url":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/","name":"All on Wheel","publisher":{"@id":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/#org"},"potentialAction":{"@type":"SearchAction","target":{"@type":"EntryPoint","urlTemplate":"<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/browse.php?q={search_term_string}"},"query-input":"required name=search_term_string"}}]}</script>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<?php // LCP: l'hero e' l'elemento piu' grande della home, il browser lo scopre
      // solo dopo aver letto il CSS. Il preload lo anticipa. ?>
<link rel="preload" as="image" href="<?php echo $aow_hero_bg; ?>" fetchpriority="high" />
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

<div id="templatemo_header">
  <?php $page_has_own_h1 = true; include 'header.php'; ?>
</div>

<div id="main"></div><div id="no_sidebar">

  <!-- ===== HERO ===== -->
  <div class="hero">
    <div class="hero_bg" style="background-image:url('<?php echo $aow_hero_bg; ?>');"></div>
    <div class="hero_inner">
      <span class="hero_kicker"><?php te('home.hero_kicker','Motorsport paddock & special vehicles'); ?></span>
      <?php if ($is_logged_in && $current_username !== ''): ?>
        <h1><?php te('home.hero_back','Welcome back'); ?>, <?php echo htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8'); ?></h1>
      <?php else: ?>
        <h1><?php te('home.hero_h','Buy, sell and rent special vehicles'); ?></h1>
      <?php endif; ?>
      <p><?php te('home.hero_p','A curated marketplace and verified supplier directory for race trailers, hospitality units, mobile clinics, shelters and custom projects across Europe.'); ?></p>
      <div class="hero_cta">
        <a href="browse.php" class="more btn_accent"><?php te('home.hero_cta1','Browse the marketplace'); ?></a>
        <a href="04_request_offer/04_request_offer.php" class="btn_ghost"><?php te('home.hero_cta2','Request a quotation'); ?></a>
        <?php if ($is_logged_in): ?><a href="01_login/my_posts.php" class="btn_ghost"><?php te('home.dashboard','Dashboard'); ?></a><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ===== Macro motorsport (5 famiglie) ===== -->
  <div class="section">
    <div class="section_head">
      <h2><?php te('home.fam_h','Special solutions for your job'); ?></h2>
      <p><?php te('home.fam_sub','Five families engineered.'); ?></p>
    </div>
    <div class="macro_grid">
      <?php
        // Dir. 21: ogni famiglia ha una PAGINA dedicata -> la card ci punta
        // direttamente (5o elemento), evitando un 301 inutile via ?macro=.
        $cards = [
          ['race-trailer','images/00_first/race_trailer.jpg','home.race_h','Race Trailer','race_trailers.php'],
          ['hospitality','images/00_first/Hospitality.jpg','home.hosp_h','Hospitality','hospitality.php'],
          ['mobile-clinic','images/templatemo_image_05.jpg','home.clinic_h','Mobile Clinic','mobile_clinics.php'],
          ['shelter-container','images/00_first/shelter_container.jpg','macro.shelter','Shelter & Container','shelter_container.php'],
          ['custom-projects','images/00_first/truck-a-truck-design-for-audi2.jpg','home.custom_h','Custom Projects','custom_projects.php'],
        ];
        foreach ($cards as $c):
          $img = $hero($c[0], $c[1]);
      ?>
      <a class="macro_card" href="<?php echo htmlspecialchars($c[4], ENT_QUOTES, 'UTF-8'); ?>">
        <span class="mc_img" style="background-image:url('<?php echo aow_css_bg($img, $c[1]); ?>');"></span>
        <span class="mc_body"><h3><?php te($c[2], $c[3]); ?></h3><span><?php te('home.view','View'); ?> &rsaquo;</span></span>
      </a>
      <?php endforeach; ?>
      <a class="macro_card" href="browse.php">
        <span class="mc_img" style="background-image:url('images/00_first/annunci.jpg');"></span>
        <span class="mc_body"><h3><?php te('home.all_h','All listings'); ?></h3><span><?php te('home.browse','Browse'); ?> &rsaquo;</span></span>
      </a>
    </div>
  </div>

  <!-- ===== Value props ===== -->
  <div class="section">
    <div class="feature_row">
      <a class="feature" href="06_company/06_30_company_directory.php"><div class="fi">&#10003;</div><h3><?php te('home.vp1_h','Verified suppliers'); ?></h3><p><?php te('home.vp1_p','A curated B2B directory of specialist bodybuilders and service providers.'); ?></p></a>
      <a class="feature" href="07_rent/07_20_rent_list.php"><div class="fi">&#128663;</div><h3><?php te('home.rent_h','Vehicle rental'); ?></h3><p><?php te('home.rent_p','Rent special vehicles, or list yours for rent and receive requests from users.'); ?></p></a>
      <a class="feature" href="blog.php"><div class="fi">&#9733;</div><h3><?php te('home.vp3_h','Experts answer'); ?></h3><p><?php te('home.vp3_p','The expert carefully analyzes user inquiries regarding technical issues to provide accurate, step-by-step troubleshooting solutions.'); ?></p></a>
    </div>
  </div>
	
  <!-- ===== B2B veicoli commerciali e speciali ===== -->
  <div class="section">
    <div class="section_head">
      <h2><?php te('home.b2b_h','Commercial & special vehicle bodies'); ?></h2>
      <p><?php te('home.b2b_sub','Road and special bodies, plus experts and shelters.'); ?></p>
    </div>
    <div class="macro_grid">
      <a class="macro_card" href="road_vehicles.php"><span class="mc_img" style="background-image:url('images/00_first/road_vehicles.JPG');"></span><span class="mc_body"><h3><?php te('b2b.road','Road vehicles'); ?></h3><span><?php te('home.explore','Explore'); ?> &rsaquo;</span></span></a>
      <a class="macro_card" href="special_vehicles.php"><span class="mc_img" style="background-image:url('<?php echo aow_css_bg('images/special_vehicle.png'); ?>');"></span><span class="mc_body"><h3><?php te('b2b.special','Special vehicles'); ?></h3><span><?php te('home.explore','Explore'); ?> &rsaquo;</span></span></a>
      <a class="macro_card" href="professionals.php"><span class="mc_img" style="background-image:url('images/00_first/Notizie_tecniche.jpg');"></span><span class="mc_body"><h3><?php te('nav.professionals','Professionals'); ?></h3><span><?php te('home.explore','Explore'); ?> &rsaquo;</span></span></a>
    </div>
  </div>

  <!-- ===== CTA band ===== -->
  <div class="section">
    <div class="cta_band">
      <div>
        <h2><?php te('home.cta_h','Sell on All on Wheel'); ?></h2>
        <p><?php te('home.cta_p','List your vehicles or register your company in the supplier directory.'); ?></p>
      </div>
      <div><a href="<?php echo $is_logged_in ? '01_login/my_posts.php' : '01_login/newregister.php'; ?>" class="more btn_accent"><?php te('home.cta_btn','Get started'); ?></a></div>
    </div>
  </div>

  <!-- ===== Ultimi annunci ===== -->
  <?php if (!empty($gallery_ads)): ?>
  <div class="section">
    <div class="section_head"><h2><?php te('home.latest_h','Latest from the marketplace'); ?></h2></div>
    <div class="listing_grid">
      <?php foreach ($gallery_ads as $g):
        $thumb = $g['upload_path'] . 'thumbnail/' . $g['image_thumbnail'];
        $g_title = trim((string)$g['title']) !== '' ? $g['title'] : 'Listing';
      ?>
<?php
  // La card portava a browse.php (la lista intera): un clic su un annuncio
  // preciso finiva sul catalogo, non sull'annuncio. Ora punta alla scheda.
  $g_url = ($g['upload_path'] === '/upload_image/03_ads/' ? '03_ads/03_view_ad.php' : '02_free_ads/02_view_ad.php')
         . '?id_ads=' . (int)$g['id_ads'];
?>
<div class="listing_card">
    <a href="<?php echo htmlspecialchars($g_url, ENT_QUOTES, 'UTF-8'); ?>">
        <span class="lc_img" style="background-image:url('<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>');"></span>
    </a>
    <p class="listing_title"><a href="<?php echo htmlspecialchars($g_url, ENT_QUOTES, 'UTF-8'); ?>">&nbsp;&nbsp;<?php echo htmlspecialchars($g_title, ENT_QUOTES, 'UTF-8'); ?></a></p>
</div>
      <?php endforeach; ?>
    </div>
    <div class="cleaner h20"></div>
    <a class="more float_r" href="portfolio.php"><?php te('nav.portfolio','Portfolio'); ?></a>
    <div class="cleaner"></div>
  </div>
  <?php endif; ?>
	
</div><!-- /#no_sidebar -->
<?php include 'footer.php'; ?>
</body>
</html>