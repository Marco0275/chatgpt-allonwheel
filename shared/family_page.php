<?php
// ============================================================
// shared/family_page.php  Renderer di una PAGINA DEDICATA a una famiglia
//
// Dir. 21 (16 lug 2026): nel corpo della pagina NON ci sono filtri.
// Ogni pagina e' dedicata a UN argomento/famiglia; i filtri vivono solo
// nelle sidebar. Qui quindi non c'e' nessuna chip-bar, nessuna faccetta,
// nessun parametro ?macro=/?cat=/?vtype= letto dall'URL: la famiglia e'
// fissata dalla pagina chiamante, non negoziabile via query string.
//
// USO (pagina thin chiamante, es. race_trailers.php):
//     $aow_family_slug = 'race-trailer';
//     require __DIR__ . '/shared/family_page.php';
//
// Variabili opzionali impostabili dalla pagina chiamante:
//     $aow_family_title  - <title> SEO (default: nome macro + brand)
//     $aow_family_desc   - meta description
//     $aow_family_self   - nome file per il canonical (default: SCRIPT_NAME)
//
// Dati: solo quelli realmente nel DB (dir. 14). Nessuno stile nuovo (dir. 8):
// riusa post_box / gallery / badge / price gia' esistenti.
// ============================================================

if (!isset($aow_family_slug) || $aow_family_slug === '') {
    http_response_code(500);
    exit('family_page.php: $aow_family_slug non impostato dalla pagina chiamante.');
}

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/product_macro.class.php';

// La famiglia deve esistere davvero (dir. 14): niente pagine fantasma.
if (!ProductMacro::exists($aow_family_slug)) {
    http_response_code(404);
    exit('Unknown product family.');
}

$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// ---- Dati della famiglia (nome, intro, hero) da product_macros ----
$aow_fam_label = '';
$aow_fam_intro = '';
$aow_fam_hero  = '';
try {
    $st = $pdo->prepare("SELECT name, intro_text, intro_text_it, hero_image FROM `product_macros` WHERE slug = :s LIMIT 1");
    $st->execute([':s' => $aow_family_slug]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $aow_fam_label = (string)$row['name'];
        $aow_fam_intro = function_exists('aow_i18n_field') ? trim(aow_i18n_field($row, 'intro_text')) : trim((string)$row['intro_text']);
        $aow_fam_hero  = trim((string)($row['hero_image'] ?? ''));
    }
} catch (PDOException $e) {
    error_log('[Allonwheel] family_page macro lookup: ' . $e->getMessage());
}
if ($aow_fam_label === '') { $aow_fam_label = ucwords(str_replace('-', ' ', $aow_family_slug)); }

// ---- Ricerca testuale ----
// Il campo Search e' presente in tutte le pagine tranne index e _admin
// (convenzione confermata 16 lug 2026) e cerca DENTRO l'argomento della
// pagina, come gia' fa road_vehicles.php. Non e' un filtro ai sensi della
// dir. 21: le faccette restano solo nelle sidebar.
$aow_q = trim($_GET['q'] ?? '');
$aow_search_clause = '';
if ($aow_q !== '') {
    $aow_search_clause = ' AND (title LIKE ? OR subtitle LIKE ? OR description LIKE ?)';
}

