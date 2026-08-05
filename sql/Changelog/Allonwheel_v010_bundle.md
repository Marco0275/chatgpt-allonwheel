# Allonwheel v0.0.10 — Bundle delta (Histats + quick win UX)
*2026-06-13 — Lint PHP 8.3 OK · JS valido · CRLF (LF sui .js). `includes/histats.php` è NUOVO.*

> Histats: imposta `HISTATS_ID` (costante/env/fallback). Banner cookie ora sito-wide via footer. Badge `browse.php` resi conformi a dir. 8.

## `header.php`
```php
<?php
// ============================================================
// header.php — Header globale del sito (menu di navigazione)
//
// Revisione UX (v0.0.9 - allineamento al flowchart reale, dir. 17/18):
//  - Macro-aree per intento: Marketplace, Suppliers, Portfolio, About.
//  - Marketplace: All listings / Free ads / Premium ads / Request a quotation.
//  - Suppliers: directory + Road vehicles / Special vehicles / Shelter & Container.
//  - Rimosse le voci motorsport legacy (cartella 00_first) dal menu.
//  - Header SOLO navigazione pubblica: identico per ospite e loggato.
//  - I link personali e il login vivono nelle sidebar di sezione
//    (sidebar_user_box.php), non piu' nell'header (dir. 17 rev.4).
// ============================================================

require_once __DIR__ . '/config/session_helper.php';

// ----- Base path automatico -----
$base_url = '';
$script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', '_admin', 'shared'] as $f) {
    if (strpos($script, '/' . $f . '/') !== false) {
        $base_url = '../';
        break;
    }
}

// Variabili di stato (mantenute per retro-compatibilita' delle pagine che le
// leggono dopo l'include; il menu pubblico NON le usa piu', dir. 17 rev.4).
$is_logged_in     = is_user_logged_in();
$current_username = $is_logged_in ? current_username() : '';
$is_admin         = $is_logged_in && isset($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';
?>
<div id="site_title">
  <h1><a href="<?php echo $base_url; ?>index.php" aria-label="All on Wheel - home"></a></h1>
</div>

<div id="templatemo_menu" class="ddsmoothmenu">
<ul>

  <!-- Home -->
  <li><a href="<?php echo $base_url; ?>index.php">Home</a></li>

  <!-- Marketplace (flowchart: Free Ads / Premium Ads / Request quotation) -->
  <li><a href="<?php echo $base_url; ?>browse.php">Marketplace</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
      <li><a href="<?php echo $base_url; ?>02_free_ads/02_view_ads.php">Free ads</a></li>
      <li><a href="<?php echo $base_url; ?>03_ads/03_view_ads.php">Premium ads</a></li>
      <li><a href="<?php echo $base_url; ?>04_request_offer/04_request_offer.php">Request a quotation</a></li>
    </ul>
  </li>

  <!-- Suppliers (flowchart: Company / Project manager -> Vehicle types -> Road / Special) -->
  <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Suppliers</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
      <li><a href="<?php echo $base_url; ?>road_vehicles.php">Road vehicles</a></li>
      <li><a href="<?php echo $base_url; ?>special_vehicles.php">Special vehicles</a></li>
      <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    </ul>
  </li>

  <!-- Portfolio -->
  <li><a href="<?php echo $base_url; ?>portfolio.php">Portfolio</a></li>

  <!-- About — solo contenuti editoriali -->
  <li><a href="<?php echo $base_url; ?>about.php">About</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>about.php">Our story</a></li>
      <li><a href="<?php echo $base_url; ?>what_we_do.php">What we do</a></li>
      <li><a href="<?php echo $base_url; ?>blog.php">Blog</a></li>
      <li><a href="<?php echo $base_url; ?>FAQ.php">F.A.Q.</a></li>
      <li><a href="<?php echo $base_url; ?>Conditions.php">Conditions &amp; rules</a></li>
      <li><a href="<?php echo $base_url; ?>contact.php">Contact us</a></li>
    </ul>
  </li>

</ul>
<br style="clear: left" />
</div>
```

