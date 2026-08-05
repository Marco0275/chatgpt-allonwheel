# Allonwheel — Pannello Admin "Records" (insert/modifica) + debug
*Bundle del 5 giu 2026. Stack PHP 8.3 / MySQL. Comunicazione IT, UI EN (dir. 0, 16).*

## Cosa è stato fatto

Aggiunto al pannello admin (`/_admin/`) un'area **Records** con i form per **inserire e modificare** i record nelle tabelle principali, più una correzione di debug sulla dashboard.

### File NUOVI
1. **`_admin/manage_records.php`** — hub del pannello: conteggio record per tabella e link agli editor.
2. **`_admin/edit_ad.php`** — insert/modifica annunci **free** (`02_free_ads`) e **premium** (`03_ads`).
   `?type=free|premium` (nome tabella in whitelist), `?edit=ID` per la modifica.
3. **`_admin/edit_company.php`** — insert/modifica aziende (`06_company`), vincolo **max 1 azienda/utente** (dir. 3).

### File MODIFICATO
4. **`_admin/dashboard.php`** — **fix debug**: i due link filtro (`Pending` / `All users`) producevano HTML non valido
   (`<a href="?filter=pending" active">`: attributo `active` inesistente + `">` di troppo). Ora lo stato attivo usa
   `class="more"` (classe già presente nel foglio di stile). Aggiunto il link **Records** alla barra di navigazione admin.

## Conformità alle direttive
- **dir. 1 (cartella=tabella):** editor agganciati a `02_free_ads`, `03_ads`, `06_company`; gallery/tech_details non toccate.
- **dir. 8/4 (CSS):** riuso classi esistenti (`post_box`, `more`, `submit_btn`, `input_field`, `error-msg`, `done`, `float_r`).
  *Nota:* per le tabelle elenco ho usato lo stesso stile inline minimale già presente in `dashboard.php`/`admin_vehicle_types.php` (coerenza col pannello esistente). Se preferisci zero inline, aggiungo classi al CSS.
- **dir. 11 (sicurezza):** `AdminAuth::requireAdminSession()` in testa a ogni pagina; CSRF one-shot (`csrf_generate`/`csrf_verify`);
  solo prepared statement; nome tabella in whitelist (mai da input); `noindex,nofollow`.
- **dir. 12 (isolamento):** ogni create/update logga su `admin_audit_log` (admin, azione, target, IP).
- **dir. 13 (free≠premium):** flussi distinti via `?type`; nei titoli **nessuna** etichetta free/premium (dir. 14).
- **dir. 15 (upload/images intoccabili):** **nessun upload** dagli editor admin — solo nomi file (default `no_image.jpg`,
  conservati in modifica). Cancellazione completa (file/gallery/tech/prodotti/servizi) resta su `moderate_ads.php` / `manage_companies.php`.
- **dir. 18 (Road/Special):** `vehicle_type` da menu (24 Road + Special + Shelter/Container); la macro `road/special` è
  derivata automaticamente da `VehicleTaxonomy::macroForSlug()`.

## Verifiche eseguite (dir. 2/10 — doppia verifica)
**1ª verifica — lint:** `php -l` su tutti i 171 file PHP del progetto → **0 errori**; idem sui 4 file di questo bundle.
**2ª verifica — funzionale su MariaDB reale** (schema `allonwhe80316.sql` importato):
- INSERT + UPDATE `02_free_ads` → OK (UPDATE preserva `expires_at`, scadenza 45 gg).
- INSERT + UPDATE `03_ads` → OK (scadenza 60 gg, `expires_at` preservato in modifica).
- INSERT + UPDATE `06_company` → OK; rilevamento **1 azienda/utente** → OK.
- `admin_audit_log` insert → OK.
- Tassonomia: 24 tipi Road + 3 Special; `macroForSlug`/`isValidType` coerenti.
**Guard di sicurezza:** presenza `requireAdminSession`, `csrf_verify`/`csrf_generate`, whitelist tabella, nessun input diretto in query, `noindex` → tutti OK.

## Da sapere
- Gli editor admin creano/modificano **il record principale**. Per gli annunci premium i **dettagli tecnici** (`03_ads_tech_details`, ~50 campi) e le **gallery** restano nel flusso utente esistente; per le aziende, **prodotti/servizi/gallery** restano in `06_company`.
- Accesso: `/_admin/index.php` → `dashboard.php` → **Records**.

---

# Codice completo


## `_admin/manage_records.php`

