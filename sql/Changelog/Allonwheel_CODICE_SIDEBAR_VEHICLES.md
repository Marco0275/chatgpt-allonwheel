# Allonwheel — Codice completo dei file modificati e nuovi
*Intervento: sidebar condizionale + pagina Vehicles. 28 maggio 2026.*

> Apri questo file in chat per leggere il codice quando l'anteprima dei `.php` appare vuota. Per il deploy diretto usa lo ZIP allegato (preserva CRLF e struttura cartelle).

---

## Indice
1. `include_sidebar.php` — MODIFICATO — Dispatcher: logged → sidebar_logged.php, visitatore → sidebar_static.php
2. `sidebar.php` — MODIFICATO — Link Marketplace corretti (Shelter & Container, Vehicles, All listings)
3. `sidebar_static.php` — MODIFICATO — Stessi link Marketplace corretti per visitatori
4. `vehicles.php` — NUOVO — Pagina dedicata ai soli veicoli (esclude Shelter/Container)

---

## `include_sidebar.php`
**MODIFICATO — Dispatcher: logged → sidebar_logged.php, visitatore → sidebar_static.php**

```php
<?php
// ============================================================
// include_sidebar.php — Dispatcher condizionale della sidebar
//
// Direttiva 17 (vincolante):
//   - utente loggato     → sidebar_logged.php
//   - utente non loggato → sidebar_static.php
//
// sidebar_static.php NON va rimossa (annulla il punto D3 del report).
//
// UTILIZZO (unica riga in ogni pagina del sito):
//   Da root:       <?php include __DIR__ . '/include_sidebar.php';
//   Da subfolder:  <?php include __DIR__ . '/../include_sidebar.php';
// ============================================================

require_once __DIR__ . '/config/session_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_sidebar_root = __DIR__;

if (is_user_logged_in()) {
    // Utente autenticato → sidebar_logged.php (che internamente delega
    // al contenuto reale in sidebar.php; cosi' il dispatcher rispetta
    // letteralmente la direttiva 17).
    include $_sidebar_root . '/sidebar_logged.php';
} else {
    // Visitatore → sidebar statica
    include $_sidebar_root . '/sidebar_static.php';
}

unset($_sidebar_root);

```

## `sidebar.php`
**MODIFICATO — Link Marketplace corretti (Shelter & Container, Vehicles, All listings)**