## `index.php`
```php
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
//  - Conservati tutti i post_box di categoria (Racing, Box trailer, …)
//    che danno corpo alla homepage.
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

$is_logged_in     = is_user_logged_in();
$current_username = $is_logged_in ? current_username() : '';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — Commercial &amp; special vehicle bodies marketplace</title>
<meta name="title" content="All on Wheel — Commercial &amp; special vehicle bodies marketplace" />
<meta name="description" content="B2B marketplace for commercial vehicle bodies and special vehicles: refrigerated and insulated bodies, tippers, box vans, ambulances, tow trucks, shelters and more. Find specialist bodybuilders and suppliers in our directory, or request a quotation." />
<meta name="keywords" content="commercial vehicle bodies, special vehicles, refrigerated body, insulated body, tipper, box van, ambulance, tow truck, shelter container, bodybuilder, supplier directory" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="7" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/piroBox.1_2.js"></script>
<script type="text/javascript" src="js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">

<div id="templatemo_header">
  <?php include 'header.php'; ?>
</div>

<div id="content_top">
  <div id="page_title">
    <?php if ($is_logged_in && $current_username !== ''): ?>
      Welcome back, <?php echo htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8'); ?>!
    <?php else: ?>
     Marketplace for special vehicles
   <?php endif; ?>
  </div>
  <div id="search_box">
    <form action="" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="Search listings" placeholder="Search…" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
  </div>
  <div class="cleaner"></div>
</div>

<div id="no_sidebar">

  <!-- ===== Hero: CTA principali ===== -->
  <div class="col_3">
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/welcome.jpg" title="All on Wheel">
        <img src="images/00_first/welcome.jpg" alt="All on Wheel" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <div class="cleaner h10"></div>
    <?php if ($is_logged_in): ?>
      <h2>Profile</h2>
      <p>Ready to manage your listings or find new vehicles?</p>
      <a href="01_login/my_posts.php" class="more">Dashboard</a>
    <?php else: ?>
      <h2>All on Wheel is a...</h2>
      <p>... platform dedicated to the world of specialty and professional vehicles, where users and companies can post ads, find unique vehicles, and create new business opportunities.
It offers targeted visibility, an international community, and a reliable environment for those working in the motor and mobile equipment industry.</p>
      <a href="01_login/newlogin.php" class="more">Login</a>
    <?php endif; ?>
  </div>

  <!-- ===== Ads / Marketplace ===== -->
  <div class="col_3">
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/annunci.jpg" title="Marketplace">
        <img src="images/00_first/annunci.jpg" alt="Marketplace" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <div class="cleaner h10"></div>
    <h2>Marketplace</h2>
    <p>Browse hundreds of listings for special vehicles  from single-car race
       transporters to full hospitality suites. Filter by type, condition and price.</p>
    <ul class="templatemo_list">
      <li><a href="02_free_ads/02_view_ads.php">Free ads</a> — open to all registered users</li>
      <li><a href="03_ads/03_view_ads.php">Premium ads</a> — detailed specs &amp; full gallery</li>
    </ul>
    <a href="browse.php" class="more">Browse</a></div>

  <!-- ===== Supplier directory ===== -->
  <div class="col_3 rmc">
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/Notizie_tecniche.jpg" title="Suppliers">
        <img src="images/00_first/Notizie_tecniche.jpg" alt="Suppliers" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <div class="cleaner h10"></div>
    <h2>Supplier directory</h2>
    <p>Find specialist manufacturers, bodybuilders and service providers.
       Our directory lists verified companies across Europe and beyond.</p>
    <ul class="templatemo_list">
      <li>Coachbuilders &amp; bodybuilders</li>
      <li>Electrical &amp; HVAC specialists</li>
      <li>Lift &amp; loading equipment</li>
    </ul>
    <a href="06_company/06_30_company_directory.php" class="more">View</a>
  </div>

  <div class="cleaner h20"></div>

  <!-- ===== Vehicle categories ===== -->
  <div class="post_box">
    <h2>Roadshow vehicles</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/Roadshow.PNG" title="Roadshow">
        <img src="images/00_first/Roadshow.PNG" alt="Roadshow" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Mobile brand experiences that travel to your audience.</em></p>
    <p>Roadshow vehicles bring your brand directly to customers — custom-fitted showrooms,
       product launch units and experiential spaces built on truck or trailer bases.
       Whether you need a one-off for a global tour or a fleet for regional activations,
       All on Wheel connects you with the right builder.</p>
    <div class="post_meta"><a href="00_first/roadshow.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="post_box">
    <h2>Sell or rent your vehicle</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/rent_sale_tb.jpg" title="Sell or rent">
        <img src="images/00_first/rent_sale_tb.jpg" alt="Sell or rent" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Earn from your vehicle between events.</em></p>
    <p>Renting your transporter or hospitality unit during the off-season — or between
       events — turns a standing cost into revenue. Post a free ad in minutes and reach
       buyers and renters across the motorsport and events industry.</p>
    <div class="post_meta"><a href="00_first/sell_or_rent.php" class="more float_r" style="margin-right:8px;">Learn more</a>
    </div>
  </div>

  <div class="post_box">
    <h2>Racing trailers</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/Racing_trailer-removebg-preview.png" title="Racing trailer">
        <img src="images/00_first/Racing_trailer-removebg-preview.png" alt="Racing trailer" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>The complete paddock solution for professional race teams.</em></p>
    <p>A race transporter is divided into three main spaces — garage, workshop and office —
       and is equipped with electrical and HVAC systems, telemetry connections, a tail lift
       and belly storage. Whether you need a two-car deck or a three-car configuration with
       demountable upper deck, the All on Wheel marketplace lists current offers from sellers
       and rental operators worldwide.</p>
    <div class="post_meta"><a href="00_first/racing_trailer.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="post_box">
    <h2>Box trailers</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/paddock_trailer_tg.jpg" title="Box trailer">
        <img src="images/00_first/paddock_trailer_tg.jpg" alt="Box trailer" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Single-car support with full workshop access.</em></p>
    <p>Box trailers are the backbone of single-car and customer racing programmes. Compact
       enough for national championships, they can be configured with telemetry bays,
       meeting rooms and full workshop equipment. Browse current listings or post your own
       in the All on Wheel free-ads section.</p>
    <div class="post_meta"><a href="00_first/box_trailer.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="post_box">
    <h2>Motorhomes &amp; Mobilhomes</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/motorhome.jpg" title="Motorhome">
        <img src="images/00_first/motorhome.jpg" alt="Motorhome" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Home, sweet home everywhere — built for the long season.</em></p>
    <p>A luxury motorhome on a cab-chassis base removes the layout limitations of a
       traditional coach and allows the creation of a fully bespoke living space.
       Crew quarters, driver suites and team hospitality all in one unit — browse
       available vehicles or connect with a specialist builder in our supplier directory.</p>
    <div class="post_meta"><a href="00_first/motorhome_mobilhome.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="post_box">
    <h2>Hospitality units</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/1Portada-Unit-Be-hospitality.jpg" title="Hospitality">
        <img src="images/00_first/1Portada-Unit-Be-hospitality.jpg" alt="Hospitality" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Your brand on the road — demountable lounges and brand experiences.</em></p>
    <p>As brands focus increasingly on emotional engagement, a hospitality trailer becomes
       a mobile statement. Demountable lounges, roof terraces and catering kitchens allow
       a full paddock-club experience at any venue. All on Wheel lists new and used units
       and connects buyers with the coachbuilders who make them.</p>
    <div class="post_meta"><a href="00_first/hospitality.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="post_box">
    <h2>Paddock trailers</h2>
    <ul class="gallery">
      <li><a class="pirobox" href="images/00_first/truck-a-truck-design-for-audi2.jpg" title="Paddock trailer">
        <img src="images/00_first/truck-a-truck-design-for-audi2.jpg" alt="Paddock trailer" width="220" height="150" border="0" />
      </a></li>
    </ul>
    <p><em>Multi-storey paddock structures for the world's top championships.</em></p>
    <p>Modern paddock trailers are multi-storey constructions housing office space, driver
       areas, dining rooms, kitchens and sponsors' lounges. The pinnacle of coachbuilding,
       they are found at the front of every elite paddock. All on Wheel lists both new
       builds and second-hand units, and connects teams with certified bodybuilders.</p>
    <div class="post_meta"><a href="00_first/paddock_trailer.php" class="more float_r">Learn more</a></div>
  </div>

  <div class="gallery_box">
    <h2>Gallery</h2>
    <ul class="gallery">
      <?php if (empty($gallery_ads)): ?>
        <li><em>No images uploaded yet.</em></li>
      <?php else: foreach ($gallery_ads as $g):
        $thumb = $g['upload_path'] . 'thumbnail/' . $g['image_thumbnail'];
        $orig  = ($g['image_original'] !== '' && $g['image_original'] !== 'no_image.jpg')
               ? $g['upload_path'] . 'original/' . $g['image_original']
               : $thumb;
        $g_title = trim((string)$g['title']) !== '' ? $g['title'] : 'Image';
      ?>
      <li><a class="pirobox" href="<?php echo htmlspecialchars($orig, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($g_title, ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($g_title, ENT_QUOTES, 'UTF-8'); ?>" width="220" height="150" border="0" onerror="this.onerror=null;this.src='images/no_image.jpg';" /></a></li>
      <?php endforeach; endif; ?>
    </ul>
    <div class="post_meta"><a class="more float_r" href="portfolio.php">Portfolio</a></div>
  </div>
  <!-- ===== 4-col highlights ===== -->
  <div class="col_4">
    <h3>Race transporters</h3>
    <img src="images/templatemo_image_01.jpg" alt="Race transporters" />
    Two-car or three-car decks, hydraulic lifts and full workshop space for the season ahead.
  </div>
  <div class="col_4">
    <h3>Hospitality units</h3>
    <img src="images/templatemo_image_02.jpg" alt="Hospitality units" />
    Demountable lounges, kitchens and roof terraces — your brand on the road.
  </div>
  <div class="col_4">
    <h3>Motorhomes</h3>
    <img src="images/templatemo_image_03.jpg" alt="Motorhomes" />
    Driver and crew quarters built for back-to-back race weekends.
  </div>
  <div class="col_4 rmc">
    <h3>Mobile workshops</h3>
    <img src="images/templatemo_image_04.jpg" alt="Mobile workshops" />
    Tool storage, compressed air and 400V service — a self-contained pit garage.
  </div>

</div><!-- end no_sidebar -->

<div id="templatemo_content">
  <div class="cleaner h10"></div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
```