// ---- Annunci della famiglia (free + premium unificati, dir. 14) ----
// Vincolo fisso: la famiglia (non modificabile via URL). Nessuna faccetta.
$aow_ads = [];
try {
    $sql = "
      SELECT id_ads, title, subtitle, list_price, type, conditions,
             image_original, image_thumbnail, description, author, created_at, id_user,
             'free' AS ad_source, 0 AS is_prem,
             '02_free_ads/02_view_ad.php' AS detail_url,
             '/upload_image/02_free_ads/' AS upload_path
      FROM `02_free_ads`
      WHERE status = 'approved' AND product_macro = ?{$aow_search_clause}

      UNION ALL

      SELECT id_ads, title, subtitle, list_price, type, conditions,
             image_original, image_thumbnail, description, author, created_at, id_user,
             'premium' AS ad_source, 1 AS is_prem,
             '03_ads/03_view_ad.php' AS detail_url,
             '/upload_image/03_ads/' AS upload_path
      FROM `03_ads`
      WHERE status = 'approved' AND product_macro = ?{$aow_search_clause}

      ORDER BY is_prem DESC, created_at DESC, id_ads DESC
    ";
    $like = '%' . $aow_q . '%';
    $bind_branch = [$aow_family_slug];
    if ($aow_q !== '') { array_push($bind_branch, $like, $like, $like); }
    $st = $pdo->prepare($sql);
    $st->execute(array_merge($bind_branch, $bind_branch)); // stessi parametri per i due rami
    $aow_ads = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Allonwheel] family_page listing: ' . $e->getMessage());
    $aow_ads = [];
}

// Fornitori certificati ISO: badge di fiducia sulla card (dati reali, dir. 14)
$aow_cert_users = [];
try {
    $rows = $pdo->query("SELECT user_id FROM `06_company` WHERE cert_iso9001 <> '' OR cert_iso14001 <> '' OR cert_iso45001 <> ''")->fetchAll(PDO::FETCH_COLUMN);
    $aow_cert_users = array_flip(array_map('intval', $rows));
} catch (PDOException $e) {
    $aow_cert_users = [];
}

// Etichetta leggibile del campo `type` (stessa resa di browse.php)
if (!function_exists('aow_family_badge')) {
    function aow_family_badge(string $type): string {
        $map = [
            'racing' => 'Racing', 'hospitality' => 'Hospitality', 'motorhome' => 'Motorhome',
            'promotion' => 'Promotion', 'horse' => 'Horse', 'medical' => 'Medical',
            'military' => 'Military', 'technology' => 'Technology', 'street_food' => 'Street food',
        ];
        $t = trim($type);
        return $map[$t] ?? ($t !== '' ? ucfirst(str_replace('_', ' ', $t)) : 'Vehicle');
    }
}