```php
<?php
// ============================================================
// sidebar.php — Sidebar unificata (visitatore + utente loggato)
//
// - Usa session_helper (is_user_logged_in)
// - Sezione "Vehicle types": caricata dalla tabella vehicle_types (DB)
//   Gestita solo dall'admin tramite /_admin/admin_vehicle_types.php
// - Fallback hardcoded se il DB non è disponibile o la tabella vuota
// ============================================================

require_once __DIR__ . '/config/session_helper.php';

// Base path — se non già calcolato dall'header
if (!isset($base_url)) {
    $base_url = '';
    $script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($script, '/' . $f . '/') !== false) {
            $base_url = '../';
            break;
        }
    }
}

$is_logged_in = is_user_logged_in();

// ---- Carica vehicle types da DB ----
$vehicle_types = [];
if (isset($pdo)) {
    try {
        $vt_stmt = $pdo->query(
            'SELECT name, slug FROM vehicle_types ORDER BY sort_order, name'
        );
        $vehicle_types = $vt_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback silenzioso
        $vehicle_types = [];
    }
}

// Fallback hardcoded se DB non disponibile o tabella vuota
if (empty($vehicle_types)) {
    $vehicle_types = [
        ['name' => 'Ambulances',                  'slug' => 'ambulanze'],
        ['name' => 'Mobile shops / Food',          'slug' => 'autonegozi_alimentari'],
        ['name' => 'Mobile shops / Haberdashery',  'slug' => 'autonegozi_mercerie'],
        ['name' => 'Armored',                      'slug' => 'blindati'],
        ['name' => 'Motorhomes',                   'slug' => 'camper'],
        ['name' => 'Tow trucks',                   'slug' => 'carrattrezzi'],
        ['name' => 'Tippers',                      'slug' => 'cassoni'],
        ['name' => 'Curtain-side bodies',           'slug' => 'centinati'],
        ['name' => 'Insulated bodies',              'slug' => 'coibentati'],
        ['name' => 'Disabled access vehicles',      'slug' => 'disabili'],
        ['name' => 'Law enforcement',               'slug' => 'forze_dell_ordine'],
        ['name' => 'Refrigerated bodies',           'slug' => 'frigoriferi'],
        ['name' => 'Box vans',                      'slug' => 'furgonature_box'],
        ['name' => 'Isothermal bodies',             'slug' => 'isotermici'],
        ['name' => 'Mobile medical labs',           'slug' => 'laboratori_medici_mobili'],
        ['name' => 'Minibuses',                     'slug' => 'minibus'],
        ['name' => 'Mobile workshops',              'slug' => 'officine_mobili'],
        ['name' => 'Aerial platforms / Cranes',     'slug' => 'piattaforme_aeree_gru'],
        ['name' => 'Public administration',         'slug' => 'pubblica_amministrazione'],
        ['name' => 'School buses',                  'slug' => 'scuolabus'],
        ['name' => 'Waste collection vehicles',     'slug' => 'servizi_ecologici'],
        ['name' => 'Lifting systems',               'slug' => 'sistemi_di_sollevamento'],
        ['name' => 'Leisure',                       'slug' => 'tempo_libero'],
        ['name' => 'Garment transport',             'slug' => 'trasporto_abiti'],
        ['name' => 'Animal transport',              'slug' => 'trasporto_animali'],
        ['name' => 'Mobile offices',                'slug' => 'uffici_mobili'],
        ['name' => 'Fire dept. / Civil protection', 'slug' => 'vvf_protezione_civile'],
    ];
}
?>

<!-- ===== Quick actions ===== -->
<div class="sb_box">
  <h3><?php echo $is_logged_in ? 'My area' : 'Get started'; ?></h3>
  <ul class="sb_list">
    <?php if ($is_logged_in): ?>
      <li><a href="<?php echo $base_url; ?>01_login/my_posts.php">Dashboard</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/all_about_me.php">My profile</a></li>
      <li><a href="<?php echo $base_url; ?>02_free_ads/02_insert_ad.php">Post a free ad</a></li>
      <li><a href="<?php echo $base_url; ?>03_ads/03_insert_ad.php">Post a premium ad</a></li>
      <li><a href="<?php echo $base_url; ?>06_company/06_10_register_company.php">Register my company</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/logout.php">Logout</a></li>
    <?php else: ?>
      <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/newregister.php">Create a free account</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/forgot_password.php">Forgot password</a></li>
    <?php endif; ?>
  </ul>
</div>

<!-- ===== Marketplace ===== -->
<div class="sb_box">
  <h3>Marketplace</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>vehicles.php">Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>portfolio.php">Portfolio</a></li>
  </ul>
</div>

<!-- ===== Vehicle types (da DB) ===== -->
<div class="sb_box">
  <h3>Vehicle types</h3>
  <ul class="sb_list">
    <?php foreach ($vehicle_types as $vt): ?>
      <li>
        <a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php?vtype=<?php echo urlencode($vt['slug']); ?>">
          <?php echo htmlspecialchars($vt['name']); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

```

## `sidebar_static.php`
**MODIFICATO — Stessi link Marketplace corretti per visitatori**

```php
<?php
// ============================================================
// sidebar_static.php — Sidebar per visitatori NON loggati (direttiva 17).
//
// - Usata da include_sidebar.php quando l'utente non e' autenticato.
// - Stesse classi CSS della sidebar dinamica (dir.4 / dir.8): nessuno
//   stile aggiuntivo, solo il foglio di stile esistente.
// - "Vehicle types" caricati dalla tabella vehicle_types (come sidebar.php),
//   con fallback hardcoded se il DB non e' disponibile o la tabella e' vuota.
// ============================================================

// Base path — se non gia' calcolato dall'header
if (!isset($base_url)) {
    $base_url = '';
    $script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($script, '/' . $f . '/') !== false) {
            $base_url = '../';
            break;
        }
    }
}

// ---- Carica vehicle types da DB (stessa logica di sidebar.php) ----
$vehicle_types = [];
if (isset($pdo)) {
    try {
        $vt_stmt = $pdo->query('SELECT name, slug FROM vehicle_types ORDER BY sort_order, name');
        $vehicle_types = $vt_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vehicle_types = [];
    }
}
if (empty($vehicle_types)) {
    // Fallback allineato a sidebar.php (slug canonici della tabella vehicle_types)
    $vehicle_types = [
        ['name' => 'Ambulances',                  'slug' => 'ambulanze'],
        ['name' => 'Mobile shops / Food',         'slug' => 'autonegozi_alimentari'],
        ['name' => 'Motorhomes',                  'slug' => 'camper'],
        ['name' => 'Tow trucks',                  'slug' => 'carrattrezzi'],
        ['name' => 'Refrigerated bodies',         'slug' => 'frigoriferi'],
        ['name' => 'Mobile workshops',            'slug' => 'officine_mobili'],
        ['name' => 'Mobile offices',              'slug' => 'uffici_mobili'],
        ['name' => 'Animal transport',            'slug' => 'trasporto_animali'],
    ];
}
?>

<!-- ===== Get started ===== -->
<div class="sb_box">
  <h3>Get started</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/newregister.php">Create a free account</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/forgot_password.php">Forgot password</a></li>
  </ul>
</div>

<!-- ===== Marketplace ===== -->
<div class="sb_box">
  <h3>Marketplace</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>vehicles.php">Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>portfolio.php">Portfolio</a></li>
  </ul>
</div>
<!-- ===== Vehicle types (da DB) ===== -->
<div class="sb_box">
  <h3>Vehicle types</h3>
  <ul class="sb_list">
    <?php foreach ($vehicle_types as $vt): ?>
      <li>
        <a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php?vtype=<?php echo urlencode($vt['slug']); ?>">
          <?php echo htmlspecialchars($vt['name']); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Searching for the right race transporter used to mean weeks of phone calls.
  With All on Wheel we narrowed our shortlist to three suppliers in an afternoon,
  all matching our spec for a two-car deck with workshop access.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>— GT Team Principal</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>

```

