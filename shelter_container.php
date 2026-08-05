<?php
// ============================================================
// shelter_container.php — Nodo flowchart "Shelter / Container"
//
// Posizione nell'albero (Parte 0): Suppliers > Company > Shelter/Container,
// confluente nella macro-categoria SPECIAL.
//
// Mostra gli annunci (free + premium, unificati — dir. 14) della famiglia
// Shelter & Container: product_macro = 'shelter-container' (dal 16 lug 2026;
// prima era item_kind — vedi la nota sulla tassonomia unificata sotto). Nessuna etichetta free/premium nel
// titolo (dir. 14). Solo dati realmente presenti nel DB: se non ci sono
// annunci, mostra uno stato vuoto (niente dati inventati).
//
// Nessuno stile nuovo (dir. 8): solo classi/struttura gia' esistenti.
// ============================================================

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';

$search = trim($_GET['q'] ?? '');
if ($search === 'Search') { $search = ''; }

$aow_rfq_section = 'shelter'; // RFQ dedicata di sezione (CTA in sidebar)
$search_clause = '';
$bind = [];
if ($search !== '') {
  $search_clause = ' AND (title LIKE ? OR description LIKE ? OR author LIKE ?)';
  $like = '%' . $search . '%';
  $bind = [$like, $like, $like];
}
$bind_union = array_merge($bind, $bind); // due rami UNION

// TASSONOMIA UNIFICATA (16 lug 2026 - decisione presa su delega):
// questa pagina filtrava per `item_kind = 'shelter_container'`, mentre le
// altre 4 famiglie filtrano per `product_macro`. Due meccanismi diversi per
// la stessa cosa. Ora usa `product_macro` come tutte le altre.
//
// Perche' e' sicuro (nessun annuncio sparisce):
//   ProductMacro::forAd() ha `item_kind === 'shelter_container'` come PRIMO
//   controllo, quindi con priorita' massima; e il backfill in
//   sql/Changelog/product_macros.sql fa la stessa mappatura. Quindi ogni
//   annuncio con item_kind='shelter_container' HA gia'
//   product_macro='shelter-container': il nuovo filtro e' un SOVRAINSIEME
//   del vecchio, non un sottoinsieme.
// In piu': product_macro e' indicizzata (idx_02/03_product_macro), item_kind
// no; e le correzioni fatte da _admin/edit_ad.php ora hanno effetto.
//
// Per gli annunci antecedenti alla migrazione con product_macro ancora NULL
// c'e' la patch idempotente sql/Changelog/2026-07-16_shelter_macro_align.sql.
$sql = "
  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         '02_free_ads/02_view_ad.php' AS detail_url,
         '/upload_image/02_free_ads/' AS upload_path
  FROM `02_free_ads`
  WHERE product_macro = 'shelter-container' AND status = 'approved' {$search_clause}

  UNION ALL

  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at,
         '03_ads/03_view_ad.php' AS detail_url,
         '/upload_image/03_ads/' AS upload_path
  FROM `03_ads`
  WHERE product_macro = 'shelter-container' AND status = 'approved' {$search_clause}

  ORDER BY created_at DESC, id_ads DESC
";

$ads = [];
$schema_ready = true;
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($bind_union);
  $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Tipicamente: colonna product_macro non ancora migrata
  // (patch sql/Changelog/product_macros.sql non applicata).
  error_log('[Allonwheel] shelter_container query error: ' . $e->getMessage());
  $schema_ready = false;
  $ads = [];
}

$is_logged_in = is_user_logged_in();

function shelterBadge(string $type): string
{
  $map = [
    'New on sell'  => 'New — for sale',
    'Used on sell' => 'Used — for sale',
    'For rent'     => 'For rent',
    'Project'      => 'Project',
  ];
  return $map[$type] ?? $type;
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — Shelter / Container</title>
<meta name="keywords" content="shelter, container, special vehicles, mobile units" />
<meta name="description" content="Shelter and container units on All on Wheel — classified under Special." />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<?php if (defined('BASE_URL')) echo '<link rel="canonical" href="' . htmlspecialchars(rtrim(BASE_URL, '/') . '/shelter_container.php', ENT_QUOTES) . '" />'; ?>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Shelter and Container</div>
    <div id="search_box">
      <form action="" method="get">
        <input type="text" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <?php if (empty($ads)): ?>
    <div class="post_box">
      <h2>No units published yet</h2>
      <?php if (!$schema_ready): ?>
        <p>This section is being set up. Please check back soon.</p>
      <?php elseif ($search !== ''): ?>
        <p>No shelter / container units match your search.
           <a href="shelter_container.php" class="more">View all</a></p>
      <?php else: ?>
        <p>There are no shelter / container units published at the moment.
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
      $aow_type_label = 'shelterBadge';
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