## `footer.php`
```php
<?php
// footer.php — Piè di pagina globale
$footer_base = '';
$footer_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', '_admin', 'shared'] as $folder) {
    if (strpos($footer_script, '/' . $folder . '/') !== false) {
        $footer_base = '../';
        break;
    }
}
?>
<!-- Footer -->
<div id="templatemo_bottom"><div class="col_4 col_f">
  <h5>Browse</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $footer_base; ?>road_vehicles.php">Road vehicles</a></li>
    <li><a href="<?php echo $footer_base; ?>special_vehicles.php">Special vehicles</a></li>
    <li><a href="<?php echo $footer_base; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $footer_base; ?>04_request_offer/04_request_offer.php">Request a quotation</a></li>
  </ul>
</div>
<div class="col_4">
  <h5>Marketplace</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>02_free_ads/02_view_ads.php">Browse free ads</a></li>
    <li><a href="<?php echo $footer_base; ?>03_ads/03_view_ads.php">Browse premium ads</a></li>
    <li><a href="<?php echo $footer_base; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $footer_base; ?>portfolio.php">Portfolio</a></li>
    <li><a href="<?php echo $footer_base; ?>blog.php">Blog</a></li>
  </ul>
</div>
<div class="col_4">
  <h5>Useful links</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>about.php">About us</a></li>
    <li><a href="<?php echo $footer_base; ?>what_we_do.php">What we do</a></li>
    <li><a href="<?php echo $footer_base; ?>FAQ.php">F.A.Q.</a></li>
    <li><a href="<?php echo $footer_base; ?>Conditions.php">Conditions &amp; rules</a></li>
    <li><a href="<?php echo $footer_base; ?>contact.php">Contact us</a></li>
  </ul>
</div>
<div class="col_4 col_l rmc">
  <h5>Follow us</h5>
  <ul class="footer_link">
    <li><a href="https://www.facebook.com/profile.php?id=61590545821976" class="facebook social">Facebook</a></li>
    <li><a href="https://www.instagram.com/allonwheel/" class="instagram social">Instagram</a></li>
  </ul>
</div>
<div class="cleaner"></div>
</div>
<div id="templatemo_footer">
  Copyright &copy; <?php echo date('Y'); ?> | <a href="https://www.allonwheel.com">All on Wheel Ltd.</a>
  | <a href="<?php echo $footer_base; ?>privacy.php">Privacy policy</a>
  | <a href="<?php echo $footer_base; ?>cookie-policy.php">Cookie policy</a>
</div>
</div>
<!-- End footer -->
<?php /* Cookie banner sito-wide: incluso una sola volta dal footer */ include __DIR__ . '/cookie_banner/cookie_banner.php'; ?>
<?php /* dir. 20: contatore Histats consolidato e consent-gated */ include __DIR__ . '/includes/histats.php'; ?>
```