## `vehicles.php`
**NUOVO — Pagina dedicata ai soli veicoli (esclude Shelter/Container)**

```php
<?php
// ============================================================
// vehicles.php — Pagina dedicata ai veicoli (nodo Vehicle types).
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

// Filtro macro opzionale: ?macro=road | ?macro=special
$macro = strtolower(trim($_GET['macro'] ?? ''));
if (!in_array($macro, ['road', 'special'], true)) {
    $macro = '';
}

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
    error_log('[Allonwheel] vehicles.php query error: ' . $e->getMessage());
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
<html xmlns="http://www.w3.org/1999/xhtml">
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
    <div id="page_title"><?php echo htmlspecialchars($page_title); ?></div>
    <div id="search_box">
      <form action="" method="get">
        <?php if ($macro !== ''): ?>
          <input type="hidden" name="macro" value="<?php echo htmlspecialchars($macro); ?>" />
        <?php endif; ?>
        <input type="text"
               value="<?php echo htmlspecialchars($search !== '' ? $search : 'Search'); ?>"
               name="q" size="10" id="searchfield" title="searchfield"
               onfocus="clearText(this)" onblur="clearText(this)" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content">

    <div class="post_box">
      <h2>All vehicles</h2>
      <p>Browse the vehicle listings currently published on the marketplace.
         You can narrow the view to one of the two macro-categories:</p>
      <p>
        <a class="more" href="vehicles.php<?php echo $macro === '' ? '' : '?macro='; ?>">All</a>
        &nbsp;|&nbsp;
        <a class="more" href="vehicles.php?macro=road">Road</a>
        &nbsp;|&nbsp;
        <a class="more" href="vehicles.php?macro=special">Special</a>
      </p>
    </div>

    <?php if (empty($ads)): ?>
    <div class="post_box">
      <h2>No vehicles published yet</h2>
      <?php if (!$schema_ready): ?>
        <p>This section is being set up. Please check back soon.</p>
      <?php elseif ($search !== '' || $macro !== ''): ?>
        <p>No vehicles match your current filter.
           <a href="vehicles.php" class="more">View all</a></p>
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

    <div class="post_box">
      <h2><?php echo htmlspecialchars($ad['title']); ?></h2>

      <?php if (!empty($ad['subtitle'])): ?>
      <p><em><?php echo htmlspecialchars($ad['subtitle']); ?></em></p>
      <?php endif; ?>

      <ul class="gallery">
        <li><a class="pirobox"
               href="<?php echo htmlspecialchars($orig_url); ?>"
               title="<?php echo htmlspecialchars($ad['title']); ?>">
            <img src="<?php echo htmlspecialchars($thumb_url); ?>"
                 alt="<?php echo htmlspecialchars($ad['title']); ?>"
                 width="220" height="150" border="0"
                 onerror="this.onerror=null;this.src='images/no_image.jpg';" /></a></li>
      </ul>

      <p><strong>Type:</strong> <?php echo htmlspecialchars(vehiclesBadge($ad['type'])); ?>
         &nbsp;|&nbsp; <strong>Condition:</strong> <?php echo htmlspecialchars($ad['conditions']); ?></p>

      <?php if ($price > 0): ?>
        <p>&euro;&nbsp;<?php echo number_format($price, 0, '.', ','); ?></p>
      <?php else: ?>
        <p><em>Price on request</em></p>
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