```php
<?php
// ============================================================
// /_admin/manage_records.php
// Hub del pannello "Records": punto unico da cui l'admin raggiunge i
// form di inserimento/modifica dei record nelle tabelle principali
// (annunci free, annunci premium, aziende, tipi veicolo).
//
// Mostra il conteggio dei record per ciascuna tabella e i link ai
// rispettivi editor. Nessuna scrittura su DB: pagina di sola lettura.
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

// Conteggio record per tabella (best-effort: se una tabella manca, 0).
function aw_count(PDO $pdo, string $table): int
{
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$cnt_free      = aw_count($pdo, '02_free_ads');
$cnt_premium   = aw_count($pdo, '03_ads');
$cnt_company   = aw_count($pdo, '06_company');
$cnt_vtypes    = aw_count($pdo, 'vehicle_types');
$cnt_users     = aw_count($pdo, 'users');
$cnt_blog      = aw_count($pdo, 'blog');

// Schede del pannello: tabella, etichetta, conteggio, editor, descrizione
$cards = [
    ['02_free_ads',   'Free Ads',      $cnt_free,    'edit_ad.php?type=free',     'Insert / edit free classified ads.'],
    ['03_ads',        'Premium Ads',   $cnt_premium, 'edit_ad.php?type=premium',  'Insert / edit premium classified ads.'],
    ['06_company',    'Companies',     $cnt_company, 'edit_company.php',          'Insert / edit supplier companies (max 1 per user).'],
    ['vehicle_types', 'Vehicle Types', $cnt_vtypes,  'admin_vehicle_types.php',   'Road / Special taxonomy used by the filters.'],
    ['blog',          'Blog posts',    $cnt_blog,    'moderate_blog.php',         'Review and publish blog articles.'],
    ['users',         'Users',         $cnt_users,   'dashboard.php',             'User tiers and premium approvals.'],
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin &mdash; Records</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <div id="site_title"><h1>&nbsp;</h1></div>
  </div>
  <div id="content_top">
    <div id="page_title">Records</div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" style="width:100%;">

    <div class="post_box">
      <div class="post_meta">
        <a href="dashboard.php">Users</a> &nbsp;|&nbsp;
        <strong>Records</strong> &nbsp;|&nbsp;
        <a href="moderate_ads.php">Ad moderation</a> &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <a href="admin_vehicle_types.php">Vehicle Types</a> &nbsp;|&nbsp;
        <a href="moderate_blog.php">Blog</a> &nbsp;|&nbsp;
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
    </div>

    <div class="post_box">
      <h2>Insert / edit records</h2>
      <p>Pick a table to add new records or edit existing ones. Image upload, gallery and
         full deletion stay in the dedicated moderation pages and in the public flow, so
         the <code>upload</code> / <code>images</code> folders are never touched from here.</p>

      <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#1D275A; color:#fff;">
            <th>Table</th><th>Section</th><th width="10%">Records</th><th>What you can do</th><th width="16%">Open</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cards as $c): ?>
          <tr>
            <td><code><?php echo htmlspecialchars($c[0]); ?></code></td>
            <td><strong><?php echo htmlspecialchars($c[1]); ?></strong></td>
            <td align="center"><?php echo (int)$c[2]; ?></td>
            <td><?php echo htmlspecialchars($c[4]); ?></td>
            <td align="center"><a class="more" href="<?php echo htmlspecialchars($c[3]); ?>">Manage</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="post_box">
      <h3>Notes</h3>
      <p>Every create/update is recorded in <code>admin_audit_log</code> with the admin id,
         action, target user, timestamp and IP. Child tables (galleries, technical details,
         products, services) keep their own dedicated flows.</p>
    </div>

  </div>
  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
  </div>
</div>
</body>
</html>

```


## `_admin/edit_ad.php`

