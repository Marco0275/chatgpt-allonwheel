<?php
// ============================================================
// portfolio.php — Vetrina "Portfolio" (nodo Portfolio del flowchart).
//
// Mostra le ULTIME 4 IMMAGINI CARICATE per ciascuna delle tre
// tipologie del flowchart:
//   - Road      (veicoli, macro_category = 'road')
//   - Special   (veicoli, macro_category = 'special')
//   - Container / Shelter (item_kind = 'shelter_container')
//
// "Ultima immagine caricata" = immagine principale (image_original /
// image_thumbnail) dell'annuncio piu' recente per created_at. Gli
// annunci free e premium sono unificati (dir. 14) e non c'e' alcuna
// etichetta free/premium. Vengono mostrati SOLO dati realmente presenti
// nel DB (niente immagini inventate): le voci no_image.jpg sono escluse.
//
// Nessuno stile nuovo (dir. 4, 8): si riusa l'identica impalcatura
// gallery_box / ul.gallery della versione precedente di questa pagina,
// con il foglio di stile esistente. Le ancore usano le classi piroBox
// gia' presenti sul sito (pirobox / pirobox_gall_*).
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';

/**
 * Ritorna le ultime $limit immagini principali caricate per una categoria.
 * $cat_where: frammento WHERE COSTANTE (nessun input utente -> niente SQLi).
 */
