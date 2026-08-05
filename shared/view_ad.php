<?php
// ============================================================
// shared/view_ad.php — Dettaglio annuncio unificato (02 e 03)
//
// REV PHASE 5b:
//  - URL immagini con BASE_URL prefix.
//  - Rimossi inline-script piroBox/ddsmoothmenu (delegato a site_init.js).
//  - onerror fallback su <img>.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

if (!isset($module) || !is_array($module)) {
  http_response_code(500);
  error_log('[Allonwheel] shared/view_ad.php chiamato senza $module');
  exit('Internal configuration error.');
}

$ALLOWED_TABLES = ['02_free_ads', '03_ads'];
if (!isset($module['table']) || !in_array($module['table'], $ALLOWED_TABLES, true)) {
  http_response_code(500);
  exit('Internal configuration error.');
}

$table   = $module['table'];
$upload_path = $module['upload_path'] ?? ('/upload_image/' . $table . '/');
$list_url  = $module['list_url']  ?? '#';
$gallery_url = $module['gallery_url'] ?? '#';
$tech_url  = $module['tech_url']  ?? null;
$page_title  = $module['page_title']  ?? 'View ad';

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

if (!isset($_GET['id_ads'])) {
  header('Location: ' . $list_url);
  exit;
}
$id_ads = (int)$_GET['id_ads'];
if ($id_ads <= 0) {
  header('Location: ' . $list_url);
  exit;
}

$sql = sprintf('SELECT * FROM `%s` WHERE id_ads = :id_ads LIMIT 1', $table);

try {
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':id_ads', $id_ads, PDO::PARAM_INT);
  $stmt->execute();
  $ad = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log('[Allonwheel] shared/view_ad query error: ' . $e->getMessage());
  header('Location: ' . $list_url);
  exit;
}

if (!$ad) {
  header('Location: ' . $list_url);
  exit;
}

// Conteggio visualizzazioni (views totali + unique per sessione). Non blocca la pagina.
if (in_array($table, ['02_free_ads', '03_ads'], true)) {
  try {
    $aow_seen_key  = 'aow_viewed_' . $table . '_' . $id_ads;
    $aow_is_unique = empty($_SESSION[$aow_seen_key]) ? 1 : 0;
    $aow_vs = $pdo->prepare(
      'INSERT INTO `seller_statistics` (id_ads, ad_table, views, unique_views)
       VALUES (:a, :t, 1, :uv)
       ON DUPLICATE KEY UPDATE views = views + 1, unique_views = unique_views + :uv2'
    );
    $aow_vs->execute([':a' => $id_ads, ':t' => $table, ':uv' => $aow_is_unique, ':uv2' => $aow_is_unique]);
    $_SESSION[$aow_seen_key] = true;
  } catch (Throwable $aow_ex) {
    error_log('[Allonwheel] view count: ' . $aow_ex->getMessage());
  }
}

// Badge per tier del proprietario dell'annuncio (Featured=Gold, Premium=Premium)
require_once __DIR__ . '/../libs/plan_policy.class.php';
$aow_owner_tier = 'free';
try {
    $ots = $pdo->prepare('SELECT user_tier FROM `users` WHERE id_user = ? LIMIT 1');
    $ots->execute([(int)($ad['id_user'] ?? 0)]);
    $aow_owner_tier = (string)($ots->fetchColumn() ?: 'free');
} catch (PDOException $e) { /* fallback free */ }
$aow_badge = PlanPolicy::badge($aow_owner_tier);

$thumb = trim((string)($ad['image_thumbnail'] ?? ''));
$orig  = trim((string)($ad['image_original']  ?? ''));
$thumb_url = ($thumb !== '' && $thumb !== 'no_image.jpg')
  ? $asset_base . $upload_path . 'thumbnail/' . $thumb
  : '../images/no_image.jpg';