## `browse.php`
```php
<?php
// ============================================================
// browse.php — Tutti gli annunci (free + premium) in un'unica pagina
//
// Combina 02_free_ads e 03_ads con UNION ALL, ordinati per data DESC.
// Ogni card indica il tipo di annuncio (Free / Premium) con un badge.
// Supporta ricerca testuale e filtro per categoria (stesse colonne
// booleane presenti in entrambe le tabelle).
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';

// ---- Mappa categorie (colonne booleane nei DB) ----
$category_map = [
    'racing'      => 'Racing',
    'hospitality' => 'Hospitality',
    'motorhome'   => 'Motorhome',
    'promotion'   => 'Promotion',
    'horse'       => 'Horse',
    'medical'     => 'Medical',
    'military'    => 'Military',
    'technology'  => 'Technology',
    'street_food' => 'Street food',
];

$search     = trim($_GET['q']   ?? '');
$active_cat = trim($_GET['cat'] ?? '');
if (!array_key_exists($active_cat, $category_map)) {
    $active_cat = '';
}

// ---- Parametri posizionali per la UNION ----
// Ogni ramo del UNION ha gli stessi parametri → li duplichiamo nell'array bind
$bind = [];

$search_clause = '';
if ($search !== '') {
    $search_clause = ' AND (title LIKE ? OR description LIKE ? OR author LIKE ?)';
    $like = '%' . $search . '%';
    $bind = [$like, $like, $like]; // per il ramo 02_free_ads
}

$cat_clause = '';
if ($active_cat !== '') {
    // Slug già validato contro $category_map, sicuro da usare come nome colonna
    $cat_clause = sprintf(' AND `%s` = 1', $active_cat);
}

// Duplica i parametri search per il secondo ramo del UNION
$bind_union = array_merge($bind, $bind); // bind × 2

$sql = "
  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         'free'    AS ad_source,
         '02_free_ads/02_view_ad.php'  AS detail_url,
         '/upload_image/02_free_ads/'  AS upload_path
  FROM `02_free_ads`
  WHERE 1=1 {$search_clause} {$cat_clause}

  UNION ALL

  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         'premium' AS ad_source,
         '03_ads/03_view_ad.php'       AS detail_url,
         '/upload_image/03_ads/'       AS upload_path
  FROM `03_ads`
  WHERE 1=1 {$search_clause} {$cat_clause}

  ORDER BY created_at DESC, id_ads DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind_union);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Allonwheel] browse.php UNION query error: ' . $e->getMessage());
    $ads = [];
}

$is_logged_in = is_user_logged_in();

function browseBadge(string $type): string
{
    $map = [
        'New on sell'  => 'New — for sale',
        'Used on sell' => 'Used — for sale',
        'For rent'     => 'For rent',
        'Project'      => 'Project',
    ];
    return $map[$type] ?? htmlspecialchars($type);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — Browse all listings</title>
<meta name="description" content="Browse all special vehicle listings — free and premium ads on All on Wheel marketplace." />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/piroBox.1_2.js"></script>
<script type="text/javascript" src="js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">All listings</div>
    <div id="search_box">
      <form action="" method="get">
        <?php if ($active_cat !== ''): ?>
          <input type="hidden" name="cat" value="<?php echo htmlspecialchars($active_cat); ?>" />
        <?php endif; ?>
        <input type="text"
               value="<?php echo htmlspecialchars($search); ?>"
               name="q" size="10" id="searchfield" title="Search listings"
               placeholder="Search…" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
        <?php if ($search !== ''): ?>
          <a href="?<?php echo $active_cat !== '' ? 'cat=' . htmlspecialchars($active_cat) : ''; ?>"
             style="margin-left:6px;font-size:11px;color:#666;">&#10005; Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content">

    <!-- ===== Filtri per categoria ===== --><!-- Legenda tipo annuncio -->

    <?php if (empty($ads)): ?>
    <div class="post_box">
      <h2>No listings found</h2>
      <?php if ($search !== '' || $active_cat !== ''): ?>
        <p>No ads match your current filter. <a href="browse.php">View all listings</a></p>
      <?php else: ?>
        <p>There are no ads published yet.
          <?php if ($is_logged_in): ?>
            <a href="02_free_ads/02_insert_ad.php">Post the first one!</a>
          <?php else: ?>
            <a href="01_login/newregister.php">Register</a> to post the first listing.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <?php foreach ($ads as $ad):
      $is_premium  = ($ad['ad_source'] === 'premium');
      $thumb       = trim((string)($ad['image_thumbnail'] ?? ''));
      $orig        = trim((string)($ad['image_original']  ?? ''));
      $upload_path = $ad['upload_path'];

      $thumb_url = ($thumb !== '' && $thumb !== 'no_image.jpg')
        ? $upload_path . 'thumbnail/' . $thumb
        : 'images/no_image.jpg';
      $orig_url  = ($orig !== '' && $orig !== 'no_image.jpg')
        ? $upload_path . 'original/' . $orig
        : $thumb_url;

      $price       = (float)$ad['list_price'];
      $desc        = (string)($ad['description'] ?? '');
      $short       = mb_strlen($desc) > 220 ? mb_substr($desc, 0, 220) . '…' : $desc;
      $created_ts  = strtotime((string)($ad['created_at'] ?? ''));
      $created_fmt = $created_ts ? date('d M Y', $created_ts) : '';
      $detail_url  = $ad['detail_url'];
    ?>

    <div class="post_box">
      <h2><?php echo htmlspecialchars($ad['title']); ?></h2>

      <?php if (!empty($ad['subtitle'])): ?>
      <p><em><?php echo htmlspecialchars($ad['subtitle']); ?></em></p>
      <?php endif; ?>
        <ul class="gallery" style="margin:0;">
          <li> <a class="pirobox"
               href="<?php echo htmlspecialchars($orig_url); ?>"
               title="<?php echo htmlspecialchars($ad['title']); ?>"> <img src="<?php echo htmlspecialchars($thumb_url); ?>"
                   alt="<?php echo htmlspecialchars($ad['title']); ?>"
                   width="220" height="150" border="0"
                   onerror="this.onerror=null;this.src='images/no_image.jpg';" /> </a> </li>
      </ul>

      <p>
        <?php if ($is_premium): ?><strong>Premium</strong><?php else: ?>Free<?php endif; ?>
        &middot; <?php echo htmlspecialchars(browseBadge($ad['type'])); ?>
        &middot; <?php echo htmlspecialchars($ad['conditions']); ?>
      </p>

      <?php if ($price > 0): ?>
        <p>
          &euro;&nbsp;<?php echo number_format($price, 0, '.', ','); ?>
        </p>
      <?php else: ?>
        <p><em>Price on request</em></p>
		<p><?php echo nl2br(htmlspecialchars($short)); ?></p>
      <?php endif; ?>

      <p><?php echo nl2br(htmlspecialchars($short)); ?></p>

      <div class="post_meta">
        <span class="cat">By <strong><?php echo htmlspecialchars($ad['author']); ?></strong></span>
        <?php if ($created_fmt !== ''): ?>
          &nbsp;|&nbsp;<span class="cat"><?php echo $created_fmt; ?></span>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($detail_url); ?>?id_ads=<?php echo (int)$ad['id_ads']; ?>"
           class="more float_r">View details</a>
        <div class="cleaner"></div>
      </div>

    </div>

    <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- end templatemo_content -->

<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>
  <?php include __DIR__ . '/footer.php'; ?>

</div>
</body>
</html>
```