// ---- SEO: una pagina = un argomento, quindi canonical pulito e stabile ----
$aow_self  = $aow_family_self  ?? basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$aow_title = $aow_family_title ?? ($aow_fam_label . ' for sale - All on Wheel');
$aow_desc  = $aow_family_desc  ?? ('Browse ' . $aow_fam_label . ' listings from specialised European builders and suppliers. Request a quotation from verified companies.');
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale()) : 'en'; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo htmlspecialchars($aow_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($aow_desc); ?>" />
<meta name="robots" content="index, follow" />
<meta name="copyright" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<link rel="canonical" href="<?php echo htmlspecialchars($base_url . $aow_self, ENT_QUOTES); ?>" />
<link href="<?php echo $base_url; ?>allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="<?php echo $base_url; ?>favicon.ico" />
<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>ddsmoothmenu.css" />
<link href="<?php echo $base_url; ?>css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?php echo $base_url; ?>js/jquery.min.js" defer></script>
<script type="text/javascript" src="<?php echo $base_url; ?>js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="<?php echo $base_url; ?>js/site_init.js" defer></script>
<?php
// D2 (SEO): BreadcrumbList (con famiglia) + ItemList degli annunci.
$aow_fb = rtrim($base_url, '/');
$aow_fcr = [
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$aow_fb.'/'],
    ['@type'=>'ListItem','position'=>2,'name'=>'Marketplace','item'=>$aow_fb.'/browse.php'],
    ['@type'=>'ListItem','position'=>3,'name'=>$aow_fam_label,'item'=>$aow_fb.'/'.$aow_self],
];
$aow_fit = []; $aow_fp = 0;
if (!empty($aow_ads) && is_array($aow_ads)) { foreach ($aow_ads as $aow_fa) {
    $aow_fdu = (string)($aow_fa['detail_url'] ?? ''); if ($aow_fdu === '') { continue; }
    $aow_fp++;
    $aow_fu = $aow_fb.'/'.$aow_fdu.(strpos($aow_fdu,'?')===false?'?':'&').'id_ads='.(int)($aow_fa['id_ads'] ?? 0);
    $aow_fit[] = ['@type'=>'ListItem','position'=>$aow_fp,'url'=>$aow_fu,'name'=>(string)($aow_fa['title'] ?? '')];
    if ($aow_fp >= 50) { break; }
} }
$aow_fld = ['@context'=>'https://schema.org','@graph'=>[
    ['@type'=>'BreadcrumbList','itemListElement'=>$aow_fcr],
    ['@type'=>'ItemList','itemListElement'=>$aow_fit],
]];
echo '<script type="application/ld+json">'.json_encode($aow_fld, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>'."\n";
?>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <!-- Dir. 21: titolo dell'argomento + campo Search (presente in tutte le
       pagine tranne index e _admin). Nessuna faccetta: quelle stanno solo
       nelle sidebar. La ricerca resta dentro questa famiglia. -->
  <div id="content_top">
    <div id="page_title"><?php echo htmlspecialchars($aow_fam_label); ?></div>
    <div id="search_box">
      <form action="" method="get">
        <input type="text" value="<?php echo htmlspecialchars($aow_q, ENT_QUOTES, 'UTF-8'); ?>" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search'); ?>" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
        <?php if ($aow_q !== ''): ?>
        <a href="<?php echo htmlspecialchars($aow_self, ENT_QUOTES); ?>" class="clear_link">&#10005; <?php te('search.clear','Clear'); ?></a>
        <?php endif; ?>
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if ($aow_fam_intro !== '' || $aow_fam_hero !== ''): ?>
    <div class="post_box">
      <?php if ($aow_fam_hero !== ''): ?>
      <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($aow_fam_hero, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($aow_fam_label, ENT_QUOTES, 'UTF-8'); ?>" class="macro_hero" />
      <?php endif; ?>
      <?php if ($aow_fam_intro !== ''): ?><p><?php echo nl2br(htmlspecialchars($aow_fam_intro)); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($aow_ads)): ?>
    <div class="post_box">
      <h2><?php echo htmlspecialchars($aow_fam_label); ?></h2>
      <p><?php te('family.empty', 'No listings in this family yet.'); ?>
         <a href="<?php echo $base_url; ?>04_request_offer/04_request_offer.php"><?php te('family.empty_rfq', 'Request a quotation'); ?></a>
         <?php te('family.empty_tail', 'and we will put you in touch with specialised builders.'); ?></p>
    </div>
    <?php else: ?>

    <?php foreach ($aow_ads as $ad):
      $is_premium  = ($ad['ad_source'] === 'premium');
      $upload_path = $ad['upload_path'];
      $thumb = trim((string)($ad['image_thumbnail'] ?? ''));
      $orig  = trim((string)($ad['image_original']  ?? ''));
      $thumb_url = ($thumb !== '' && $thumb !== 'no_image.jpg') ? $upload_path . 'thumbnail/' . $thumb : $base_url . 'images/no_image.jpg';
      $orig_url  = ($orig  !== '' && $orig  !== 'no_image.jpg') ? $upload_path . 'original/'  . $orig  : $thumb_url;
      $price = (float)$ad['list_price'];
      $desc  = (string)($ad['description'] ?? '');
      $short = mb_strlen($desc) > 220 ? mb_substr($desc, 0, 220) . '...' : $desc;
      $detail_url = $base_url . $ad['detail_url'] . '?id_ads=' . (int)$ad['id_ads'];
    ?>
    <?php
      // Card unificata (17 lug 2026): stesso formato di browse.php e delle
      // altre pagine dedicate. Markup unico in shared/ad_card.php.
      $aow_ad = $ad;
      $aow_ad['is_premium'] = ($ad['ad_source'] === 'premium');
      $aow_type_label = 'aow_family_badge';
      include __DIR__ . '/ad_card.php';
    ?>
    <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- end templatemo_content -->

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
  <div class="cleaner"></div>
  <?php include __DIR__ . '/../footer.php'; ?>
</div>
</body>
</html>
