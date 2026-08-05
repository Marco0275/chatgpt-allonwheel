# Allonwheel v0.0.9 — Bundle file modificati (menu/sidebar, dir. 17 rev.4)
*Generato il 2026-06-09 — header SOLO nav pubblica; link utente login-aware in ogni sidebar (`sidebar_user_box.php`). Lint PHP 8.3: OK. Render funzionale OK. CRLF preservati.*

> Copia ogni blocco nel file omonimo in **root** del sito. `sidebar_user_box.php` è un file NUOVO. Tutti i file sono CRLF.

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
  <h1><a href="<?php echo $base_url; ?>index.php"></a></h1>
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
```

## `sidebar_user_box.php`  *(nuovo)*
```php
<?php
// ============================================================
// sidebar_user_box.php — Box utente login-aware, condiviso da TUTTE le
// sidebar di sezione (dir. 17 rev.4 — richiesta utente 9 giu 2026).
//
// Modello:
//   - L'header e' SOLO navigazione pubblica (identico per ospite e loggato).
//   - I link personali NON stanno piu' nell'header: ogni sidebar li mostra qui.
//   - Loggato     -> box "My account" con tutti i link personali + logout.
//   - Non loggato -> solo il link di Login.
//
// Produce SOLO box .sb_box. Nessuno stile nuovo (dir. 8).
// ============================================================
require_once __DIR__ . '/config/session_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- Base path automatico (se non gia' calcolato dalla pagina/sidebar) -----
if (!isset($base_url)) {
    $base_url = '';
    $_ub_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_ub_script, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_ub_script);
}

$is_logged_in = is_user_logged_in();
$is_admin     = $is_logged_in && isset($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';
?>
<?php if ($is_logged_in): ?>
<!-- ===== My account (loggato) ===== -->
<div class="sb_box">
  <h3>My account</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/my_posts.php">My posts</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/all_about_me.php">My profile</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/modify_user_details.php">Account settings</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/request_premium.php">Upgrade to premium</a></li>
    <li><a href="<?php echo $base_url; ?>02_free_ads/02_00_select_type.php">Post a free ad</a></li>
    <li><a href="<?php echo $base_url; ?>03_ads/03_00_select_type.php">Post a premium ad</a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_10_register_company.php">Register company</a></li>
    <li><a href="<?php echo $base_url; ?>blog_write.php">Write an article</a></li>
    <?php if ($is_admin): ?>
      <li><a href="<?php echo $base_url; ?>_admin/dashboard.php">Admin panel</a></li>
    <?php endif; ?>
    <li><a href="<?php echo $base_url; ?>01_login/logout.php">Logout</a></li>
  </ul>
</div>
<?php else: ?>
<!-- ===== Account (visitatore): solo link di login ===== -->
<div class="sb_box">
  <h3>Account</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a></li>
  </ul>
</div>
<?php endif; ?>
```

## `sidebar_default.php`
```php
<?php
// ============================================================
// sidebar_default.php — Sidebar di default.
//
// Usata su index, pagine editoriali (about, blog, FAQ, contact, privacy,
// cookie-policy, Conditions, what_we_do), cartella 00_first e _admin,
// e come fallback del dispatcher. Espone le sezioni principali del
// flowchart come punto di ingresso al sito.
//
// Riusa il box "Testimonial" della precedente sidebar statica.
// Produce SOLO box .sb_box. Nessuno stile nuovo (dir. 8).
// ============================================================
if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}
?>

<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>
<!-- ===== Explore ===== -->
<div class="sb_box">
  <h3>Explore</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>road_vehicles.php">Road Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>special_vehicles.php">Special Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>portfolio.php">Portfolio</a></li>
  </ul>
</div>
<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div> 
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar_marketplace.php`
```php
<?php
// ============================================================
// sidebar_marketplace.php — Sidebar della sezione "Marketplace".
// Opzioni di sezione (flowchart): Free Ads, Premium Ads, Request quotation.
// Produce SOLO box .sb_box (il wrapper #templatemo_sidebar lo apre la pagina).
// Nessuno stile nuovo (dir. 8): classi .sb_box / .sb_list esistenti.
// ============================================================
require_once __DIR__ . '/config/session_helper.php';
if (!isset($base_url)) { $base_url = ''; }
$is_logged_in = is_user_logged_in();
?>
<div class="sb_box">
  <h3>Marketplace</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $base_url; ?>02_free_ads/02_view_ads.php">Free ads</a></li>
    <li><a href="<?php echo $base_url; ?>03_ads/03_view_ads.php">Premium ads</a></li>
    <!-- Nodo "Request quotation" del flowchart: cartella/tabella dedicata
         ora collegato alla pagina dedicata 04_request_offer/04_request_offer.php. -->
    <li><a href="<?php echo $base_url; ?>04_request_offer/04_request_offer.php">Request a quotation</a></li>
  </ul>