## `includes/histats.php`  *(nuovo)*
```php
<?php
// ============================================================
// includes/histats.php — Contatore Histats CONSOLIDATO e consent-gated.
//
// dir. 20: il contatore Histats e' una dotazione PERMANENTE del sito e NON
// va rimosso. Qui e' implementato "al meglio":
//   - UNICO punto di inclusione (questo partial, incluso da footer.php):
//     niente piu' snippet duplicato in decine di pagine.
//   - Caricamento ASINCRONO (script async, non blocca il rendering).
//   - CONSENT-GATED: parte SOLO se l'utente ha accettato i cookie
//     'analytics' (cookie aow_consent + Consent Mode v2). Conforme GDPR.
//   - ID parametrico: nessun ID hard-coded sparso nel codice.
//
// COME CONFIGURARE L'ID:
//   - definisci la costante HISTATS_ID (es. in config/bootstrap.php), oppure
//   - imposta la variabile d'ambiente HISTATS_ID, oppure
//   - in ultima istanza scrivi l'ID nel fallback qui sotto.
// Senza ID il partial non produce nulla (no-op sicuro).
// ============================================================

$histats_id = '';
if (defined('HISTATS_ID')) {
    $histats_id = (string) HISTATS_ID;
} elseif (getenv('HISTATS_ID')) {
    $histats_id = (string) getenv('HISTATS_ID');
}
// Fallback manuale (inserisci qui il tuo ID Histats se non usi costante/env):
if ($histats_id === '') {
    $histats_id = ''; // es. '4891234'
}

// Accetta solo ID numerico (difesa input); se vuoto/non valido -> nessun output.
$histats_id = trim($histats_id);
if ($histats_id === '' || !ctype_digit($histats_id)) {
    return;
}
?>
<!-- Histats counter (dir. 20) — caricato SOLO con consenso 'analytics'. -->
<div id="histats_counter"></div>
<script>
(function () {
  'use strict';
  if (window.aowLoadHistats) { return; } // definito una sola volta
  var loaded = false;
  // Loader idempotente: inietta lo snippet async ufficiale Histats.
  window.aowLoadHistats = function () {
    if (loaded) { return; }
    loaded = true;
    window._Hasync = window._Hasync || [];
    _Hasync.push(['Histats.start', '1,<?php echo $histats_id; ?>,4,0,0,0,00010000']);
    _Hasync.push(['Histats.fasi', '1']);
    _Hasync.push(['Histats.track_hits', '']);
    var hs = document.createElement('script');
    hs.type = 'text/javascript';
    hs.async = true;
    hs.src = 'https://s10.histats.com/js15_as.js';
    (document.getElementsByTagName('head')[0] || document.body).appendChild(hs);
  };
  // Se il consenso 'analytics' e' GIA' presente (visitatore di ritorno), parte
  // subito; altrimenti restera' in attesa che cookie_consent.js lo richiami
  // al momento dell'accettazione (hook in applyConsent).
  try {
    var m = document.cookie.match(/(?:^|; )aow_consent=([^;]+)/);
    var c = m ? JSON.parse(decodeURIComponent(m[1])) : null;
    if (c && c.analytics) { window.aowLoadHistats(); }
  } catch (e) {}
})();
</script>
```

