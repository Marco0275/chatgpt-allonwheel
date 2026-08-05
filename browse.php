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
require_once __DIR__ . '/libs/product_macro.class.php';

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

// Dir. 21: le famiglie hanno una PAGINA DEDICATA. I vecchi URL a faccetta
// (browse.php?macro=<slug>) restano validi ma rimandano con 301 alla pagina
// dell'argomento, cosi' i link e i bookmark esistenti non si rompono (dir. 19)
// e ogni famiglia ha un solo URL canonico.
$aow_family_pages = [
    'race-trailer'      => 'race_trailers.php',
    'hospitality'       => 'hospitality.php',
    'mobile-clinic'     => 'mobile_clinics.php',
    'shelter-container' => 'shelter_container.php',
    'custom-projects'   => 'custom_projects.php',
    'rentals'           => '07_rent/07_20_rent_list.php',
];
$aow_req_macro = trim($_GET['macro'] ?? '');
if ($aow_req_macro !== '' && isset($aow_family_pages[$aow_req_macro])) {
    $aow_dest = (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/') . $aow_family_pages[$aow_req_macro];
    header('Location: ' . $aow_dest, true, 301);
    exit;
}

$active_macro = trim($_GET['macro'] ?? '');
if (!ProductMacro::exists($active_macro)) {
    $active_macro = '';
}

// ---- Filtro vehicle_type (live da DB: nuovi tipi admin compaiono da soli) ----
// Nuova tassonomia (24 lug 2026): le tipologie stanno in DUE tabelle.
//   Road    -> vehicle_types (lista del codice della strada)
//   Special -> special_types (lista curata dall'admin); lo shelter usa la
//              stessa lista, perche' e' lo stesso allestimento su container.
// Si continua a portare 'macro_category' in ogni riga, cosi' il resto della
// pagina (che filtra per macro) non cambia di una virgola.
require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';
$all_vtypes = [];

$aow_types = VehicleTaxonomy::allTypesGrouped($pdo);

foreach ($aow_types as $aow_cat => $aow_rows) {

    foreach ($aow_rows as $aow_r) {

        if (!is_array($aow_r)) {
            $aow_r = [
                'slug' => (string)$aow_r,
                'name' => ucfirst(str_replace('_', ' ', (string)$aow_r))
            ];
        }

        $aow_r['macro_category'] = $aow_cat;
        $all_vtypes[] = $aow_r;
    }
}

$vtype_slugs = array_column($all_vtypes, 'slug');
$active_vtype = trim($_GET['vtype'] ?? '');
if (!in_array($active_vtype, $vtype_slugs, true)) { $active_vtype = ''; }

// ---- Faccette aggiuntive (server-side, solo dati reali) ----
$cond_set = ['New', 'As good as new', 'Used', 'Poor', 'Project'];
$cond_selected = array_values(array_intersect((array)($_GET['cond'] ?? []), $cond_set));
$rs_set = ['road', 'special'];
$rs_selected = array_values(array_intersect((array)($_GET['rs'] ?? []), $rs_set));
$pmin = (isset($_GET['pmin']) && $_GET['pmin'] !== '') ? max(0, (int)$_GET['pmin']) : null;
$pmax = (isset($_GET['pmax']) && $_GET['pmax'] !== '') ? max(0, (int)$_GET['pmax']) : null;

// Elenco macro per la faccetta Family (sidebar)
$all_macros = [];
try {
    $all_macros = $pdo->query('SELECT slug, name FROM `product_macros` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $all_macros = []; }
// Famiglia sintetica "Rentals": punta alla sezione noleggio (07_rent).
$all_macros[] = ['slug' => 'rentals', 'name' => 'Rentals'];

// Helper: URL corrente senza una faccetta (per i chip "rimuovi")
function aow_facet_remove(string $key, ?string $val = null): string {
    $q = $_GET;
    if ($val !== null && isset($q[$key]) && is_array($q[$key])) {
        $q[$key] = array_values(array_filter($q[$key], static fn($v) => (string)$v !== $val));
        if (!$q[$key]) { unset($q[$key]); }
    } else {
        unset($q[$key]);
    }
    $qs = http_build_query($q);
    return 'browse.php' . ($qs !== '' ? '?' . $qs : '');
}

// Helper: costruisce la query string dei filtri preservando i parametri attivi.
if (!function_exists('aow_bqs')) {
    function aow_bqs(array $p): string {
        $p = array_filter($p, static function ($v) { return $v !== '' && $v !== null; });
        if (!$p) { return 'browse.php'; }
        $pairs = [];
        foreach ($p as $k => $v) { $pairs[] = urlencode((string)$k) . '=' . urlencode((string)$v); }
        return '?' . implode('&amp;', $pairs);
    }
}

// Intro di brand della macro selezionata (catalogo e marketplace fusi).
$macro_label = '';
$macro_intro = '';
$macro_hero  = ''; // hero_image della macro (se valorizzata in product_macros)
if ($active_macro !== '') {
    try {
        $stmt_macro = $pdo->prepare("SELECT name, intro_text, intro_text_it, hero_image FROM `product_macros` WHERE slug = :s LIMIT 1");
        $stmt_macro->execute([':s' => $active_macro]);
        if ($mrow = $stmt_macro->fetch(PDO::FETCH_ASSOC)) {
            $macro_label = (string)$mrow['name'];
            $macro_intro = trim(aow_i18n_field($mrow, 'intro_text'));
            $macro_hero  = trim((string)($mrow['hero_image'] ?? ''));
        }
    } catch (PDOException $e) {
        error_log('[Allonwheel] browse macro intro error: ' . $e->getMessage());
    }
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

$macro_clause = '';
if ($active_macro !== '') {
    $macro_clause = ' AND product_macro = ?';
    $bind[] = $active_macro; // un parametro per ramo (ordine: dopo search)
}

$vtype_clause = '';
if ($active_vtype !== '') {
    $vtype_clause = ' AND vehicle_type = ?';
    $bind[] = $active_vtype; // dopo macro: l'ordine dei bind segue l'ordine in WHERE
}

// Faccette: condition (IN), road/special (mappato su vehicle_type), prezzo (range)
$cond_clause = '';
if ($cond_selected) {
    $ph = implode(',', array_fill(0, count($cond_selected), '?'));
    $cond_clause = " AND conditions IN ($ph)";
    foreach ($cond_selected as $c) { $bind[] = $c; }
}
$rs_clause = '';
if ($rs_selected) {
    $rs_slugs = [];
    foreach ($all_vtypes as $vt) {
        if (in_array($vt['macro_category'], $rs_selected, true)) { $rs_slugs[] = $vt['slug']; }
    }
    if (in_array('special', $rs_selected, true)) { $rs_slugs[] = 'shelter_container'; }
    $rs_slugs = array_values(array_unique($rs_slugs));
    if ($rs_slugs) {
        $ph = implode(',', array_fill(0, count($rs_slugs), '?'));
        $rs_clause = " AND vehicle_type IN ($ph)";
        foreach ($rs_slugs as $s) { $bind[] = $s; }
    } else {
        $rs_clause = ' AND 1=0';
    }
}
$price_clause = '';
if ($pmin !== null) { $price_clause .= ' AND list_price >= ?'; $bind[] = $pmin; }
if ($pmax !== null) { $price_clause .= ' AND list_price <= ?'; $bind[] = $pmax; }

// ---- Visibilita' pubblica: solo annunci approvati (P1.5) ----
// Coerente con la gallery di index.php; evita di esporre nel marketplace
// pubblico annunci pending/rejected (dir. 11). 'approved' e' costante: in SQL.
$status_clause = " AND status = 'approved'";

// Duplica i parametri search per il secondo ramo del UNION
$bind_union = array_merge($bind, $bind); // bind × 2

// ---- Paginazione (27 lug 2026) ----
// La query non aveva LIMIT: ogni visita caricava in memoria e stampava TUTTI
// gli annunci approvati. Con l'inventario a zero non si notava; a 2.000
// annunci sarebbero 2.000 card e altrettante immagini in una sola pagina.
// Il conteggio totale serve anche a mostrare "N listings", informazione che
// oggi manca del tutto (l'utente non sa se sta guardando 4 o 400 risultati).
$per_page = 24;
$page     = max(1, (int)($_GET['page'] ?? 1));

$count_sql = "
SELECT COUNT(*) FROM (
  SELECT id_ads FROM `02_free_ads`
  WHERE 1=1 {$status_clause} {$search_clause} {$cat_clause} {$macro_clause} {$vtype_clause} {$cond_clause} {$rs_clause} {$price_clause} 
  UNION ALL
  SELECT id_ads FROM `03_ads`
  WHERE 1=1 {$status_clause} {$search_clause} {$cat_clause} {$macro_clause} {$vtype_clause} {$cond_clause} {$rs_clause} {$price_clause} 
) c";
$total_ads = 0;
try {
    $cst = $pdo->prepare($count_sql);
    $cst->execute($bind_union);
    $total_ads = (int)$cst->fetchColumn();
} catch (PDOException $e) {
    error_log('[Allonwheel] browse.php count query error: ' . $e->getMessage());
}
$total_pages = max(1, (int)ceil($total_ads / $per_page));
if ($page > $total_pages) { $page = $total_pages; }
$offset = ($page - 1) * $per_page;

$sql = "
SELECT x.*, u.user_tier AS owner_tier FROM (
  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at, id_user,
         'free'    AS ad_source,
         '02_free_ads/02_view_ad.php'  AS detail_url, 0 AS is_prem,
         '/upload_image/02_free_ads/'  AS upload_path
  FROM `02_free_ads`
  WHERE 1=1 {$status_clause} {$search_clause} {$cat_clause} {$macro_clause} {$vtype_clause} {$cond_clause} {$rs_clause} {$price_clause} 

  UNION ALL

  SELECT id_ads, title, subtitle, list_price, type, conditions,
         image_original, image_thumbnail, description, author, created_at, id_user,
         'premium' AS ad_source,
         '03_ads/03_view_ad.php'       AS detail_url, 1 AS is_prem,
         '/upload_image/03_ads/'       AS upload_path
  FROM `03_ads`
  WHERE 1=1 {$status_clause} {$search_clause} {$cat_clause} {$macro_clause} {$vtype_clause} {$cond_clause} {$rs_clause} {$price_clause} 

) x
  LEFT JOIN `users` u ON u.id_user = x.id_user
  ORDER BY CASE u.user_tier WHEN 'gold' THEN 0 WHEN 'premium' THEN 1 ELSE 2 END, x.is_prem DESC, x.created_at DESC, x.id_ads DESC
  LIMIT " . (int)$per_page . " OFFSET " . (int)$offset . "
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind_union);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Allonwheel] browse.php UNION query error: ' . $e->getMessage());
    $ads = [];
}

// Utenti con azienda certificata (ISO 9001/14001/45001): il badge "Certified
// supplier" sulle card rinforza la fiducia nel punto di decisione (dati reali, dir. 14).
$cert_users = [];
try {
    $aow_rows = $pdo->query("SELECT user_id FROM `06_company` WHERE cert_iso9001 <> '' OR cert_iso14001 <> '' OR cert_iso45001 <> ''")->fetchAll(PDO::FETCH_COLUMN);
    $cert_users = array_flip(array_map('intval', $aow_rows));
} catch (Throwable $e) { $cert_users = []; }

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
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel — Browse all listings</title>
<meta name="description" content="Browse all special vehicle listings — free and premium ads on All on Wheel marketplace." />
<?php // Le pagine oltre la prima non portano valore nell'indice ma consumano
      // crawl budget: restano scansionabili (i link agli annunci vanno
      // seguiti) e non indicizzate. Un solo tag robots per pagina. ?>
<meta name="robots" content="<?php echo $page > 1 ? 'noindex, follow' : 'index, follow'; ?>" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<?php $aow_canon = 'browse.php' . ($active_macro !== '' ? '?macro=' . rawurlencode($active_macro) : '');
// La paginazione ha bisogno del proprio canonico: senza, pagina 2, 3, 4...
// dichiarerebbero tutte lo stesso URL e Google terrebbe solo la prima.
if ($page > 1) { $aow_canon .= (strpos($aow_canon, '?') === false ? '?' : '&') . 'page=' . (int)$page; }
if (defined('BASE_URL')) echo '<link rel="canonical" href="' . htmlspecialchars(rtrim(BASE_URL,'/') . '/' . $aow_canon, ENT_QUOTES) . '" />' . "
";
$aow_pg_url = static function (int $p): string {
    $q = $_GET; if ($p <= 1) { unset($q['page']); } else { $q['page'] = $p; }
    $qs = http_build_query($q);
    return 'browse.php' . ($qs !== '' ? '?' . $qs : '');
};
if ($page > 1) { echo '<link rel="prev" href="' . htmlspecialchars($aow_pg_url($page - 1), ENT_QUOTES) . '" />' . "
"; }
if ($page < $total_pages) { echo '<link rel="next" href="' . htmlspecialchars($aow_pg_url($page + 1), ENT_QUOTES) . '" />' . "
"; }
?>
<?php
// D2 (SEO): BreadcrumbList + ItemList dei risultati.
if (defined('BASE_URL')) {
    $aow_b = rtrim(BASE_URL, '/');
    $aow_crumbs = [
        ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$aow_b.'/'],
        ['@type'=>'ListItem','position'=>2,'name'=>'Marketplace','item'=>$aow_b.'/browse.php'],
    ];
    $aow_items = []; $aow_pos = 0;
    if (!empty($ads) && is_array($ads)) { foreach ($ads as $aow_a) {
        $aow_du = (string)($aow_a['detail_url'] ?? ''); if ($aow_du === '') { continue; }
        $aow_pos++;
        $aow_u = $aow_b.'/'.$aow_du.(strpos($aow_du,'?')===false?'?':'&').'id_ads='.(int)($aow_a['id_ads'] ?? 0);
        $aow_items[] = ['@type'=>'ListItem','position'=>$aow_pos,'url'=>$aow_u,'name'=>(string)($aow_a['title'] ?? '')];
        if ($aow_pos >= 50) { break; }
    } }
    $aow_ld = ['@context'=>'https://schema.org','@graph'=>[
        ['@type'=>'BreadcrumbList','itemListElement'=>$aow_crumbs],
        ['@type'=>'ItemList','itemListElement'=>$aow_items],
    ]];
    echo '<script type="application/ld+json">'.json_encode($aow_ld, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>'."
";
}
?>
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
    <div id="page_title"><?php echo ($active_macro !== '' && $macro_label !== '') ? htmlspecialchars($macro_label) : 'All listings';
      // Il conteggio dei risultati e' l'informazione che dice all'utente se la
      // ricerca ha senso: senza, non sa se restringere o allargare i filtri.
      if ($total_ads > 0): ?><span class="result_count"><?php echo (int)$total_ads; ?> <?php te($total_ads === 1 ? 'browse.listing' : 'browse.listings', $total_ads === 1 ? 'listing' : 'listings'); ?></span><?php endif; ?></div>
    <div id="search_box">
      <form action="" method="get">
        <?php if ($active_cat !== ''): ?>
          <input type="hidden" name="cat" value="<?php echo htmlspecialchars($active_cat); ?>" />
        <?php endif; ?>
        <?php if ($active_macro !== ''): ?>
          <input type="hidden" name="macro" value="<?php echo htmlspecialchars($active_macro); ?>" />
        <?php endif; ?>
        <input type="text"
               value="<?php echo htmlspecialchars($search); ?>"
               name="q" size="10" id="searchfield" title="Search listings"
               placeholder="Search…" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
        <?php if ($search !== ''): ?>
          <a href="?<?php echo $active_cat !== '' ? 'cat=' . htmlspecialchars($active_cat) : ''; ?>"
             class="clear_link">&#10005; Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if ($active_macro !== '' && ($macro_intro !== '' || $macro_hero !== '')): ?>
    <div class="post_box">
      <?php if ($macro_hero !== ''): ?>
      <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($macro_hero, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($macro_label, ENT_QUOTES, 'UTF-8'); ?>" class="macro_hero" />
      <?php endif; ?>
      <?php if ($macro_intro !== ''): ?><p><?php echo nl2br(htmlspecialchars($macro_intro)); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

	    <?php
    // Dir. 21 (16 lug 2026): niente filtri nel corpo pagina. La chip-bar dei
    // filtri attivi e' stata rimossa; i filtri restano validi via URL e la
    // loro UI vive solo nelle sidebar. Le famiglie hanno pagine dedicate
    // (?macro= -> 301, vedi in testa al file).
    ?>

    <?php // M4: flash esito salvataggio ricerca ?>
    <?php if (!empty($_SESSION['ss_flash'])): ?>
    <p class="done"><?php echo htmlspecialchars($_SESSION['ss_flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['ss_flash']); ?></p>
    <?php endif; ?>

    <?php
    // M4: salva questa ricerca (alert email). Visibile solo con un criterio attivo
    // (famiglia o testo): un alert "tutto il marketplace" sarebbe solo rumore.
    // Quando non c'e' alcun risultato il modulo di alert e' gia' dentro
    // l'empty state, con la possibilita' di lasciare l'email anche senza
    // account: mostrarne due, uno sopra l'altro, confonde e basta.
    if (($active_macro !== '' || $search !== '') && $total_ads > 0):
        if (function_exists('current_user_id') && current_user_id() !== null):
            require_once __DIR__ . '/config/csrf.php'; ?>
    <form method="post" action="saved_search_save.php">
      <?php echo csrf_generate(); ?>
      <input type="hidden" name="macro" value="<?php echo htmlspecialchars($active_macro, ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" />
      <label><?php te('ss.save_label', 'Get an email when new matching listings arrive:'); ?></label>
      <select name="freq">
        <option value="daily"><?php te('ss.freq_daily', 'As they arrive (daily check)'); ?></option>
        <option value="weekly"><?php te('ss.freq_weekly', 'Weekly digest'); ?></option>
      </select>
      <input type="submit" class="submit_btn" value="<?php te('ss.save_btn', 'Save this search'); ?>" />
    </form>
    <div class="cleaner h10"></div>
    <?php else:
        // Prima qui c'era solo un invito ad accedere: chiedere la
        // registrazione per ricevere una email e' un ostacolo sproporzionato,
        // e la richiesta si perdeva. Ora basta l'indirizzo, con conferma via
        // email (doppio opt-in) prima di attivare l'alert.
        require_once __DIR__ . '/config/csrf.php'; ?>
    <form method="post" action="saved_search_save.php" class="es_form">
      <?php echo csrf_generate(); ?>
      <div class="hp_field" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>
      <input type="hidden" name="macro" value="<?php echo htmlspecialchars($active_macro, ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" />
      <label for="ss_email"><?php te('ss.save_label', 'Get an email when new matching listings arrive:'); ?></label>
      <input type="email" id="ss_email" name="email" required="required" autocomplete="email" placeholder="name@company.com" />
      <select name="freq" aria-label="<?php te('ss.freq', 'Frequency'); ?>">
        <option value="daily"><?php te('ss.freq_daily', 'As they arrive (daily check)'); ?></option>
        <option value="weekly"><?php te('ss.freq_weekly', 'Weekly digest'); ?></option>
      </select>
      <input type="submit" class="submit_btn" value="<?php te('ss.save_btn', 'Save this search'); ?>" />
      <small><?php te('ss.save_privacy', 'We only use your address for this alert. You will receive a confirmation email first.'); ?></small>
    </form>
    <div class="cleaner h10"></div>
    <?php endif; endif; ?>

    <?php
    // --- Ponte tassonomico: fornitori correlati quando si filtra per macro ---
    if ($active_macro !== '' && isset($pdo)) {
        require_once __DIR__ . '/libs/06_company.class.php';
        require_once __DIR__ . '/shared/related_suppliers.php';
        try {
            $aow_cm  = new CompanyManager($pdo);
            $aow_sup = aow_related_suppliers($aow_cm, $active_macro, 8);
            if ($aow_sup) {
                aow_render_related_suppliers(
                    $aow_sup,
                    '',
                    ($macro_label !== '' ? $macro_label : $active_macro),
                    '06_company/06_30_company_directory.php'
                );
            }
        } catch (Throwable $e) { /* silenzioso */ }
    }
    ?>
    <?php if (empty($ads)): ?>
    <?php
      // Empty state (27 lug 2026).
      // Prima diceva soltanto "non ci sono annunci": un vicolo cieco che
      // rimandava a casa il 100% della domanda in arrivo, proprio nel momento
      // in cui l'utente ha dichiarato cosa cerca. Ora la ricerca a vuoto
      // diventa un lead: si registra la richiesta e si avvisa l'utente quando
      // arriva il primo annuncio compatibile. Su un marketplace in cold start
      // questa e' la pagina piu' importante del sito.
      $aow_empty_ctx = [
          'q'     => $search,
          'macro' => $active_macro,
          'vtype' => $active_vtype,
          'label' => $macro_label !== '' ? $macro_label : '',
      ];
      include __DIR__ . '/shared/empty_state.php';
    ?>

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

    <?php
      // Card unificata (17 lug 2026): un solo formato per tutte le pagine.
      // Il markup vive in shared/ad_card.php.
      $aow_ad = $ad;
      $aow_ad['is_premium'] = $is_premium;
      $aow_ad['is_prem']    = $is_premium ? 1 : 0;

      // Se l'annuncio e' free (02_free_ads), azzeriamo owner_tier per la card,
      // altrimenti ad_card.php mostra il badge Premium se l'utente proprietario e' un utente Premium.
      if (!$is_premium) {
          $aow_ad['owner_tier'] = 'free';
      }

      $aow_cert_users = $cert_users;
      $aow_type_label = 'browseBadge';
      include __DIR__ . '/shared/ad_card.php';
    ?>
    <?php endforeach; ?>

    <?php if ($total_pages > 1): ?>
    <div class="cleaner h20"></div>
    <nav class="aow_pager" aria-label="<?php te('browse.pagination','Listing pages'); ?>">
      <?php if ($page > 1): ?>
        <a class="pg" rel="prev" href="<?php echo htmlspecialchars($aow_pg_url($page - 1), ENT_QUOTES); ?>">&lsaquo; <?php te('browse.prev','Previous'); ?></a>
      <?php endif; ?>
      <?php
      // Finestra di pagine intorno a quella corrente: con molte pagine un
      // elenco completo diventa illeggibile.
      $from = max(1, $page - 2); $to = min($total_pages, $page + 2);
      if ($from > 1) { echo '<a class="pg" href="' . htmlspecialchars($aow_pg_url(1), ENT_QUOTES) . '">1</a>'; if ($from > 2) { echo '<span class="pg_gap">…</span>'; } }
      for ($p = $from; $p <= $to; $p++) {
          if ($p === $page) { echo '<span class="pg pg_cur" aria-current="page">' . $p . '</span>'; }
          else { echo '<a class="pg" href="' . htmlspecialchars($aow_pg_url($p), ENT_QUOTES) . '">' . $p . '</a>'; }
      }
      if ($to < $total_pages) { if ($to < $total_pages - 1) { echo '<span class="pg_gap">…</span>'; } echo '<a class="pg" href="' . htmlspecialchars($aow_pg_url($total_pages), ENT_QUOTES) . '">' . $total_pages . '</a>'; }
      ?>
      <?php if ($page < $total_pages): ?>
        <a class="pg" rel="next" href="<?php echo htmlspecialchars($aow_pg_url($page + 1), ENT_QUOTES); ?>"><?php te('browse.next','Next'); ?> &rsaquo;</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
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