```php
<?php
// ============================================================
// /_admin/edit_ad.php
// Inserimento e modifica record annunci (free + premium) da pannello admin.
//
//  ?type=free     -> tabella 02_free_ads (scadenza 45 giorni)
//  ?type=premium  -> tabella 03_ads     (scadenza 60 giorni)
//  ?edit=ID       -> carica il record indicato nel form di modifica
//
// La cancellazione (con file/gallery/tech_details) resta a carico di
// moderate_ads.php: qui ci si limita a INSERT/UPDATE dei campi del record
// principale, senza toccare upload/images (dir. 15) ne' la gallery.
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$admin_id = AdminAuth::requireAdminSession();

// ------------------------------------------------------------
// Tipo annuncio -> tabella (whitelist: niente nome tabella da input libero)
// ------------------------------------------------------------
$type = (($_GET['type'] ?? $_POST['type'] ?? 'free') === 'premium') ? 'premium' : 'free';
$table = ($type === 'premium') ? '03_ads' : '02_free_ads';
$label = ($type === 'premium') ? 'Premium Ad' : 'Free Ad';
$interval_days = ($type === 'premium') ? 60 : 45;

$success = '';
$error   = '';

// Valori enum ammessi (coerenti con lo schema delle tabelle annunci)
$allowed_status     = ['pending', 'approved', 'rejected'];
$allowed_types      = ['New on sell', 'Used on sell', 'For rent', 'Project'];
$allowed_conditions = ['New', 'As good as new', 'Used', 'Poor', 'Project'];
$checkbox_fields    = ['racing', 'promotion', 'horse', 'hospitality', 'medical',
                       'military', 'motorhome', 'technology', 'street_food'];

// ------------------------------------------------------------
// POST: add / edit
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {

        $id_user     = (int)($_POST['id_user'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $subtitle    = trim($_POST['subtitle'] ?? '');
        $author      = trim($_POST['author'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : 'approved';
        $ad_type     = in_array($_POST['ad_type'] ?? '', $allowed_types, true) ? $_POST['ad_type'] : 'New on sell';
        $conditions  = in_array($_POST['conditions'] ?? '', $allowed_conditions, true) ? $_POST['conditions'] : 'New';

        // Prezzo opzionale: normalizza formato europeo (1.500,50 -> 1500.50)
        $raw_price = trim((string)($_POST['list_price'] ?? ''));
        if ($raw_price === '') {
            $list_price = 0.0;
        } else {
            $normalized = $raw_price;
            if (strpos($normalized, ',') !== false) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
            $list_price = filter_var($normalized, FILTER_VALIDATE_FLOAT);
        }

        // Immagini: solo nomi file, nessun upload (dir. 15). Default no_image.jpg.
        $image_original  = trim($_POST['image_original'] ?? '');
        $image_thumbnail = trim($_POST['image_thumbnail'] ?? '');
        if ($image_original === '')  { $image_original  = 'no_image.jpg'; }
        if ($image_thumbnail === '') { $image_thumbnail = 'no_image.jpg'; }

        // Tassonomia (flowchart, dir. 18): macro derivata dallo slug scelto.
        $vehicle_type = trim((string)($_POST['vehicle_type'] ?? ''));
        if ($vehicle_type === VehicleTaxonomy::SHELTER_SLUG) {
            $item_kind      = VehicleTaxonomy::KIND_SHELTER;
            $macro_category = VehicleTaxonomy::MACRO_SPECIAL;
        } else {
            $item_kind      = VehicleTaxonomy::KIND_VEHICLE;
            $macro_category = VehicleTaxonomy::macroForSlug($vehicle_type);
        }

        // Flag booleani
        $cb = [];
        foreach ($checkbox_fields as $chk) {
            $cb[$chk] = isset($_POST[$chk]) ? 1 : 0;
        }

        // --- Validazione ---
        if ($id_user <= 0) {
            $error = 'Please select the owner (user) of this ad.';
        } elseif ($title === '') {
            $error = 'Title is required.';
        } elseif ($description === '') {
            $error = 'Description is required.';
        } elseif ($list_price === false || $list_price < 0) {
            $error = 'Invalid list price: enter digits only (e.g. 1500 or 1500.50) or leave empty.';
        } elseif ($vehicle_type === '' ||
                  ($vehicle_type !== VehicleTaxonomy::SHELTER_SLUG &&
                   !VehicleTaxonomy::isValidType($vehicle_type, $macro_category, $pdo))) {
            $error = 'Please choose a valid vehicle type / category.';
        } else {
            // Verifica esistenza utente proprietario
            $stmt = $pdo->prepare('SELECT username, email, phone FROM users WHERE id_user = :id LIMIT 1');
            $stmt->execute([':id' => $id_user]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$owner) {
                $error = 'The selected owner does not exist.';
            } else {
                // Se i contatti sono vuoti, eredita dal profilo utente (canonico)
                if ($author === '') { $author = (string)$owner['username']; }
                if ($email === '')  { $email  = (string)$owner['email']; }
                if ($phone === '')  { $phone  = (string)$owner['phone']; }
            }
        }

        if ($error === '') {
            $params = [
                ':id_user'        => $id_user,
                ':author'         => $author,
                ':email'          => $email,
                ':phone'          => $phone,
                ':title'          => $title,
                ':subtitle'       => $subtitle,
                ':list_price'     => $list_price,
                ':status'         => $status,
                ':type'           => $ad_type,
                ':conditions'     => $conditions,
                ':description'    => $description,
                ':racing'         => $cb['racing'],
                ':promotion'      => $cb['promotion'],
                ':horse'          => $cb['horse'],
                ':hospitality'    => $cb['hospitality'],
                ':medical'        => $cb['medical'],
                ':military'       => $cb['military'],
                ':motorhome'      => $cb['motorhome'],
                ':technology'     => $cb['technology'],
                ':street_food'    => $cb['street_food'],
                ':item_kind'      => $item_kind,
                ':macro_category' => $macro_category,
                ':vehicle_type'   => $vehicle_type,
                ':image_original' => $image_original,
                ':image_thumbnail'=> $image_thumbnail,
            ];

            if ($action === 'add') {
                $sql = "INSERT INTO `{$table}`
                    (id_user, author, email, phone, title, subtitle, list_price, status, type, conditions, description,
                     racing, promotion, horse, hospitality, medical, military, motorhome, technology, street_food,
                     item_kind, macro_category, vehicle_type, image_original, image_thumbnail, expires_at)
                    VALUES
                    (:id_user, :author, :email, :phone, :title, :subtitle, :list_price, :status, :type, :conditions, :description,
                     :racing, :promotion, :horse, :hospitality, :medical, :military, :motorhome, :technology, :street_food,
                     :item_kind, :macro_category, :vehicle_type, :image_original, :image_thumbnail,
                     DATE_ADD(NOW(), INTERVAL {$interval_days} DAY))";
                try {
                    $pdo->prepare($sql)->execute($params);
                    $new_id = (int)$pdo->lastInsertId();
                    UserTier::logAdminAction($pdo, $admin_id, 'ad_create', $id_user,
                        $label . ' created (id_ads=' . $new_id . ', table=' . $table . ')',
                        $_SERVER['REMOTE_ADDR'] ?? '');
                    $_SESSION['admin_success'] = $label . ' created (ID ' . $new_id . ').';
                } catch (PDOException $e) {
                    error_log('[Allonwheel] admin ad insert error: ' . $e->getMessage());
                    $error = 'Database error while creating the ad.';
                }
            } else {
                $id_ads = (int)($_POST['id_ads'] ?? 0);
                $params[':id_ads'] = $id_ads;
                // expires_at NON viene toccato in modifica (preserva la scadenza)
                $sql = "UPDATE `{$table}` SET
                    id_user=:id_user, author=:author, email=:email, phone=:phone,
                    title=:title, subtitle=:subtitle, list_price=:list_price, status=:status,
                    type=:type, conditions=:conditions, description=:description,
                    racing=:racing, promotion=:promotion, horse=:horse, hospitality=:hospitality,
                    medical=:medical, military=:military, motorhome=:motorhome, technology=:technology,
                    street_food=:street_food, item_kind=:item_kind, macro_category=:macro_category,
                    vehicle_type=:vehicle_type, image_original=:image_original, image_thumbnail=:image_thumbnail
                    WHERE id_ads=:id_ads LIMIT 1";
                try {
                    $pdo->prepare($sql)->execute($params);
                    UserTier::logAdminAction($pdo, $admin_id, 'ad_update', $id_user,
                        $label . ' updated (id_ads=' . $id_ads . ', table=' . $table . ')',
                        $_SERVER['REMOTE_ADDR'] ?? '');
                    $_SESSION['admin_success'] = $label . ' updated (ID ' . $id_ads . ').';
                } catch (PDOException $e) {
                    error_log('[Allonwheel] admin ad update error: ' . $e->getMessage());
                    $error = 'Database error while updating the ad.';
                }
            }

            if ($error === '') {
                header('Location: edit_ad.php?type=' . $type);
                exit;
            }
        }
    }
}

// Flash dal redirect
if ($success === '') { $success = $_SESSION['admin_success'] ?? ''; }
unset($_SESSION['admin_success']);

// ------------------------------------------------------------
// Record da modificare (?edit=ID)
// ------------------------------------------------------------
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id_ads = :id LIMIT 1");
    $stmt->execute([':id' => $edit_id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Lista utenti per il menu proprietario
$users = $pdo->query('SELECT id_user, username, email FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);

// Tassonomia: tipi Road e Special per il menu vehicle_type
$road_types    = VehicleTaxonomy::typesByMacro(VehicleTaxonomy::MACRO_ROAD, $pdo);
$special_types = VehicleTaxonomy::typesByMacro(VehicleTaxonomy::MACRO_SPECIAL, $pdo);

// Elenco record recenti
$list = $pdo->query("SELECT id_ads, id_user, title, status, list_price, vehicle_type, created_at
                     FROM `{$table}` ORDER BY id_ads DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Helper di selezione
function aw_sel($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}
function aw_chk($item, $field) {
    return (!empty($item) && (int)($item[$field] ?? 0) === 1) ? ' checked="checked"' : '';
}
$v = function ($key, $default = '') use ($edit_item) {
    return htmlspecialchars((string)($edit_item[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin &mdash; Edit Ads</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <div id="site_title"><h1>&nbsp;</h1></div>
  </div>
  <div id="content_top">
    <div id="page_title">Records &mdash; Ads (<?php echo $label; ?>)</div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" style="width:100%;">

    <!-- Navigazione admin -->
    <div class="post_box">
      <div class="post_meta">
        <a href="dashboard.php">Users</a> &nbsp;|&nbsp;
        <a href="manage_records.php">Records</a> &nbsp;|&nbsp;
        <a href="moderate_ads.php">Ad moderation</a> &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <a href="admin_vehicle_types.php">Vehicle Types</a> &nbsp;|&nbsp;
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
      <div class="post_meta">
        Editing table <code><?php echo htmlspecialchars($table); ?></code> &mdash; switch:
        <a href="edit_ad.php?type=free">Free Ads</a> &nbsp;|&nbsp;
        <a href="edit_ad.php?type=premium">Premium Ads</a>
        <div class="cleaner"></div>
      </div>
    </div>

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <!-- ===== Form add / edit ===== -->
    <div class="post_box">
      <h2><?php echo $edit_item ? ('Edit ' . $label . ' #' . (int)$edit_item['id_ads']) : ('Add new ' . $label); ?></h2>
      <form method="post" action="edit_ad.php?type=<?php echo $type; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>" />
        <input type="hidden" name="type" value="<?php echo $type; ?>" />
        <?php if ($edit_item): ?>
        <input type="hidden" name="id_ads" value="<?php echo (int)$edit_item['id_ads']; ?>" />
        <?php endif; ?>

        <table width="100%" border="0" cellpadding="6">
          <tr>
            <td width="180"><label>Owner (user):</label></td>
            <td>
              <select name="id_user" class="input_field" required>
                <option value="">-- select user --</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo (int)$u['id_user']; ?>"<?php echo aw_sel($edit_item['id_user'] ?? '', $u['id_user']); ?>>
                  <?php echo htmlspecialchars($u['username'] . ' (' . $u['email'] . ')'); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr><td><label>Title:</label></td>
              <td><input type="text" name="title" maxlength="200" class="input_field" style="width:340px;" value="<?php echo $v('title'); ?>" required /></td></tr>
          <tr><td><label>Subtitle:</label></td>
              <td><input type="text" name="subtitle" maxlength="200" class="input_field" style="width:340px;" value="<?php echo $v('subtitle'); ?>" /></td></tr>
          <tr><td><label>List price:</label></td>
              <td><input type="text" name="list_price" class="input_field" style="width:140px;" value="<?php echo $edit_item ? htmlspecialchars(number_format((float)$edit_item['list_price'], 2, '.', '')) : ''; ?>" />
                  <small> Empty = 0.00</small></td></tr>
          <tr><td><label>Status:</label></td>
              <td><select name="status" class="input_field">
                  <?php foreach ($allowed_status as $s): ?>
                  <option value="<?php echo $s; ?>"<?php echo aw_sel($edit_item['status'] ?? 'approved', $s); ?>><?php echo ucfirst($s); ?></option>
                  <?php endforeach; ?>
              </select></td></tr>
          <tr><td><label>Type:</label></td>
              <td><select name="ad_type" class="input_field">
                  <?php foreach ($allowed_types as $t): ?>
                  <option value="<?php echo htmlspecialchars($t); ?>"<?php echo aw_sel($edit_item['type'] ?? 'New on sell', $t); ?>><?php echo htmlspecialchars($t); ?></option>
                  <?php endforeach; ?>
              </select></td></tr>
          <tr><td><label>Conditions:</label></td>
              <td><select name="conditions" class="input_field">
                  <?php foreach ($allowed_conditions as $c): ?>
                  <option value="<?php echo htmlspecialchars($c); ?>"<?php echo aw_sel($edit_item['conditions'] ?? 'New', $c); ?>><?php echo htmlspecialchars($c); ?></option>
                  <?php endforeach; ?>
              </select></td></tr>
          <tr><td valign="top"><label>Vehicle type / category:</label></td>
              <td><select name="vehicle_type" class="input_field" required>
                  <option value="">-- select --</option>
                  <option value="<?php echo VehicleTaxonomy::SHELTER_SLUG; ?>"<?php echo aw_sel($edit_item['vehicle_type'] ?? '', VehicleTaxonomy::SHELTER_SLUG); ?>>Shelter / Container (Special)</option>
                  <optgroup label="Road">
                  <?php foreach ($road_types as $slug => $name): ?>
                    <option value="<?php echo htmlspecialchars($slug); ?>"<?php echo aw_sel($edit_item['vehicle_type'] ?? '', $slug); ?>><?php echo htmlspecialchars($name); ?></option>
                  <?php endforeach; ?>
                  </optgroup>
                  <optgroup label="Special">
                  <?php foreach ($special_types as $slug => $name): if ($slug === VehicleTaxonomy::SHELTER_SLUG) continue; ?>
                    <option value="<?php echo htmlspecialchars($slug); ?>"<?php echo aw_sel($edit_item['vehicle_type'] ?? '', $slug); ?>><?php echo htmlspecialchars($name); ?></option>
                  <?php endforeach; ?>
                  </optgroup>
              </select>
              <small> Macro (road/special) is derived automatically from this choice.</small></td></tr>
          <tr><td valign="top"><label>Tags:</label></td>
              <td>
                <?php foreach ($checkbox_fields as $chk): ?>
                <label style="display:inline-block; width:150px;">
                  <input type="checkbox" name="<?php echo $chk; ?>" value="1"<?php echo aw_chk($edit_item, $chk); ?> />
                  <?php echo ucfirst(str_replace('_', ' ', $chk)); ?>
                </label>
                <?php endforeach; ?>
              </td></tr>
          <tr><td valign="top"><label>Description:</label></td>
              <td><textarea name="description" rows="5" class="input_field" style="width:340px;" required><?php echo $v('description'); ?></textarea></td></tr>
          <tr><td><label>Contacts (optional):</label></td>
              <td>
                Author <input type="text" name="author" maxlength="100" class="input_field" style="width:140px;" value="<?php echo $v('author'); ?>" />
                Email <input type="text" name="email" maxlength="150" class="input_field" style="width:180px;" value="<?php echo $v('email'); ?>" />
                Phone <input type="text" name="phone" maxlength="30" class="input_field" style="width:120px;" value="<?php echo $v('phone'); ?>" />
                <br /><small>Leave empty to inherit from the owner's profile.</small>
              </td></tr>
          <tr><td><label>Image filenames:</label></td>
              <td>
                Original <input type="text" name="image_original" maxlength="255" class="input_field" style="width:200px;" value="<?php echo $v('image_original', 'no_image.jpg'); ?>" />
                Thumb <input type="text" name="image_thumbnail" maxlength="255" class="input_field" style="width:200px;" value="<?php echo $v('image_thumbnail', 'no_image.jpg'); ?>" />
                <br /><small>Filenames only &mdash; no upload here. Manage images/gallery from the user-facing flow.</small>
              </td></tr>
          <tr><td></td>
              <td>
                <input type="submit" class="submit_btn" value="<?php echo $edit_item ? 'Save changes' : 'Create ad'; ?>" />
                <?php if ($edit_item): ?>
                <a href="edit_ad.php?type=<?php echo $type; ?>" class="more">Cancel</a>
                <?php endif; ?>
              </td></tr>
        </table>
      </form>
    </div>

    <!-- ===== Elenco record ===== -->
    <div class="post_box">
      <h2>Recent <?php echo $label; ?> records (<?php echo count($list); ?>, max 100)</h2>
      <?php if (empty($list)): ?>
      <p><em>No records yet.</em></p>
      <?php else: ?>
      <table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#1D275A; color:#fff;">
            <th width="5%">ID</th><th>Title</th><th>Owner</th><th>Status</th><th>Price</th><th>Type</th><th width="14%">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $row): ?>
          <tr>
            <td align="center"><?php echo (int)$row['id_ads']; ?></td>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td align="center"><?php echo (int)$row['id_user']; ?></td>
            <td align="center"><?php echo htmlspecialchars($row['status']); ?></td>
            <td align="right"><?php echo number_format((float)$row['list_price'], 2); ?></td>
            <td><?php echo htmlspecialchars(VehicleTaxonomy::label((string)$row['vehicle_type'])); ?></td>
            <td align="center">
              <a class="more" href="edit_ad.php?type=<?php echo $type; ?>&amp;edit=<?php echo (int)$row['id_ads']; ?>">Edit</a>
              &nbsp;<a href="moderate_ads.php">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p><small>Deletion (with images, gallery and tech details) is handled on the
         <a href="moderate_ads.php">Ad moderation</a> page.</small></p>
      <?php endif; ?>
    </div>

  </div>
  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
  </div>
</div>
</body>
</html>

```