## `config/security_headers.php`
```php
<?php
// ============================================================
// config/security_headers.php
// Header HTTP di sicurezza, applicati globalmente su ogni pagina.
//
// USO: includere questo file il PIÙ PRESTO POSSIBILE in ogni pagina
//  (idealmente prima di qualsiasi output). Il modo più semplice è
//  includerlo da config/bootstrap.php — così copre automaticamente
//  ogni pagina che già usa bootstrap.
//
// HEADER APPLICATI:
//  - X-Frame-Options: SAMEORIGIN — blocca clickjacking via iframe
//  - X-Content-Type-Options: nosniff — blocca MIME sniffing
//  - Referrer-Policy: strict-origin-when-cross-origin
//  - X-XSS-Protection: 1; mode=block (legacy ma innocuo)
//  - Strict-Transport-Security (HSTS) — solo se in HTTPS
//  - Content-Security-Policy (CSP) — restrittiva ma compatibile con
//  le librerie esterne usate dal sito (jQuery CDN, ddsmoothmenu, pirobox)
//  - Permissions-Policy — disattiva camera/microfono/geolocation
//
// CSP NOTE:
// La CSP è "report-only" di default per non rompere il sito in produzione
// se ci sono inline-script residui. Quando hai verificato i log che
// non ci sono violazioni, cambia templatemo_CSP_ENFORCE a true.
// ============================================================

if (defined('templatemo_SECURITY_HEADERS_LOADED')) {
  return;
}
define('templatemo_SECURITY_HEADERS_LOADED', true);

// Se headers già inviati (output già iniziato) non possiamo fare nulla
if (headers_sent($file, $line)) {
  error_log("[Allonwheel] security_headers: headers già inviati da $file:$line");
  return;
}

// Set in modalità ENFORCE (false = report-only, sicuro per il primo deploy)
if (!defined('templatemo_CSP_ENFORCE')) {
  define('templatemo_CSP_ENFORCE', false);
}

// ------------------------------------------------------------
// 1. Anti-clickjacking
// ------------------------------------------------------------
header('X-Frame-Options: SAMEORIGIN');

// ------------------------------------------------------------
// 2. Anti-MIME-sniffing
// ------------------------------------------------------------
header('X-Content-Type-Options: nosniff');

// ------------------------------------------------------------
// 3. Referrer policy: invia origin solo verso lo stesso schema
// ------------------------------------------------------------
header('Referrer-Policy: strict-origin-when-cross-origin');

// ------------------------------------------------------------
// 4. Legacy XSS filter (innocuo nei browser moderni)
// ------------------------------------------------------------
header('X-XSS-Protection: 1; mode=block');

// ------------------------------------------------------------
// 5. HSTS — solo su HTTPS
// ------------------------------------------------------------
$is_https = (
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
  (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

if ($is_https) {
  // 1 anno + includeSubDomains. Aggiungi 'preload' SOLO dopo aver
  // sottomesso il dominio a hstspreload.org
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ------------------------------------------------------------
// 6. Content Security Policy
// ------------------------------------------------------------
// Compatibile con le risorse esterne in uso:
//  - jQuery / ddsmoothmenu / pirobox (servite localmente da /js/)
//  - Tracker Histats reintrodotto (dir. 20): caricato SOLO col consenso 'analytics'
//    (consent-gated); host Histats consentiti in CSP qui sotto.
//  - L'init degli script (ddsmoothmenu.init, piroBox, clearText) e' stato
//  spostato in /js/site_init.js. 'unsafe-inline' in script-src resta
//  ancora necessario per i gestori inline residui (onfocus/onsubmit).
//  TODO futuro: convertire anche questi handler in delega eventi e
//  rimuovere 'unsafe-inline'.
$csp = implode('; ', [
  "default-src 'self'",
  "script-src 'self' 'unsafe-inline' https://s10.histats.com https://sstatic1.histats.com",
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data: https://sstatic1.histats.com https://s10.histats.com",
  "font-src 'self' data:",
  "connect-src 'self'",
  "frame-ancestors 'self'",
  "form-action 'self'",
  "base-uri 'self'",
  "object-src 'none'",
]);

if (templatemo_CSP_ENFORCE) {
  header('Content-Security-Policy: ' . $csp);
} else {
  // Mode "report-only": logga le violazioni nel browser console ma
  // non blocca il caricamento. Sicuro per il primo deploy.
  header('Content-Security-Policy-Report-Only: ' . $csp);
}

// ------------------------------------------------------------
// 7. Permissions-Policy: il sito non usa camera/mic/geolocation
// ------------------------------------------------------------
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');

// ------------------------------------------------------------
// 8. Rimuovi header che rivelano lo stack tecnologico
// ------------------------------------------------------------
header_remove('X-Powered-By');
header_remove('Server');
?>
```

