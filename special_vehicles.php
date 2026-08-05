<?php
// ============================================================
// special_vehicles.php — Pagina dedicata ai veicoli (nodo Vehicle types).
//
// Posizione nell'albero (Parte 0): Suppliers > Project manager >
// Vehicle types → Road / Special (esclusi Shelter / Container).
//
// Mostra gli annunci (free + premium, unificati — dir. 14) il cui
// item_kind = 'vehicle' (oppure NULL, per retro-compatibilita' con
// gli annunci pre-migrazione che non hanno ancora la colonna popolata).
// Nessuna etichetta free/premium nel titolo (dir. 14). Solo dati
// realmente presenti nel DB.
//
// Nessuno stile nuovo (dir. 8): solo classi/struttura gia' esistenti
// (stessa impalcatura di shelter_container.php e browse.php).
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';

$search = trim($_GET['q'] ?? '');
if ($search === 'Search') { $search = ''; }

// Coerenza URL: se qualcuno arriva con ?macro=road su questa pagina, mandalo
// alla pagina Road (evita il titolo "Special" con contenuti/URL road).
if (($_GET['macro'] ?? '') === 'road') {
    $qs = $_GET; unset($qs['macro']);
    header('Location: road_vehicles.php' . ($qs ? '?' . http_build_query($qs) : ''), true, 301);
    exit;
}

// Pagina dedicata: mostra SOLO i veicoli "special" (categoria forzata,
// indipendente dal parametro GET).
$aow_rfq_section = 'special'; // RFQ dedicata di sezione (CTA in sidebar)
$macro = 'special';

// ---- Filtro vehicle_type (live da DB: solo tipi di questo macro) ----
$all_vtypes = [];
// Nuova tassonomia (24 lug 2026): questa pagina pesca da
// special_types: la lista curata dall'admin.
// La tabella non si sceglie qui: la decide VehicleTaxonomy.
require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';
$all_vtypes = VehicleTaxonomy::typesForCategory(VehicleTaxonomy::CAT_SPECIAL, $pdo);
$vtype_slugs  = array_column($all_vtypes, 'slug');
$active_vtype = trim($_GET['vtype'] ?? '');
if (!in_array($active_vtype, $vtype_slugs, true)) { $active_vtype = ''; }
$qs = function (array $p) {
    $p = array_filter($p, static function ($v) { return $v !== '' && $v !== null; });
    $pairs = [];
    foreach ($p as $k => $v) { $pairs[] = urlencode((string)$k) . '=' . urlencode((string)$v); }
    return $pairs ? '?' . implode('&amp;', $pairs) : 'special_vehicles.php';
};

$search_clause = '';
$bind = [];
if ($search !== '') {
    $search_clause = ' AND (title LIKE ? OR description LIKE ? OR author LIKE ?)';
    $like = '%' . $search . '%';
    $bind = [$like, $like, $like];
}

$macro_clause = '';
if ($macro !== '') {
    $macro_clause = ' AND macro_category = ?';
    $bind[] = $macro;
}

$vtype_clause = '';
if ($active_vtype !== '') {
    $vtype_clause = ' AND vehicle_type = ?';
    $bind[] = $active_vtype;
}

// Due rami UNION → bind raddoppiato
$bind_union = array_merge($bind, $bind);

// Le colonne item_kind / macro_category potrebbero non esistere prima
// della migrazione: in tal caso la query fallisce e trattiamo l'elenco
// come vuoto (graceful, niente errori a video).
$sql = "
  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         '02_free_ads/02_view_ad.php' AS detail_url,
         '/upload_image/02_free_ads/' AS upload_path
  FROM `02_free_ads`
  WHERE (item_kind = 'vehicle' OR item_kind IS NULL)
        AND status = 'approved'
        {$search_clause}
        {$macro_clause}
        {$vtype_clause}

  UNION ALL

  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         '03_ads/03_view_ad.php' AS detail_url,
         '/upload_image/03_ads/' AS upload_path
  FROM `03_ads`
  WHERE (item_kind = 'vehicle' OR item_kind IS NULL)
        AND status = 'approved'
        {$search_clause}
        {$macro_clause}
        {$vtype_clause}

  ORDER BY created_at DESC, id_ads DESC
";

$ads = [];
$schema_ready = true;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind_union);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tipicamente: colonne item_kind / macro_category non ancora migrate.
    error_log('[Allonwheel] special_vehicles.php query error: ' . $e->getMessage());
    $schema_ready = false;
    $ads = [];
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

// Etichetta pagina dipendente dal filtro macro
$page_title = 'Vehicles';
if ($macro === 'road')    { $page_title = 'Vehicles — Road'; }
if ($macro === 'special') { $page_title = 'Vehicles — Special'; }
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — <?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords" content="special vehicles, road vehicles, marketplace, ads" />
<meta name="description" content="All vehicles published on All on Wheel — free and premium ads, Road and Special categories." />
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
$seo_canonical = 'special_vehicles.php' . ($active_vtype !== '' ? '?vtype=' . rawurlencode($active_vtype) : '');
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
        <?php if ($macro !== ''): ?>
          <input type="hidden" name="macro" value="<?php echo htmlspecialchars($macro); ?>" />
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
      <h2>No vehicles published yet</h2>
      <?php if (!$schema_ready): ?>
        <p>This section is being set up. Please check back soon.</p>
      <?php elseif ($search !== '' || $macro !== ''): ?>
        <p>No vehicles match your current filter.
           <a href="special_vehicles.php" class="more">View all</a></p>
      <?php else: ?>
        <p>There are no vehicles published at the moment.
          <?php if ($is_logged_in): ?>
            <a href="02_free_ads/02_00_select_type.php" class="more">Post one</a>
          <?php else: ?>
            <a href="01_login/newregister.php" class="more">Register</a> to post one.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <?php foreach ($ads as $ad):
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
    <?php
      // Card unificata (17 lug 2026): stesso formato di tutte le altre pagine.
      // Markup in shared/ad_card.php. Il premium si deduce dal detail_url
      // perche' questa query non seleziona ad_source.
      $aow_ad = $ad;
      $aow_ad['is_premium'] = (strpos((string)($ad['detail_url'] ?? ''), '03_ads') !== false);
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