## `_admin/edit_company.php`

```php
<?php
// ============================================================
// /_admin/edit_company.php
// Inserimento e modifica record azienda (tabella 06_company) da pannello admin.
//
//  ?edit=ID  -> carica il record nel form di modifica
//
// Vincolo: max 1 azienda per utente (dir. 3). In inserimento e quando si
// cambia il proprietario in modifica, si verifica che l'utente non abbia
// gia' un'altra azienda.
//
// La cancellazione completa (logo, gallery, prodotti, servizi) resta a carico
// di manage_companies.php: qui solo INSERT/UPDATE dei campi anagrafici, senza
// toccare upload/images (dir. 15) ne' le tabelle figlie.
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

$success = '';
$error   = '';

// Campi anagrafici testuali (mappa nome campo -> obbligatorio?)
$text_fields = [
    'ragione_sociale'    => true,
    'partita_iva'        => true,
    'codice_fiscale'     => false,
    'indirizzo'          => true,
    'cap'                => true,
    'citta'              => true,
    'provincia'          => true,
    'nazione'            => true,
    'telefono'           => false,
    'cellulare'          => false,
    'fax'                => false,
    'email'              => true,
    'pec'                => false,
    'sito_web'           => false,
    'referente_nome'     => false,
    'referente_cognome'  => false,
    'referente_ruolo'    => false,
    'referente_email'    => false,
    'referente_telefono' => false,
];

// ------------------------------------------------------------
// POST: add / edit
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {

        $user_id     = (int)($_POST['user_id'] ?? 0);
        $descrizione = trim($_POST['descrizione'] ?? '');
        $logo        = trim($_POST['logo'] ?? '');
        $attiva      = isset($_POST['attiva']) ? 1 : 0;
        $company_id  = (int)($_POST['id'] ?? 0);

        $data = [];
        foreach (array_keys($text_fields) as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        if ($data['nazione'] === '') { $data['nazione'] = 'Italia'; }

        // --- Validazione ---
        if ($user_id <= 0) {
            $error = 'Please select the owner (user) of this company.';
        } elseif ($data['ragione_sociale'] === '') {
            $error = 'Company name (ragione sociale) is required.';
        } elseif ($data['partita_iva'] === '') {
            $error = 'VAT number (partita IVA) is required.';
        } elseif ($data['email'] === '') {
            $error = 'Company e-mail is required.';
        } else {
            // L'utente proprietario deve esistere
            $stmt = $pdo->prepare('SELECT id_user FROM users WHERE id_user = :id LIMIT 1');
            $stmt->execute([':id' => $user_id]);
            if (!$stmt->fetchColumn()) {
                $error = 'The selected owner does not exist.';
            } else {
                // Vincolo 1 azienda/utente: esiste gia' un'azienda di questo utente?
                $stmt = $pdo->prepare('SELECT id FROM `06_company` WHERE user_id = :uid LIMIT 1');
                $stmt->execute([':uid' => $user_id]);
                $existing = (int)($stmt->fetchColumn() ?: 0);
                if ($existing > 0 && $existing !== $company_id) {
                    $error = 'This user already has a registered company (max 1 per user).';
                }
            }
        }

        if ($error === '') {
            $params = [];
            foreach (array_keys($text_fields) as $f) {
                $params[':' . $f] = $data[$f];
            }
            $params[':user_id']     = $user_id;
            $params[':descrizione'] = $descrizione;
            $params[':logo']        = ($logo === '') ? null : $logo;
            $params[':attiva']      = $attiva;

            if ($action === 'add') {
                $cols = array_keys($text_fields);
                $col_list  = '`user_id`, `' . implode('`, `', $cols) . '`, `descrizione`, `logo`, `attiva`';
                $val_list  = ':user_id, :' . implode(', :', $cols) . ', :descrizione, :logo, :attiva';
                $sql = "INSERT INTO `06_company` ({$col_list}) VALUES ({$val_list})";
                try {
                    $pdo->prepare($sql)->execute($params);
                    $new_id = (int)$pdo->lastInsertId();
                    UserTier::logAdminAction($pdo, $admin_id, 'company_create', $user_id,
                        'Company created (id=' . $new_id . ')', $_SERVER['REMOTE_ADDR'] ?? '');
                    $_SESSION['admin_success'] = 'Company created (ID ' . $new_id . ').';
                } catch (PDOException $e) {
                    error_log('[Allonwheel] admin company insert error: ' . $e->getMessage());
                    $error = 'Database error while creating the company (VAT number may already be in use).';
                }
            } else {
                $params[':id'] = $company_id;
                $sets = [];
                foreach (array_keys($text_fields) as $f) { $sets[] = "`{$f}`=:{$f}"; }
                $sets[] = '`user_id`=:user_id';
                $sets[] = '`descrizione`=:descrizione';
                $sets[] = '`logo`=:logo';
                $sets[] = '`attiva`=:attiva';
                $sql = "UPDATE `06_company` SET " . implode(', ', $sets) . " WHERE id=:id LIMIT 1";
                try {
                    $pdo->prepare($sql)->execute($params);
                    UserTier::logAdminAction($pdo, $admin_id, 'company_update', $user_id,
                        'Company updated (id=' . $company_id . ')', $_SERVER['REMOTE_ADDR'] ?? '');
                    $_SESSION['admin_success'] = 'Company updated (ID ' . $company_id . ').';
                } catch (PDOException $e) {
                    error_log('[Allonwheel] admin company update error: ' . $e->getMessage());
                    $error = 'Database error while updating the company (VAT number may already be in use).';
                }
            }

            if ($error === '') {
                header('Location: edit_company.php');
                exit;
            }
        }
    }
}

if ($success === '') { $success = $_SESSION['admin_success'] ?? ''; }
unset($_SESSION['admin_success']);

// Record da modificare
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM `06_company` WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $edit_id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$users = $pdo->query('SELECT id_user, username, email FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);

$list = $pdo->query('SELECT id, user_id, ragione_sociale, partita_iva, citta, attiva
                     FROM `06_company` ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

$v = function ($key, $default = '') use ($edit_item) {
    return htmlspecialchars((string)($edit_item[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
function aw_sel_c($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}

// Etichette leggibili per i campi
$field_labels = [
    'ragione_sociale'    => 'Company name',
    'partita_iva'        => 'VAT number',
    'codice_fiscale'     => 'Tax code',
    'indirizzo'          => 'Address',
    'cap'                => 'Postal code',
    'citta'              => 'City',
    'provincia'          => 'Province',
    'nazione'            => 'Country',
    'telefono'           => 'Phone',
    'cellulare'          => 'Mobile',
    'fax'                => 'Fax',
    'email'              => 'E-mail',
    'pec'                => 'PEC',
    'sito_web'           => 'Website',
    'referente_nome'     => 'Contact first name',
    'referente_cognome'  => 'Contact last name',
    'referente_ruolo'    => 'Contact role',
    'referente_email'    => 'Contact e-mail',
    'referente_telefono' => 'Contact phone',
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin &mdash; Edit Companies</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <div id="site_title"><h1>&nbsp;</h1></div>
  </div>
  <div id="content_top">
    <div id="page_title">Records &mdash; Companies</div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" style="width:100%;">

    <div class="post_box">
      <div class="post_meta">
        <a href="dashboard.php">Users</a> &nbsp;|&nbsp;
        <a href="manage_records.php">Records</a> &nbsp;|&nbsp;
        <a href="moderate_ads.php">Ad moderation</a> &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <a href="admin_vehicle_types.php">Vehicle Types</a> &nbsp;|&nbsp;
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
    </div>

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <div class="post_box">
      <h2><?php echo $edit_item ? ('Edit company #' . (int)$edit_item['id']) : 'Add new company'; ?></h2>
      <form method="post" action="edit_company.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>" />
        <?php if ($edit_item): ?>
        <input type="hidden" name="id" value="<?php echo (int)$edit_item['id']; ?>" />
        <?php endif; ?>

        <table width="100%" border="0" cellpadding="6">
          <tr>
            <td width="180"><label>Owner (user):</label></td>
            <td>
              <select name="user_id" class="input_field" required>
                <option value="">-- select user --</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo (int)$u['id_user']; ?>"<?php echo aw_sel_c($edit_item['user_id'] ?? '', $u['id_user']); ?>>
                  <?php echo htmlspecialchars($u['username'] . ' (' . $u['email'] . ')'); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small> Max 1 company per user.</small>
            </td>
          </tr>
          <?php foreach ($text_fields as $f => $required): ?>
          <tr>
            <td><label><?php echo htmlspecialchars($field_labels[$f]); ?>:</label></td>
            <td><input type="text" name="<?php echo $f; ?>" class="input_field" style="width:340px;"
                       value="<?php echo $v($f); ?>"<?php echo $required ? ' required' : ''; ?> /></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td valign="top"><label>Description:</label></td>
            <td><textarea name="descrizione" rows="4" class="input_field" style="width:340px;"><?php echo $v('descrizione'); ?></textarea></td>
          </tr>
          <tr>
            <td><label>Logo filename:</label></td>
            <td><input type="text" name="logo" maxlength="255" class="input_field" style="width:260px;" value="<?php echo $v('logo'); ?>" />
                <br /><small>Filename only (in /uploads/06_company/) &mdash; no upload here.</small></td>
          </tr>
          <tr>
            <td><label>Visible in directory:</label></td>
            <td><label><input type="checkbox" name="attiva" value="1"<?php echo (!$edit_item || (int)$edit_item['attiva'] === 1) ? ' checked="checked"' : ''; ?> /> Active</label></td>
          </tr>
          <tr>
            <td></td>
            <td>
              <input type="submit" class="submit_btn" value="<?php echo $edit_item ? 'Save changes' : 'Create company'; ?>" />
              <?php if ($edit_item): ?>
              <a href="edit_company.php" class="more">Cancel</a>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </form>
    </div>

    <div class="post_box">
      <h2>Companies (<?php echo count($list); ?>, max 100)</h2>
      <?php if (empty($list)): ?>
      <p><em>No companies yet.</em></p>
      <?php else: ?>
      <table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#1D275A; color:#fff;">
            <th width="5%">ID</th><th>Company name</th><th>VAT</th><th>City</th><th>Owner</th><th>Active</th><th width="14%">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $row): ?>
          <tr>
            <td align="center"><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['ragione_sociale']); ?></td>
            <td><?php echo htmlspecialchars($row['partita_iva']); ?></td>
            <td><?php echo htmlspecialchars($row['citta']); ?></td>
            <td align="center"><?php echo (int)$row['user_id']; ?></td>
            <td align="center"><?php echo ((int)$row['attiva'] === 1) ? 'Yes' : 'No'; ?></td>
            <td align="center">
              <a class="more" href="edit_company.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
              &nbsp;<a href="manage_companies.php">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p><small>Activation/deactivation and full deletion (logo, gallery, products, services)
         are handled on the <a href="manage_companies.php">Companies</a> page.</small></p>
      <?php endif; ?>
    </div>

  </div>
  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
  </div>
</div>
</body>
</html>

```