## `js/cookie_consent.js`
```javascript
/* cookie_banner/cookie_consent.js — gestione consenso (no dipendenze) */
(function () {
  'use strict';
  var COOKIE = 'aow_consent';
  var VERSION = '1.0';

  // Google Consent Mode v2: default tutto DENIED finche' non c'e' consenso
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('consent', 'default', {
    ad_storage: 'denied', analytics_storage: 'denied',
    ad_user_data: 'denied', ad_personalization: 'denied'
  });

  function readConsent() {
    var m = document.cookie.match(/(?:^|; )aow_consent=([^;]+)/);
    try { return m ? JSON.parse(decodeURIComponent(m[1])) : null; } catch (e) { return null; }
  }
  function writeConsent(c) {
    c.v = VERSION;
    document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(c)) +
      ';path=/;max-age=' + (60 * 60 * 24 * 180) + ';SameSite=Lax' +
      (location.protocol === 'https:' ? ';Secure' : '');
  }
  function applyConsent(c) {
    gtag('consent', 'update', {
      analytics_storage: c.analytics ? 'granted' : 'denied',
      ad_storage:        c.marketing ? 'granted' : 'denied',
      ad_user_data:      c.marketing ? 'granted' : 'denied',
      ad_personalization:c.marketing ? 'granted' : 'denied'
    });
    // Prova del consenso lato server (registro consensi)
    try {
      fetch('/cookie_banner/consent_log.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ categories: c, version: VERSION })
      });
    } catch (e) {}

    // Statistiche: carica i tracker 'analytics' SOLO col consenso (es. Histats, dir. 20).
    if (c.analytics && typeof window.aowLoadHistats === 'function') {
      window.aowLoadHistats();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var banner = document.getElementById('aow-cookie-banner');
    var manage = document.getElementById('aow-cc-manage');
    var existing = readConsent();
    if (existing) { applyConsent(existing); } else if (banner) { banner.hidden = false; }

    function close(c) { writeConsent(c); applyConsent(c); if (banner) banner.hidden = true; if (manage) manage.hidden = false; }

    var accept = document.getElementById('aow-cc-accept');
    var reject = document.getElementById('aow-cc-reject');
    var save   = document.getElementById('aow-cc-save');
    if (accept) accept.onclick = function () { close({ analytics: true,  marketing: true  }); };
    if (reject) reject.onclick = function () { close({ analytics: false, marketing: false }); };
    if (save)   save.onclick   = function () {
      close({
        analytics: !!document.getElementById('aow-cc-analytics').checked,
        marketing: !!document.getElementById('aow-cc-marketing').checked
      });
    };
    if (manage) manage.onclick = function () { if (banner) banner.hidden = false; manage.hidden = true; };
  });
})();
```