function portfolio_latest(PDO $pdo, string $cat_where, int $limit = 4): array
{
    $sql = "
      SELECT id_ads, title, image_original, image_thumbnail, created_at,
             '/upload_image/02_free_ads/' AS upload_path,
             '02_free_ads/02_view_ad.php' AS detail_url
      FROM `02_free_ads`
      WHERE status = 'approved'
            AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
            AND ({$cat_where})

      UNION ALL

      SELECT id_ads, title, image_original, image_thumbnail, created_at,
             '/upload_image/03_ads/' AS upload_path,
             '03_ads/03_view_ad.php' AS detail_url
      FROM `03_ads`
      WHERE status = 'approved'
            AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
            AND ({$cat_where})

      UNION ALL

      SELECT id_ads, title, image_original, image_thumbnail, created_at,
             '/upload_image/03_ads/' AS upload_path,
             '03_ads/03_view_ad.php' AS detail_url
      FROM `07_rent_ads`
      WHERE status = 'approved'
            AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
            AND ({$cat_where})
      ORDER BY created_at DESC, id_ads DESC
      LIMIT {$limit}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Ultime immagini dagli annunci di NOLEGGIO (07_rent_ads). */
function portfolio_latest_rent(PDO $pdo, int $limit = 4): array
{
    $sql = "SELECT id_ads, title, image_original, image_thumbnail, created_at,
                   '/upload_image/07_rent/' AS upload_path,
                   '07_rent/07_21_rent_view.php' AS detail_url
            FROM `07_rent_ads`
            WHERE status = 'approved' AND image_thumbnail <> '' AND image_thumbnail <> 'no_image.jpg'
            ORDER BY created_at DESC, id_ads DESC LIMIT " . (int)$limit;
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


// Definizione delle tre sezioni del portfolio (etichette in inglese, dir. 0).
// 'gall' = suffisso classe piroBox per la galleria; 'more' = pagina reale.
$sections = [
    [
        'h2'    => 'Road vehicles',
        'key'   => 'b2b.road',
        'gall'  => 'pirobox_gall_road',
        'where' => "(item_kind = 'vehicle' OR item_kind IS NULL) AND macro_category = 'road'",
        'more'  => 'special_vehicles.php?macro=road',
    ],
    [
        'h2'    => 'Special vehicles',
        'key'   => 'b2b.special',
        'gall'  => 'pirobox_gall_special',
        'where' => "(item_kind = 'vehicle' OR item_kind IS NULL) AND macro_category = 'special'",
        'more'  => 'special_vehicles.php?macro=special',
    ],
    [
        'h2'    => 'Shelter / Container',
        'key'   => 'macro.shelter',
        'gall'  => 'pirobox_gall_shelter',
        'where' => "item_kind = 'shelter_container'",
        'more'  => 'shelter_container.php',
    ],
    [
        'h2'    => 'Rentals',
        'key'   => 'home.rent_h',
        'gall'  => 'pirobox_gall_rent',
        'rent'  => true,
        'more'  => 'rent_vehicles.php',
    ],
];

// Carica le immagini per ogni sezione (degrada in modo pulito se le colonne
// item_kind / macro_category non sono ancora migrate).
$schema_ready = true;
foreach ($sections as $k => $sec) {
    try {
        $sections[$k]['images'] = !empty($sec['rent']) ? portfolio_latest_rent($pdo, 4) : portfolio_latest($pdo, $sec['where'], 4);
    } catch (PDOException $e) {
        error_log('[Allonwheel] portfolio.php query error: ' . $e->getMessage());
        $schema_ready = false;
        $sections[$k]['images'] = [];
    }
}

$is_logged_in = is_user_logged_in();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php te('nav.portfolio','Portfolio'); ?></title>
<meta name="keywords" content="portfolio, road vehicles, special vehicles, shelter, container" />
<meta name="description" content="Portfolio — the latest images uploaded for Road, Special and Shelter / Container categories on All on Wheel." />
<meta name="revisit-after" content="3" />
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
<?php $seo_canonical = 'portfolio.php'; include __DIR__ . '/includes/seo_head.php'; ?>
</head>

<body>

<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include 'header.php'; ?>
</div>

 <div id="content_top">
    <div id="page_title"><?php te('nav.portfolio','Portfolio'); ?></div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

<?php foreach ($sections as $sec):
    // Assegna direttamente l'intero array di immagini senza tagliarlo
    $images = $sec['images']; 
    
    // Con una sola immagine la classe galleria fa scattare un alert in piroBox:
    // in quel caso usiamo la classe "pirobox" (immagine singola).
    $anchor_class = (count($images) > 1) ? $sec['gall'] : 'pirobox';
  ?>
<div class="cleaner h20"></div> 
<div class="post_box">
    <h2><?php echo htmlspecialchars(t($sec['key'] ?? '', $sec['h2']), ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if (empty($images)): ?>
		  <ul class="gallery m0">
		 </ul>
	<p><em>
		<br>
	  <?php
        echo $schema_ready
          ? t('port.empty_imgs','No images uploaded in this category yet.')
          : t('port.setup','This section is being set up. Please check back soon.');
      ?>
	</em></p>
		  

  <?php else: ?>
  <ul class="gallery m0">
      <?php foreach ($images as $img):
        $thumb = trim((string)($img['image_thumbnail'] ?? ''));
        $orig  = trim((string)($img['image_original']  ?? ''));
        $path  = (string)$img['upload_path'];

        $thumb_url = $path . 'thumbnail/' . $thumb;
        $orig_url  = ($orig !== '' && $orig !== 'no_image.jpg')
          ? $path . 'original/' . $orig
          : $thumb_url;

        $title = trim((string)($img['title'] ?? ''));
        if ($title === '') { $title = 'Image'; }
      ?>
      <li><a class="<?php echo $anchor_class; ?>"
             href="<?php echo htmlspecialchars($orig_url, ENT_QUOTES, 'UTF-8'); ?>"
             title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
          <img src="<?php echo htmlspecialchars($thumb_url, ENT_QUOTES, 'UTF-8'); ?>"
               alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
               width="220" height="150" border="0" loading="lazy" decoding="async" /></a></li>
      <?php endforeach; ?>
		<div class="cleaner h20"></div>
    </ul>
    <?php endif; ?>
	  <div class="post_meta">
		  <table width="100%" border="0">
  <tbody>
    <tr>
      <td width="96%">&nbsp;</td>
      <td width="4%" align="right"><a href="<?php echo htmlspecialchars($sec['more'], ENT_QUOTES, 'UTF-8'); ?>" class="more float_r">More</a></td>
    </tr>
  </tbody>
</table>

</div>
  </div>
	<div class="cleaner h20"></div> 
  <?php endforeach; ?>

  <div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