$orig_url  = ($orig  !== '' && $orig  !== 'no_image.jpg')
  ? $asset_base . $upload_path . 'original/' . $orig
  : $thumb_url;
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($ad['title']); ?></title>
<meta name="keywords" content="<?php echo htmlspecialchars($ad['title']); ?>" />
<meta name="description" content="<?php echo htmlspecialchars(mb_substr((string)($ad['description'] ?? ''), 0, 160)); ?>" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
<?php
$seo_canonical = ltrim((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '?id_ads=' . (int)$id_ads;
$aow_site   = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
// Gli URL delle immagini sono relativi alla cartella dello script ('../images/..'):
// concatenarli al dominio produceva indirizzi tipo https://sito/../images/x.jpg,
// che i crawler scartano. Qui i segmenti '..' vengono risolti davvero.
$aow_abs    = static function (string $u) use ($aow_site): string {
    if ($u === '' || preg_match('#^https?://#i', $u)) { return $u; }
    $dir  = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    $path = ($u[0] === '/') ? $u : $dir . '/' . $u;
    $out  = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') { continue; }
        if ($seg === '..') { array_pop($out); continue; }
        $out[] = $seg;
    }
    return $aow_site . '/' . implode('/', $out);
};
// $seo_canonical e' gia' relativo alla radice del sito, quindi non passa dal
// risolutore dei percorsi relativi. Va calcolato qui perche' seo_head.php lo
// cancella dopo l'uso.
$aow_canon_abs = $aow_site . '/' . ltrim($seo_canonical, '/');
$aow_desc   = mb_substr(trim(strip_tags((string)($ad['description'] ?? ''))), 0, 300);
$aow_img_abs = !empty($orig_url) ? $aow_abs((string)$orig_url) : '';

$aow_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => (string)$ad['title'],
    'description' => $aow_desc,
    'url' => $aow_canon_abs,
];

// Le immagini in schema.org devono essere URL assoluti: con il percorso
// relativo Google le scartava e la scheda restava senza foto nei risultati.
if ($aow_img_abs !== '') { $aow_ld['image'] = $aow_img_abs; }
if (!empty($ad['vehicle_type'])) {
    $aow_ld['category'] = (string)$ad['vehicle_type'];
}
if (!empty($ad['author'])) {
    $aow_ld['brand'] = ['@type' => 'Organization', 'name' => (string)$ad['author']];
}
$aow_dims = [];
foreach (['length_mt' => 'depth', 'width_mt' => 'width', 'height_mt' => 'height'] as $col => $prop) {
    if (!empty($ad[$col])) {
        $aow_dims[$prop] = ['@type' => 'QuantitativeValue', 'value' => (float)$ad[$col], 'unitCode' => 'MTR'];
    }
}
$aow_ld += $aow_dims;

$aow_price = (float)($ad['list_price'] ?? 0);
if ($aow_price > 0) {
    // La condizione ha cinque valori nel database: schiacciarli su
    // New/Used faceva sparire "as good as new" e "project" dai rich result.
    $aow_cond_map = [
        'New'              => 'https://schema.org/NewCondition',
        'As good as new'   => 'https://schema.org/RefurbishedCondition',
        'Used'             => 'https://schema.org/UsedCondition',
        'Poor'             => 'https://schema.org/UsedCondition',
        'Project'          => 'https://schema.org/DamagedCondition',
    ];
    $aow_ld['offers'] = [
        '@type' => 'Offer',
        'price' => number_format($aow_price, 2, '.', ''),
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => $aow_cond_map[(string)($ad['conditions'] ?? '')] ?? 'https://schema.org/UsedCondition',
        'url' => $aow_canon_abs,
    ];
}