## `cookie_banner/cookie_consent.js`
```javascript
/* cookie_banner/cookie_consent.js — gestione consenso (no dipendenze) */
(function () {
  'use strict';
  var COOKIE = 'aow_consent';
  var VERSION = '1.0';

  // Google Consent Mode v2: default tutto DENIED finche' non c'e' consenso
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('consent', 'default', {
    ad_storage: 'denied', analytics_storage: 'denied',
    ad_user_data: 'denied', ad_personalization: 'denied'
  });

  function readConsent() {
    var m = document.cookie.match(/(?:^|; )aow_consent=([^;]+)/);
    try { return m ? JSON.parse(decodeURIComponent(m[1])) : null; } catch (e) { return null; }
  }
  function writeConsent(c) {
    c.v = VERSION;
    document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(c)) +
      ';path=/;max-age=' + (60 * 60 * 24 * 180) + ';SameSite=Lax' +
      (location.protocol === 'https:' ? ';Secure' : '');
  }
  function applyConsent(c) {
    gtag('consent', 'update', {
      analytics_storage: c.analytics ? 'granted' : 'denied',
      ad_storage:        c.marketing ? 'granted' : 'denied',
      ad_user_data:      c.marketing ? 'granted' : 'denied',
      ad_personalization:c.marketing ? 'granted' : 'denied'
    });
    // Prova del consenso lato server (registro consensi)
    try {
      fetch('/cookie_banner/consent_log.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ categories: c, version: VERSION })
      });
    } catch (e) {}

    // Statistiche: carica i tracker 'analytics' SOLO col consenso (es. Histats, dir. 20).
    if (c.analytics && typeof window.aowLoadHistats === 'function') {
      window.aowLoadHistats();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var banner = document.getElementById('aow-cookie-banner');
    var manage = document.getElementById('aow-cc-manage');
    var existing = readConsent();
    if (existing) { applyConsent(existing); } else if (banner) { banner.hidden = false; }

    function close(c) { writeConsent(c); applyConsent(c); if (banner) banner.hidden = true; if (manage) manage.hidden = false; }

    var accept = document.getElementById('aow-cc-accept');
    var reject = document.getElementById('aow-cc-reject');
    var save   = document.getElementById('aow-cc-save');
    if (accept) accept.onclick = function () { close({ analytics: true,  marketing: true  }); };
    if (reject) reject.onclick = function () { close({ analytics: false, marketing: false }); };
    if (save)   save.onclick   = function () {
      close({
        analytics: !!document.getElementById('aow-cc-analytics').checked,
        marketing: !!document.getElementById('aow-cc-marketing').checked
      });
    };
    if (manage) manage.onclick = function () { if (banner) banner.hidden = false; manage.hidden = true; };
  });
})();
```