## `_admin/dashboard.php`

```php
<?php

// ============================================================
// /_admin/dashboard.php
// Pannello admin: tabella utenti con flag per concedere/revocare premium.
//
// Visibile solo dopo AdminAuth::requireAdminSession() (timeout 30 min,
// IP-bound, password re-auth obbligatoria).
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

// Forza sessione admin valida
$admin_id = AdminAuth::requireAdminSession();

// Filtro: ?filter=pending → solo richieste pending
$filter  = isset($_GET['filter']) && $_GET['filter'] === 'pending' ? 'pending' : 'all';
$only_pend = ($filter === 'pending');
$users   = UserTier::listUsersForAdmin($pdo, $only_pend);

// Stats riassuntive
$stats = $pdo->query(
  "SELECT
    SUM(CASE WHEN user_tier='free'  THEN 1 ELSE 0 END) AS free_count,
    SUM(CASE WHEN user_tier='premium' THEN 1 ELSE 0 END) AS premium_count,
    SUM(CASE WHEN user_tier='admin' THEN 1 ELSE 0 END) AS admin_count,
    SUM(CASE WHEN premium_requested=1 AND user_tier='free' THEN 1 ELSE 0 END) AS pending_count
   FROM users"
)->fetch(PDO::FETCH_ASSOC);

// Token CSRF per i form di grant/revoke (uno per pagina, riusato in più form)
csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Flash messages
$success = $_SESSION['admin_success'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->
<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <div id="site_title">
    <h1>&nbsp;</h1>
    </div>
  </div>
  <div id="content_top">
    <div id="page_title">Premium Approvals</div>
    <div class="cleaner"></div>
  </div>
  <div id="templatemo_content" style="width:100%;">
    <?php if ($success !== ''): ?>
    <div class="post_box">
      <p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box">
      <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>
    <!-- Riepilogo + filtri -->
    <!-- Navigazione admin -->
    <div class="post_box">
      <div class="post_meta">
        <strong>Users</strong>
        &nbsp;|&nbsp;
        <a href="manage_records.php">Records</a>
        &nbsp;|&nbsp;
        <a href="moderate_ads.php">Ad moderation</a>
        &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <a href="admin_vehicle_types.php">Vehicle Types</a> &nbsp;|&nbsp;
        <a href="moderate_blog.php">Blog</a>
        <div class="cleaner"></div>
      </div>
    </div>
    <div class="post_box">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></h2>
    <p>
      <strong>Free:</strong> <?php echo (int)$stats['free_count']; ?> &nbsp;|&nbsp;
      <strong>Premium:</strong> <?php echo (int)$stats['premium_count']; ?> &nbsp;|&nbsp;
      <strong>Admin:</strong> <?php echo (int)$stats['admin_count']; ?> &nbsp;|&nbsp;
      <strong>Pending requests:</strong> <?php echo (int)$stats['pending_count']; ?>
    </p>
    <div class="post_meta">
      <a href="?filter=pending"<?php echo $filter === 'pending' ? ' class="more"' : ''; ?>>
        Pending requests (<?php echo (int)$stats['pending_count']; ?>)
      </a>
      &nbsp;|
      <a href="?filter=all"<?php echo $filter === 'all' ? ' class="more"' : ''; ?>>
        All users (<?php echo (int)($stats['free_count'] + $stats['premium_count'] + $stats['admin_count']); ?>)
      </a>
      &nbsp;
      <a href="logout.php" class="more float_r">Sign out</a>
      <div class="cleaner"></div>
    </div>
    </div>
    <!-- Tabella utenti -->
    <div class="post_box">
    <h2><?php echo $only_pend ? 'Pending premium requests' : 'All users'; ?></h2>
    <?php if (empty($users)): ?>
      <p><em><?php echo $only_pend ? 'No pending requests at the moment.' : 'No users found.'; ?></em></p>
    <?php else: ?>
      <table border="1" cellpadding="1" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
        <tr style="background:#1D275A; color:#fff;">
          <th width="3%">ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Current tier</th>
          <th>Free / Premium ads</th>
          <th>Requested at</th>
          <th>Granted at</th>
          <th width="19%">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u):
          $is_admin = ($u['user_tier'] === 'admin');
          $is_premium = ($u['user_tier'] === 'premium');
          $is_free  = ($u['user_tier'] === 'free');
          $has_pend = ((int)$u['premium_requested'] === 1) && $is_free;
        ?>
        <tr<?php echo $has_pend ? ' style="background:#FFF8DC;"' : ''; ?>>
          <td align="center" valign="middle"><?php echo (int)$u['id_user']; ?></td>
          <td align="center" valign="middle"><?php echo htmlspecialchars($u['username']); ?></td>
          <td align="center" valign="middle"><?php echo htmlspecialchars($u['email']); ?></td>
          <td align="center" valign="middle">
            <?php if ($is_admin): ?>
            <strong>admin</strong>
            <?php elseif ($is_premium): ?>
            <strong style="color:#1D275A;">premium</strong>
            <?php else: ?>
            free<?php echo $has_pend ? ' <em>(requested)</em>' : ''; ?>
            <?php endif; ?>
          </td>
          <td align="center" valign="middle">
            <?php echo (int)$u['free_ads_count']; ?> /
            <?php echo (int)$u['premium_ads_count']; ?>
          </td>
          <td align="center" valign="middle">
            <?php
            echo $u['premium_requested_at']
            ? htmlspecialchars(date('Y-m-d', strtotime($u['premium_requested_at'])))
            : '—';
            ?>
          </td>
          <td align="center" valign="middle">
            <?php
            echo $u['premium_granted_at']
            ? htmlspecialchars(date('Y-m-d', strtotime($u['premium_granted_at'])))
            : '—';
            ?>
          </td>
          <td align="center" valign="middle">
            <?php if ($is_admin): ?>
            <em>—</em>
            <?php elseif ($is_premium): ?>
            <!-- Form REVOKE -->
            <form method="post" action="grant_premium.php" style="margin:0;"
              onsubmit="return confirm('Revoke premium tier for <?php echo htmlspecialchars(addslashes($u['email'])); ?>?');">
              <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id"  value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action"   value="revoke" />
	</br>
              <button type="submit" class="more">Revoke</button>
				</br>
            </form>
            <?php else: ?>
            <!-- Form GRANT -->
            <form method="post" action="grant_premium.php" style="margin:0;">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action"  value="grant" />
				</br>
				<input name="confirm" type="hidden" required value="1" checked="checked" />
              <button type="submit" class="more">Premium</button>
								</br>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    </div>
    <!-- Note di sicurezza -->
    <div class="post_box">
    <h3>Notes</h3>
    <p>
      Free users: max <strong><?php echo UserTier::FREE_AD_LIMIT; ?></strong> free ads. &nbsp;
      Premium users: max <strong><?php echo UserTier::PREMIUM_AD_LIMIT; ?></strong> premium ads (plus the same free ad allowance).
    </p>
    <p>
      Every grant or revoke is logged in <code>admin_audit_log</code> with timestamp, IP and details.
      Session expires after <?php echo AdminAuth::ADMIN_SESSION_MINUTES; ?> minutes of inactivity.
    </p>
    </div>
  </div>
  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. — Restricted area</p>
  </div>
</div>
</body>
</html>


```
