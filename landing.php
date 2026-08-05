<?php
// ============================================================
// landing.php — Landing di servizio B2B ad alta conversione.
//
// Riscritta sul design system esistente (hero, section, macro_grid/macro_card
// con micro-animazione hover, feature_row, cta_band): NESSUNO stile inline,
// NESSUN CSS nuovo (dir. 8), UI in inglese (dir. 0). Sostituisce la versione
// precedente con <style> inline e testi in italiano.
//
// CTA rivolte ai decision maker B2B: "Request a Feasibility Study" e
// "Get a Custom Quote" -> flusso RFQ (04_request_offer). Le card veicolo
// riusano le hero delle 5 macro (product_macros) con fallback, come la home.
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/libs/site_settings.class.php';
require_once __DIR__ . '/includes/aow_media.php';

$is_logged_in = is_user_logged_in();

// Hero della landing (admin via site_settings, con fallback versionato)
$aow_hero_img = aow_img_src(
    SiteSettings::get($pdo, 'hero_image', 'images/project.png'),
    'images/00_first/race_trailer.jpg'
);
$aow_hero_bg = aow_css_bg($aow_hero_img);

// Hero delle macro (come index.php): product_macros.hero_image con fallback.
$macro_hero = [];
try {
    foreach ($pdo->query('SELECT slug, hero_image FROM `product_macros`')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (!empty($r['hero_image'])) { $macro_hero[$r['slug']] = $r['hero_image']; }
    }
} catch (Throwable $e) { $macro_hero = []; }
$hero = static function (string $slug, string $fallback) use ($macro_hero): string {
    return $macro_hero[$slug] ?? $fallback;
};

$RFQ = '04_request_offer/04_request_offer.php';
$aow_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — Special vehicle engineering, built to your brief</title>
<meta name="description" content="B2B partner for special and paddock vehicles: race trailers, hospitality units, mobile clinics, shelters and custom projects. Request a feasibility study or a custom quote from specialist European builders." />
<meta name="keywords" content="special vehicle engineering, race trailer builder, motorsport hospitality unit, mobile clinic manufacturer, shelter container, custom vehicle project, feasibility study, custom quote, B2B" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link rel="canonical" href="<?php echo htmlspecialchars($aow_base, ENT_QUOTES); ?>/landing.php" />
<meta property="og:type" content="website" />
<meta property="og:title" content="All on Wheel — Special vehicle engineering, built to your brief" />
<meta property="og:description" content="Race trailers, hospitality, mobile clinics, shelters and custom projects. Request a feasibility study or a custom quote." />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
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
      <span class="hero_kicker">Special vehicle engineering</span>
      <h1>Built to your brief, delivered across Europe</h1>
      <p>From race trailers and hospitality units to mobile clinics, shelters and one-off custom builds — we turn a specification into a road-ready vehicle, with verified specialist builders behind every project.</p>
      <div class="hero_cta">
        <a href="<?php echo $RFQ; ?>?intent=feasibility_study" class="more btn_accent">Request a Feasibility Study</a>
        <a href="<?php echo $RFQ; ?>?intent=custom_quote" class="btn_ghost">Get a Custom Quote</a>
      </div>
    </div>
  </div>

  <!-- ===== Esplora i veicoli (micro-animazione hover su .macro_card) ===== -->
  <div class="section">
    <div class="section_head">
      <h2>Explore our <span>vehicle families</span></h2>
      <p>Hover a family to explore it — then request a study or a quote in one click.</p>
    </div>
    <div class="macro_grid">
      <?php
        // slug macro, immagine fallback (coerente con index.php), etichetta, pagina dedicata
        $cards = [
          ['race-trailer',     'images/00_first/race_trailer.jpg',                        'Race Trailers',       'race_trailers.php'],
          ['hospitality',      'images/00_first/Hospitality.jpg',                         'Hospitality',         'hospitality.php'],
          ['mobile-clinic',    'images/templatemo_image_05.jpg',                          'Mobile Clinics',      'mobile_clinics.php'],
          ['shelter-container','images/00_first/shelter_container.jpg',                   'Shelter & Container', 'shelter_container.php'],
          ['custom-projects',  'images/00_first/truck-a-truck-design-for-audi2.jpg',      'Custom Projects',     'custom_projects.php'],
        ];
        foreach ($cards as $c):
          $img = $hero($c[0], $c[1]);
      ?>
      <a class="macro_card" href="<?php echo htmlspecialchars($c[3], ENT_QUOTES, 'UTF-8'); ?>">
        <span class="mc_img" style="background-image:url('<?php echo aow_css_bg($img, $c[1]); ?>');"></span>
        <span class="mc_body"><h3><?php echo htmlspecialchars($c[2], ENT_QUOTES, 'UTF-8'); ?></h3><span>Explore &rsaquo;</span></span>
      </a>
      <?php endforeach; ?>
      <a class="macro_card" href="07_rent/07_20_rent_list.php">
        <span class="mc_img" style="background-image:url('<?php echo aow_css_bg('images/rent_tb.jpg'); ?>');"></span>
        <span class="mc_body"><h3>Rental fleet</h3><span>Explore &rsaquo;</span></span>
      </a>
    </div>
  </div>

  <!-- ===== Come lavoriamo (value props B2B) ===== -->
  <div class="section">
    <div class="section_head">
      <h2>From brief to <span>road-ready</span></h2>
      <p>A single partner across the whole build.</p>
    </div>
    <div class="feature_row">
      <div class="feature"><div class="fi">1</div><h3>Feasibility</h3><p>We assess your brief, payload, homologation path and budget before anything is built — no surprises later.</p></div>
      <div class="feature"><div class="fi">2</div><h3>Design &amp; quote</h3><p>Technical layout, materials and a transparent custom quote, matched to EU type-approval requirements.</p></div>
      <div class="feature"><div class="fi">3</div><h3>Build &amp; deliver</h3><p>Manufacturing by verified specialist builders, with delivery and registration support across Europe.</p></div>
    </div>
  </div>

  <!-- ===== Prova sociale / directory ===== -->
  <div class="section">
    <div class="feature_row">
      <a class="feature" href="06_company/06_30_company_directory.php"><div class="fi">&#10003;</div><h3>Verified suppliers</h3><p>A curated B2B directory of specialist bodybuilders and service providers.</p></a>
      <a class="feature" href="browse.php"><div class="fi">&#128269;</div><h3>Live marketplace</h3><p>Browse in-stock special vehicles and bodies, or list your own to reach qualified buyers.</p></a>
      <a class="feature" href="blog.php"><div class="fi">&#9733;</div><h3>Ask the Experts</h3><p>Technical answers on type-approval, insulation, buy-vs-rent and registration — before you commit.</p></a>
    </div>
  </div>

  <!-- ===== CTA band ===== -->
  <div class="section">
    <div class="cta_band">
      <div>
        <h2>Have a project in mind?</h2>
        <p>Send us your specification and get a feasibility study or a custom quote from our engineers.</p>
      </div>
      <div><a href="<?php echo $RFQ; ?>?intent=feasibility_study" class="more btn_accent">Request a Feasibility Study</a></div>
    </div>
  </div>

</div><!-- /#no_sidebar -->
<?php include 'footer.php'; ?>
</body>
</html>
