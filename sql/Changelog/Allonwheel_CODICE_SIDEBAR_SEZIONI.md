# Allonwheel — Sidebar per-sezione + verifica header
*Rev. 2 giu 2026 — implementazione dir. 17 (nuova): sidebar per-sezione, pagine personali solo nell'header.*

## Cosa è stato fatto

- `include_sidebar.php` riscritto come **dispatcher per-sezione**: risolve la sidebar dalla **cartella/pagina corrente** (non più dallo stato di login). Le pagine **non** vanno toccate: includono già questo file.
- **4 nuove sidebar di sezione** (solo box `.sb_box`, nessuno stile nuovo — dir. 8): `sidebar_marketplace.php`, `sidebar_suppliers.php`, `sidebar_account.php`, `sidebar_default.php`.
- I 3 file legacy (`sidebar.php`, `sidebar_logged.php`, `sidebar_static.php`) ridotti a **shim** verso la default (retrocompat, link preservati — dir. 9; eliminato il wrapper `#templatemo_sidebar` duplicato di `sidebar_static.php`).
- **Header invariato**: già conforme alla dir. 17 (pagine personali nel dropdown Account, mai in sidebar).

## Mappatura sezione → sidebar

| Path corrente | Sezione | Sidebar |
|---|---|---|
| `02_free_ads/`, `03_ads/`, `shared/`, `browse.php`, `ads.php`, `ad_post.php` | Marketplace | `sidebar_marketplace.php` |
| `06_company/`, `shelter_container.php`, `road_vehicles.php`, `special_vehicles.php` | Suppliers | `sidebar_suppliers.php` |
| `01_login/` | Account | `sidebar_account.php` |
| index, pagine editoriali, `00_first/`, `_admin/`, `portfolio.php` | Default | `sidebar_default.php` |

---

## `include_sidebar.php`

```php
<?php
// ============================================================
// include_sidebar.php — Dispatcher della sidebar PER SEZIONE
//
// Direttiva 17 (nuova — annulla e sostituisce la precedente versione
// condizionale loggato/statico):
//   - Ogni sezione del sito (Marketplace, Suppliers, Account, ...)
//     ha la PROPRIA sidebar con le OPZIONI DI SEZIONE.
//   - La sidebar viene risolta dalla SEZIONE CORRENTE (cartella/pagina),
//     NON piu' dallo stato di login.
//   - Le PAGINE PERSONALI dell'utente loggato (my_posts, profilo,
//     post ad, gestione azienda, logout) NON compaiono in nessuna
//     sidebar: stanno solo nell'header dell'area login.
//
// Le pagine includono questo file dentro il proprio <div id="templatemo_sidebar">,
// quindi i file di sezione qui inclusi devono produrre SOLO box .sb_box
// (nessun wrapper #templatemo_sidebar duplicato).
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

// ----- Base path automatico (se l'header non l'ha gia' calcolato) -----
if (!isset($base_url)) {
    $base_url = '';
    $_sb_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_sb_script, '/' . $f . '/') !== false) {
            $base_url = '../';
            break;
        }
    }
    unset($_sb_script);
}

// ----- Risoluzione della sezione corrente dal path dello script -----
$_sb_script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
$_sb_basename = basename($_sb_script);

$_sb_in = static function (string $folder) use ($_sb_script): bool {
    return strpos($_sb_script, '/' . $folder . '/') !== false;
};

if ($_sb_in('02_free_ads') || $_sb_in('03_ads') || $_sb_in('shared')
    || in_array($_sb_basename, ['browse.php', 'ads.php', 'ad_post.php'], true)) {
    // Sezione Marketplace (Free Ads / Premium Ads / Request quotation)
    $_sb_section = 'marketplace';
} elseif ($_sb_in('06_company')
    || in_array($_sb_basename, ['shelter_container.php', 'special_vehicles.php', 'road_vehicles.php'], true)) {
    // Sezione Suppliers (Company -> Shelter/Container; Project manager -> Vehicle types -> Road/Special)
    $_sb_section = 'suppliers';
} elseif ($_sb_in('01_login')) {
    // Area Account (le pagine personali restano nell'header, non qui)
    $_sb_section = 'account';
} else {
    // Index, pagine editoriali, 00_first, _admin, portfolio -> sidebar di default
    $_sb_section = 'default';
}

// ----- Inclusione della sidebar di sezione -----
$_sb_file = $_sidebar_root . '/sidebar_' . $_sb_section . '.php';
if (is_file($_sb_file)) {
    include $_sb_file;
} else {
    include $_sidebar_root . '/sidebar_default.php';
}

unset($_sidebar_root, $_sb_script, $_sb_basename, $_sb_in, $_sb_section, $_sb_file);
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
if (!isset($base_url)) { $base_url = ''; }
?>
<div class="sb_box">
  <h3>Marketplace</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $base_url; ?>02_free_ads/02_view_ads.php">Free ads</a></li>
    <li><a href="<?php echo $base_url; ?>03_ads/03_view_ads.php">Premium ads</a></li>
    <!-- Nodo "Request quotation" del flowchart: cartella/tabella dedicata
         ancora da confermare (Fase 0). In attesa, punta alla pagina contatti. -->
    <li><a href="<?php echo $base_url; ?>contact.php">Request a quotation</a></li>
  </ul>
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

<?php if (!$is_logged_in): ?>
<!-- ===== Account (visitatore) ===== -->
<div class="sb_box">
  <h3>Account</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/newregister.php">Create account</a></li>
    <li><a href="<?php echo $base_url; ?>01_login/forgot_password.php">Forgot password</a></li>
  </ul>
</div>
<?php endif; ?>

<!-- ===== Need help? (sempre disponibile, nessuna pagina personale) ===== -->
<div class="sb_box">
  <h3>Need help?</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>FAQ.php">F.A.Q.</a></li>
    <li><a href="<?php echo $base_url; ?>Conditions.php">Conditions &amp; rules</a></li>
    <li><a href="<?php echo $base_url; ?>contact.php">Contact us</a></li>
  </ul>
</div>
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

<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Searching for the right race transporter used to mean weeks of phone calls.
  With All on Wheel we narrowed our shortlist to three suppliers in an afternoon,
  all matching our spec for a two-car deck with workshop access.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; GT Team Principal</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `sidebar.php`

```php
<?php
// ============================================================
// sidebar.php — Shim di retrocompatibilita'.
//
// Nel nuovo modello (dir. 17) la sidebar e' risolta PER SEZIONE da
// include_sidebar.php. Questo file resta solo per eventuali include
// diretti pregressi: senza contesto di sezione, delega alla sidebar
// di default. Non contiene pagine personali utente.
// ============================================================
require_once __DIR__ . '/sidebar_default.php';
```

## `sidebar_logged.php`

```php
<?php
// sidebar_logged.php — Shim di retrocompatibilita' (dir. 17 nuova).
// Il vecchio modello loggato/statico e' superato: la sidebar e' ora
// risolta per sezione da include_sidebar.php. Delego alla default.
require_once __DIR__ . '/sidebar_default.php';
```

## `sidebar_static.php`

```php
<?php
// sidebar_static.php — Shim di retrocompatibilita' (dir. 17 nuova).
// Sostituisce la vecchia sidebar statica del visitatore (che apriva un
// proprio #templatemo_sidebar, causando un wrapper duplicato). Ora
// delega alla sidebar di default, senza wrapper e senza pagine personali.
require_once __DIR__ . '/sidebar_default.php';
```