</div>
<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>

<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div> 
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar_suppliers.php`
```php
<?php
// ============================================================
// sidebar_suppliers.php — Sidebar della sezione "Suppliers".
//
// Opzioni di sezione (flowchart):
//   Suppliers -> Company -> Shelter / Container
//            \-> Project manager -> Vehicle types -> Road / Special
//
// La lista "Vehicle types" arriva dalla tabella DB vehicle_types
// (gestita dall'admin). Fallback hardcoded se il DB non e' disponibile.
// Produce SOLO box .sb_box. Nessuno stile nuovo (dir. 8).
// ============================================================
require_once __DIR__ . '/config/session_helper.php';

if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}

// ---- Carica vehicle types da DB (stessa logica della vecchia sidebar.php) ----
$vehicle_types = [];
if (isset($pdo)) {
    try {
        $vt_stmt = $pdo->query('SELECT name, slug FROM vehicle_types ORDER BY sort_order, name');
        $vehicle_types = $vt_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vehicle_types = [];
    }
}
// Fallback hardcoded se DB non disponibile o tabella vuota.
if (empty($vehicle_types)) {
    $vehicle_types = [
        ['name' => 'Ambulances',                  'slug' => 'ambulanze'],
        ['name' => 'Street Food',                 'slug' => 'autonegozi_alimentari'],
        ['name' => 'Haberdashery',                'slug' => 'autonegozi_mercerie'],
        ['name' => 'Armored',                     'slug' => 'blindati'],
        ['name' => 'Motorhomes',                  'slug' => 'camper'],
        ['name' => 'Tow trucks',                  'slug' => 'carrattrezzi'],
        ['name' => 'Tippers',                     'slug' => 'cassoni'],
        ['name' => 'Curtain-side bodies',         'slug' => 'centinati'],
        ['name' => 'Insulated bodies',            'slug' => 'coibentati'],
        ['name' => 'Disabled access vehicles',    'slug' => 'disabili'],
        ['name' => 'Law enforcement',             'slug' => 'forze_dell_ordine'],
        ['name' => 'Refrigerated bodies',         'slug' => 'frigoriferi'],
        ['name' => 'Box vans',                    'slug' => 'furgonature_box'],
        ['name' => 'Isothermal bodies',           'slug' => 'isotermici'],
        ['name' => 'Mobile medical labs',         'slug' => 'laboratori_medici_mobili'],
        ['name' => 'Minibuses',                   'slug' => 'minibus'],
        ['name' => 'Mobile workshops',            'slug' => 'officine_mobili'],
        ['name' => 'Aerial platforms / Cranes',   'slug' => 'piattaforme_aeree_gru'],
        ['name' => 'Public administration',       'slug' => 'pubblica_amministrazione'],
        ['name' => 'School buses',                'slug' => 'scuolabus'],
        ['name' => 'Waste collection vehicles',   'slug' => 'servizi_ecologici'],
        ['name' => 'Lifting systems',             'slug' => 'sistemi_di_sollevamento'],
        ['name' => 'Leisure',                     'slug' => 'tempo_libero'],
        ['name' => 'Garment transport',           'slug' => 'trasporto_abiti'],
        ['name' => 'Animal transport',            'slug' => 'trasporto_animali'],
        ['name' => 'Mobile offices',              'slug' => 'uffici_mobili'],
        ['name' => 'Fire dept. / Civil protection','slug' => 'vvf_protezione_civile'],
    ];
}
?>

<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>
<!-- ===== Suppliers ===== -->
<div class="sb_box">
  <h3>Suppliers</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>road_vehicles.php">Road Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>special_vehicles.php">Special Vehicles</a></li>
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
<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div> 
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar_special.php`
```php
<?php
// ============================================================
// sidebar_special.php — Sidebar della sezione "Special".
//
// Pagine che la usano (risolte da include_sidebar.php):
//   - special_vehicles.php
//   - shelter_container.php   (ramo Shelter/Container -> Special del flowchart)
//
// Mostra: navigazione Suppliers + l'elenco delle CATEGORIE SPECIALI
// (catalogo unico in CompanyManager::$products_special) con link alla
// directory aziende filtrata per categoria speciale (?special=<key>),
// poi il logo azienda in evidenza e il testimonial.
//
// Produce SOLO box .sb_box (il wrapper #templatemo_sidebar lo apre la pagina).
// Nessuno stile nuovo (dir. 8): classi .sb_box / .sb_list esistenti.
// ============================================================
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/06_company.class.php';

