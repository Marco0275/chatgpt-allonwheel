<?php
// ============================================================
// rent_vehicles.php — Pagina dedicata ai veicoli a noleggio (Vehicle rental).
//
// Posizione nell'albero: Suppliers > Project manager > Vehicle rental.
//
// Mostra gli annunci di noleggio veicoli speciali basandosi sui dati di
// 07_rent/07_20_rent_list.php, con impalcatura e layout visivo identico a special_vehicles.php.
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/rent.class.php';
require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';

$search = trim($_GET['q'] ?? '');
if ($search === 'Search') { $search = ''; }

// La sidebar 'Special vehicles' filtra questa pagina
$aow_special_search_action = 'rent_vehicles.php';

// ---- Filtro vehicle_type (dalla tassonomia veicoli speciali) ----
$all_vtypes   = VehicleTaxonomy::typesForCategory(VehicleTaxonomy::CAT_SPECIAL, $pdo);
$vtype_slugs  = array_column($all_vtypes, 'slug');
$active_vtype = trim($_GET['vtype'] ?? '');
if (!in_array($active_vtype, $vtype_slugs, true)) { $active_vtype = ''; }

$active_label = $active_vtype !== '' ? VehicleTaxonomy::label($active_vtype, $pdo) : '';

// Caricamento annunci di noleggio
$rent = new RentAds($pdo);
$ads  = $rent->listActive($active_vtype !== '' ? $active_vtype : null);

// Filtro di ricerca testuale (se specificato nel form in alto)
if ($search !== '') {
    $search_lower = mb_strtolower($search);
    $ads = array_filter($ads, static function ($ad) use ($search_lower) {
        $title  = mb_strtolower((string)($ad['title'] ?? ''));
        $desc   = mb_strtolower((string)($ad['description'] ?? ''));
        $author = mb_strtolower((string)($ad['author'] ?? ''));
        return strpos($title, $search_lower) !== false
            || strpos($desc, $search_lower) !== false
            || strpos($author, $search_lower) !== false;
    });
}

$is_logged_in = is_user_logged_in();

function vehiclesBadge(string $type): string
{
    $map = [
        'New on sell'  => 'New — for sale',
        'Used on sell' => 'Used — for sale',
        'For rent'     => 'For rent',
        'Project'      => 'Project',
    ];
    return $map[$type] ?? $type;
}

// Etichetta e titolo della pagina
$page_title = 'Vehicle rental';
if ($active_label !== '') {
    $page_title .= ' — ' . $active_label;
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — <?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords" content="special vehicles rental, rent vehicles, marketplace, ads" />
<meta name="description" content="Special vehicles for rent published on All on Wheel." />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
<?php
$seo_canonical = 'rent_vehicles.php' . ($active_vtype !== '' ? '?vtype=' . rawurlencode($active_vtype) : '');
include __DIR__ . '/includes/seo_head.php';
?>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title"><?php echo htmlspecialchars($page_title); ?></div>
    <div id="search_box">
      <form action="" method="get">
        <?php if ($active_vtype !== ''): ?>
          <input type="hidden" name="vtype" value="<?php echo htmlspecialchars($active_vtype); ?>" />
        <?php endif; ?>
        <input type="text"
               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
    <?php if (empty($ads)): ?>
    <div class="post_box">
      <h2>No rental listings published yet</h2>
      <?php if ($search !== '' || $active_vtype !== ''): ?>
        <p>No rental vehicles match your current filter.
           <a href="rent_vehicles.php" class="more">View all</a></p>
      <?php else: ?>
        <p>There are no rental vehicles published at the moment.
          <?php if ($is_logged_in): ?>
            <a href="07_rent/07_10_rent_post.php" class="more">Post the first one!</a>
          <?php else: ?>
            <a href="01_login/newregister.php" class="more">Register</a> to post the first listing or request.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <?php foreach ($ads as $ad):
      if (!isset($ad['detail_url'])) {
        $ad['detail_url'] = '07_rent/07_21_rent_view.php?id=' . $ad['id_ads'];
      }
      if (!isset($ad['upload_path'])) {
        $ad['upload_path'] = 'upload_image/07_rent/';
      }
      $thumb       = trim((string)($ad['image_thumbnail'] ?? ''));
      $orig        = trim((string)($ad['image_original']  ?? ''));
      $upload_path = $ad['upload_path'];

      $thumb_url = ($thumb !== '' && $thumb !== 'no_image.jpg')
        ? $upload_path . 'thumbnail/' . $thumb
        : 'images/no_image.jpg';
      $orig_url  = ($orig !== '' && $orig !== 'no_image.jpg')
        ? $upload_path . 'original/' . $orig
        : $thumb_url;

      $price       = (float)($ad['list_price'] ?? 0);
      $desc        = (string)($ad['description'] ?? '');
      $short       = mb_strlen($desc) > 220 ? mb_substr($desc, 0, 220) . '…' : $desc;
      $created_ts  = strtotime((string)($ad['created_at'] ?? ''));
      $created_fmt = $created_ts ? date('d M Y', $created_ts) : '';
      $detail_url  = $ad['detail_url'];
    ?>
    <?php
      // Card unificata: stesso formato di special_vehicles.php
      $aow_ad = $ad;
      $aow_ad['is_premium'] = false;
      $aow_type_label = 'vehiclesBadge';
      include __DIR__ . '/shared/ad_card.php';
    ?>
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