// Briciole di pane: erano assenti su tutto il sito, quindi nei risultati di
// ricerca ogni annuncio compariva senza contesto di categoria.
$aow_crumbs = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $aow_site . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Marketplace', 'item' => $aow_site . '/browse.php'],
];
$aow_crumb_cat = trim((string)($ad['product_macro'] ?? ''));
if ($aow_crumb_cat !== '') {
    $aow_crumbs[] = ['@type' => 'ListItem', 'position' => 3,
        'name' => ucwords(str_replace('-', ' ', $aow_crumb_cat)),
        'item' => $aow_site . '/browse.php?macro=' . rawurlencode($aow_crumb_cat)];
}
$aow_crumbs[] = ['@type' => 'ListItem', 'position' => count($aow_crumbs) + 1,
    'name' => (string)$ad['title'], 'item' => $aow_canon_abs];

$seo_jsonld = ['@context' => 'https://schema.org', '@graph' => [
    $aow_ld,
    ['@type' => 'BreadcrumbList', 'itemListElement' => $aow_crumbs],
]];
unset($seo_jsonld['@graph'][0]['@context']);
include __DIR__ . '/../includes/seo_head.php';

// Open Graph: senza questi tag un annuncio condiviso su WhatsApp, LinkedIn o
// in una chat interna appare come un link nudo, senza foto ne' titolo — e la
// condivisione tra colleghi e' uno dei pochi canali gratuiti di questo mercato.
?>
<meta property="og:type" content="product" />
<meta property="og:site_name" content="All on Wheel" />
<meta property="og:title" content="<?php echo htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($aow_desc, ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:url" content="<?php echo htmlspecialchars($aow_canon_abs, ENT_QUOTES, 'UTF-8'); ?>" />
<?php if ($aow_img_abs !== ''): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($aow_img_abs, ENT_QUOTES, 'UTF-8'); ?>" />
<?php endif; ?>
<meta name="twitter:card" content="<?php echo $aow_img_abs !== '' ? 'summary_large_image' : 'summary'; ?>" />
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <?php // Briciole visibili: orientano l'utente e danno un percorso di
          // risalita alla categoria, che prima non esisteva. ?>
    <nav class="aow_crumbs" aria-label="Breadcrumb">
      <a href="<?php echo $base_url; ?>index.php">Home</a> &rsaquo;
      <a href="<?php echo $base_url; ?>browse.php">Marketplace</a>
      <?php if ($aow_crumb_cat !== ''): ?>
        &rsaquo; <a href="<?php echo $base_url; ?>browse.php?macro=<?php echo rawurlencode($aow_crumb_cat); ?>"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $aow_crumb_cat)), ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endif; ?>
      &rsaquo; <span aria-current="page"><?php echo htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8'); ?></span>
    </nav>
    <div id="page_title"><?php echo htmlspecialchars($ad['title']); ?></div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    

    <h6><em><?php echo htmlspecialchars((string)($ad['subtitle'] ?? '')); ?></em></h6>
    <?php if ($aow_badge === 'Featured'): ?><p class="badges"><span class="badge badge_featured">Featured</span></p>
    <?php elseif ($aow_badge === 'Premium'): ?><p class="badges"><span class="badge badge_premium">Premium</span></p><?php endif; ?>

    <a href="#contact_form" class="aow-sticky-cta"><?php te('cta.request_quote','Request quotation'); ?></a>
    <div id="contact_form" class="ad_detail">

    <div class="gallery_box">
      <ul class="gallery">
        <li>
        <a class="pirobox"
         href="<?php echo htmlspecialchars($orig_url); ?>"
         title="<?php echo htmlspecialchars($ad['title']); ?>">
          <img src="<?php echo htmlspecialchars($thumb_url); ?>"
           alt="<?php echo htmlspecialchars($ad['title']); ?>"
           width="220" height="150" border="0" loading="lazy" decoding="async" />
        </a>
        </li>
      </ul>
    </div>

    <div class="cleaner h10"></div>

    <div>
      <a class="more float_r" href="<?php echo htmlspecialchars($gallery_url); ?>?id_ads=<?php echo $id_ads; ?>">Gallery</a>
    </div>

    <div class="cleaner h10"></div>

        <?php if ($tech_url !== null): ?>
      <div>
        <a class="more float_r" href="<?php echo htmlspecialchars($tech_url); ?>?id_ads=<?php echo $id_ads; ?>">Tech details</a>
      </div>
      <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php /* Stampa PDF dell'annuncio: disponibile per FREE e PREMIUM (endpoint condiviso). */ ?>
    <div>
      <a class="more float_r" href="<?php echo $base_url; ?>shared/ad_pdf.php?id_ads=<?php echo $id_ads; ?>&amp;t=<?php echo urlencode((string)$table); ?>"><?php te('ad.pdf','Download PDF'); ?></a>
    </div>
    <div class="cleaner h10"></div>

    <?php
      // Documenti tecnici (asset di conversione) — download tracciato via proxy.
      require_once __DIR__ . '/../config/session_helper.php';
      require_once __DIR__ . '/../libs/ads_documents.class.php';
      $aow_docs  = (new AdsDocuments($pdo))->listByAd($id_ads, $table);
      $aow_uid   = current_user_id();
      $aow_owner = ($aow_uid !== null && (int)($ad['id_user'] ?? 0) === $aow_uid);
    ?>
    <?php /* Documenti tecnici: SOLO annunci premium (free = prima pagina + gallery, rev. 7 lug) */ ?>
    <?php if ($table === '03_ads' && (!empty($aow_docs) || $aow_owner)): ?>
      <div class="float_l"><strong>Technical documents:</strong></div>
      <div class="cleaner h10"></div>
      <?php if (!empty($aow_docs)): ?>
        <ul>
        <?php foreach ($aow_docs as $aow_d): ?>
          <li><a href="<?php echo $base_url; ?>download_doc.php?id=<?php echo (int)$aow_d['id']; ?>"><?php echo htmlspecialchars((string)$aow_d['original_name']); ?></a></li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($aow_owner): ?>
        <div><a class="more float_r" href="<?php echo $base_url; ?>03_ads/03_documents.php?id_ads=<?php echo $id_ads; ?>&amp;ad_table=<?php echo urlencode((string)$table); ?>">Manage documents</a></div>
        <div class="cleaner h10"></div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="float_l"><strong>Author:</strong> <?php echo htmlspecialchars($ad['author']); ?></div>
    <div class="cleaner h10"></div>

    <div class="float_l"><strong>Title:</strong> <?php echo htmlspecialchars($ad['title']); ?></div>
    <div class="cleaner h10"></div>

    <div class="float_l"><strong>Type:</strong> <?php echo htmlspecialchars($ad['type']); ?></div>
    <div class="cleaner h10"></div>

    <div class="float_l"><strong>Condition:</strong> <?php echo htmlspecialchars($ad['conditions']); ?></div>
    <div class="cleaner h10"></div>

    <div class="float_l"><strong>List price:</strong> <?php echo number_format((float)$ad['list_price'], 2); ?> &euro;</div>
    <div class="cleaner h10"></div>

    <?php // Misure (solo se valorizzate). In DB sono in cm; mostro in metri
          // per lunghezza/larghezza/altezza, piu' leggibile per un veicolo. ?>
    <?php if (!empty($ad['length_mt'])): ?>
    <div class="float_l"><strong>Length:</strong> <?php echo htmlspecialchars(number_format($ad['length_mt'], 2)); ?> m</div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php if (!empty($ad['width_mt'])): ?>
    <div class="float_l"><strong>Width:</strong> <?php echo htmlspecialchars(number_format($ad['width_mt'], 2)); ?> m</div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php if (!empty($ad['height_mt'])): ?>
    <div class="float_l"><strong>Height:</strong> <?php echo htmlspecialchars(number_format($ad['height_mt'], 2)); ?> m</div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php if (!empty($ad['axles_n'])): ?>
    <div class="float_l"><strong>Axles:</strong> <?php echo (int)$ad['axles_n']; ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($ad['expires_at'])): ?>
    <?php
      $exp_ts   = strtotime($ad['expires_at']);
      $days_left = (int)ceil(($exp_ts - time()) / 86400);
    ?>
    <div class="float_l"><strong>Published until:</strong>
      <?php echo date('d/m/Y', $exp_ts); ?>
      (<?php echo $days_left > 0 ? $days_left . ' day' . ($days_left === 1 ? '' : 's') . ' left' : 'expired'; ?>)
    </div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <div class="cleaner h20"></div>
    <p><strong>Description:</strong></p>
		<blockquote>
    <p align="justify"><?php echo nl2br(htmlspecialchars((string)($ad['description'] ?? ''))); ?></p>
    </blockquote>
		<?php
    // --- Ponte tassonomico: fornitori per la categoria dell'annuncio (macro) ---
    $aow_ad_macro = trim((string)($ad['product_macro'] ?? ''));
    if ($aow_ad_macro !== '' && isset($pdo)) {
        require_once __DIR__ . '/../libs/06_company.class.php';
        require_once __DIR__ . '/../libs/product_macro.class.php';
        require_once __DIR__ . '/related_suppliers.php';
        try {
            $aow_cm  = new CompanyManager($pdo);
            $aow_sup = aow_related_suppliers($aow_cm, $aow_ad_macro, 8);
            if ($aow_sup) {
                aow_render_related_suppliers(
                    $aow_sup,
                    $base_url,
                    ProductMacro::label($aow_ad_macro, $pdo),
                    $base_url . '06_company/06_30_company_directory.php'
                );
            }
        } catch (Throwable $e) { /* silenzioso: il ponte non deve mai rompere la scheda */ }
    }
    ?>
    <div class="cleaner h20"></div>
    <?php
    // D (P2.14): annunci correlati della stessa categoria (riusa ad_card.php).
    if (($aow_ad_macro ?? '') !== '' && isset($pdo)) {
        try {
            $aow_rl = $pdo->prepare(
              "SELECT id_ads, title, subtitle, list_price, type, conditions, image_original, image_thumbnail, description, author, created_at, id_user, '02_free_ads/02_view_ad.php' AS detail_url, 0 AS is_prem, '/upload_image/02_free_ads/' AS upload_path FROM `02_free_ads` WHERE product_macro = :m AND status = 'approved' AND id_ads <> :id "
            . "UNION ALL "
            . "SELECT id_ads, title, subtitle, list_price, type, conditions, image_original, image_thumbnail, description, author, created_at, id_user, '03_ads/03_view_ad.php' AS detail_url, 1 AS is_prem, '/upload_image/03_ads/' AS upload_path FROM `03_ads` WHERE product_macro = :m2 AND status = 'approved' AND id_ads <> :id2 "
            . "ORDER BY id_ads DESC LIMIT 4"
            );
            $aow_rl->execute([':m' => $aow_ad_macro, ':id' => (int)$id_ads, ':m2' => $aow_ad_macro, ':id2' => (int)$id_ads]);
            $aow_related = $aow_rl->fetchAll(PDO::FETCH_ASSOC);
            if ($aow_related) {
                echo '<h3>' . htmlspecialchars(function_exists('t') ? t('view.related_listings', 'Related listings') : 'Related listings') . '</h3>';
                echo '<div class="products_box">';
                $aow_ad_backup = $aow_ad;
                foreach ($aow_related as $aow_r) {
                    $aow_ad = $aow_r; $aow_ad['is_premium'] = (bool)($aow_r['is_prem'] ?? 0);
                    include __DIR__ . '/ad_card.php';
                }
                $aow_ad = $aow_ad_backup;
                echo '</div>';
            }
        } catch (Throwable $e) { /* silenzioso */ }
    }
    ?>
    <div class="cleaner h20"></div>

    <a class="more float_r" href="<?php echo htmlspecialchars($list_url); ?>">Back</a>

    <div class="cleaner"></div>
    </div>

  </div>

<?php echo '<!-- SIDEBAR START -->'; ?>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

<?php echo '<!-- FOOTER START -->'; ?>
<?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