if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}

// Catalogo delle categorie speciali (slug => label). Unica fonte di verita'.
$special_categories = CompanyManager::$products_special;
?>

<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>
<!-- ===== Suppliers ===== -->
<div class="sb_box">
  <h3>Suppliers</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>road_vehicles.php">Road Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>special_vehicles.php">Special Vehicles</a></li>
  </ul>
</div>

<!-- ===== Special categories ===== -->
<div class="sb_box">
  <h3>Special categories</h3>
  <ul class="sb_list">
    <?php foreach ($special_categories as $slug => $label): ?>
      <li>
        <a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php?special=<?php echo urlencode($slug); ?>">
          <?php echo htmlspecialchars($label); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div>
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar_account.php`
```php
<?php
// ============================================================
// sidebar_account.php — Sidebar dell'area "Account" (cartella 01_login).
//
// Direttiva 17: le PAGINE PERSONALI dell'utente loggato (my_posts,
// profilo, post ad, gestione azienda, logout) stanno SOLO nell'header
// e NON devono comparire in nessuna sidebar. Qui quindi:
//   - visitatore  -> opzioni di accesso (login / registrazione / recupero)
//   - utente loggato -> box neutro di assistenza (nessun link personale)
//
// Produce SOLO box .sb_box. Nessuno stile nuovo (dir. 8).
// ============================================================
require_once __DIR__ . '/config/session_helper.php';

if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}

$is_logged_in = is_user_logged_in();
?>

<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>

<!-- ===== Need help? (sempre disponibile, nessuna pagina personale) ===== -->
<div class="sb_box">
  <h3>Need help?</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>FAQ.php">F.A.Q.</a></li>
    <li><a href="<?php echo $base_url; ?>Conditions.php">Conditions &amp; rules</a></li>
    <li><a href="<?php echo $base_url; ?>contact.php">Contact us</a></li>
  </ul>
</div>
<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div> 
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar_blog.php`
```php
<?php
// ============================================================
// sidebar_blog.php — Sidebar della sezione "Blog" (dir. 17 rev.3).
// Pagine: blog.php, blog_post.php, blog_write.php (via include_sidebar.php).
// Mostra "Latest articles" (dato reale da tabella `blog` via BlogManager) +
// CTA "Write an article" (loggati) + logo azienda + testimonial.
// dir.14: nessun box Categories/Newsletter (colonna/feature inesistenti).
// dir.8: solo classi .sb_box/.sb_list esistenti, nessuno stile nuovo.
// ============================================================
require_once __DIR__ . '/config/session_helper.php';

if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}

// ---- Ultimi articoli (dato reale; fallback silenzioso se DB assente) ----
$_blog_latest = [];
if (!isset($pdo)) {
    $_cfg = __DIR__ . '/config/database.php';
    if (is_file($_cfg)) { require_once $_cfg; }
}
if (isset($pdo)) {
    require_once __DIR__ . '/libs/blog.class.php';
    try {
        $_bm = new BlogManager($pdo);
        $_blog_latest = $_bm->listPublished(6, 0);
    } catch (Throwable $e) {
        $_blog_latest = [];
    }
}

$is_logged_in = is_user_logged_in();
?>

<!-- ===== Account / My account (login-aware, dir. 17 rev.4) ===== -->
<?php include __DIR__ . '/sidebar_user_box.php'; ?>
<!-- ===== Blog ===== -->
<div class="sb_box">
  <h3>Blog</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>blog.php">All articles</a></li>
  </ul>
</div>

<?php if (!empty($_blog_latest)): ?>
<!-- ===== Latest articles ===== -->
<div class="sb_box">
  <h3>Latest articles</h3>
  <ul class="sb_list">
    <?php foreach ($_blog_latest as $_a): ?>
      <li>
        <a href="<?php echo $base_url; ?>blog_post.php?id=<?php echo (int)$_a['id']; ?>">
          <?php echo htmlspecialchars($_a['title'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div>
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Sourcing a body or special vehicle to spec used to mean weeks of phone calls.
  With All on Wheel we shortlisted three approved suppliers in a single afternoon,
  each matching our requirements for vehicle type, build and budget.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; Fleet Manager</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```
