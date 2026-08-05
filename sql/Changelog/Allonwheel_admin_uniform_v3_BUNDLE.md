# Allonwheel — Admin: debug + layout/header uniformi + split Road/Special + cron-job.org (v3)
*Bundle 5 giu 2026. Sostituisce le consegne admin precedenti.*

## Cosa è stato fatto

### A) Debug / errori corretti
- **Header non uniforme**: `admin_vehicle_types.php` usava una barra `templatemo_menu` totalmente diversa (Dashboard/Companies/Logout) rispetto al `post_meta` delle altre pagine; nav con link e ordine divergenti su quasi ogni pagina → **unificati**.
- **Stili inline / CSS fuori standard (dir. 8)**: `admin_vehicle_types.php` conteneva un blocco `<style>`, `#no_sidebar` (classe inesistente), `../favicon.ico` (le altre usano `../images/favicon.ico`) e includeva `../footer.php` (footer pubblico). `moderate_ads`, `manage_companies`, `moderate_blog` avevano `style="..."` inline (tabelle, righe, stato, form, thumb) e `templatemo_content style="width:100%"`. **Tutto rimosso**: 0 `style=` inline su tutte le pagine admin.
- **Duplicati obsoleti**: `_admin/_admin__manage_companies.php` e `_admin/_admin__moderate_ads.php` sono **copie identiche** dei file ufficiali → da eliminare (non inclusi nel delta).

### B) Header admin uniforme su tutte le pagine
- Nuovi include condivisi **`_admin/admin_header.php`** e **`_admin/admin_footer.php`**: un solo punto per `<head>`, wrapper, `page_title`, apertura `#templatemo_content`, **barra di navigazione admin identica** (Users · Records · Ad moderation · Companies · Vehicle Types · Blog · Sign out) con **voce attiva evidenziata**.
- Tutte le 8 pagine di contenuto ora fanno `require admin_header.php` / `admin_footer.php` (titolo + voce attiva impostati prima dell'include).

### C) Vehicle Types divisi Road / Special
- `admin_vehicle_types.php` riscritto: il form ha un selettore **Macro-category (road/special)** salvato in `vehicle_types.macro_category`; la lista è in **due tabelle separate**, “Road — closed list” e “Special — complement”.

### D) Accesso cron-job.org via .env/.htaccess
- `scripts/expire_ads.php`: oltre alla CLI, ora è eseguibile via **HTTP solo con token valido** (`hash_equals`, confronto a tempo costante) letto da **`CRON_TOKEN`** in `.env`; il token va inviato nell'header `X-Cron-Token` (consigliato) o `?token=`. Dry-run anche via `?dry-run=1`. Senza token → **403**.
- `scripts/.htaccess`: `Require all denied` di default, con **eccezione solo per `expire_ads.php`** (`<Files>` → `Require all granted`); resta protetto dal token.
- `config/env` (template): aggiunta voce **`CRON_TOKEN=`** con istruzioni. Nessuna credenziale reale nel template.

## Verifiche (doppia, dir. 2/10)
- **Lint** `php -l`: tutti i file admin + `expire_ads.php` → 0 errori.
- **0 `style=` inline** su tutte le pagine admin; classi admin tutte definite nel CSS.
- **Header**: render OK (nav uniforme, voce attiva, `admin_full`, `noindex`); footer chiude il layout (div bilanciati header+footer).
- **DB reale (MariaDB)**: split Road=24 / Special=3; insert con macro (→ road 25 / special 4); whitelist macro non valida → `special`.
- **Cron token**: vuoto→403, errato→403, corretto→consentito.

## Installazione
1. Estrai il delta nella webroot (sovrascrive i file). `scripts/.htaccess` è incluso.
2. Nel **`.env` reale** (sopra la webroot) imposta `CRON_TOKEN=<stringa lunga casuale>`.
3. Su cron-job.org: URL `https://<dominio>/scripts/expire_ads.php`, header `X-Cron-Token: <token>` (in alternativa `?token=<token>`).
4. Rimuovi i due file obsoleti `_admin/_admin__manage_companies.php` e `_admin/_admin__moderate_ads.php`.
   *Nessuna migrazione DB:* `vehicle_types.macro_category` è già presente.

---

# Codice


## `_admin/admin_header.php`

```php
<?php
// ============================================================
// /_admin/admin_header.php
// Header/layout condiviso di TUTTE le pagine dell'area admin.
// Uniforma <head>, wrapper, titolo pagina, apertura #templatemo_content
// e la barra di navigazione admin (con voce attiva evidenziata).
//
// Uso (prima dell'include, nella pagina chiamante):
//   $admin_title  = 'Records';            // titolo pagina (h1/page_title)
//   $admin_active = 'records';            // chiave voce di menu attiva
//   require __DIR__ . '/admin_header.php';
//
// Chiavi $admin_active ammesse: users, records, ads, companies, vtypes, blog.
// Solo classi del foglio di stile esistente (dir. 8), niente stile inline.
// ============================================================

if (!isset($admin_title))  { $admin_title  = 'Admin'; }
if (!isset($admin_active)) { $admin_active = ''; }

// Voci del menu admin: chiave => [etichetta, file]
$admin_nav = [
    'users'     => ['Users',         'dashboard.php'],
    'records'   => ['Records',       'manage_records.php'],
    'ads'       => ['Ad moderation', 'moderate_ads.php'],
    'companies' => ['Companies',     'manage_companies.php'],
    'vtypes'    => ['Vehicle Types', 'admin_vehicle_types.php'],
    'blog'      => ['Blog',          'moderate_blog.php'],
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin &mdash; <?php echo htmlspecialchars($admin_title); ?></title>
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
    <div id="page_title"><?php echo htmlspecialchars($admin_title); ?></div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" class="admin_full">

    <div class="post_box">
      <div class="post_meta">
        <?php $first = true; foreach ($admin_nav as $key => $item): ?>
          <?php if (!$first) { echo ' &nbsp;|&nbsp; '; } $first = false; ?>
          <?php if ($key === $admin_active): ?>
            <strong><?php echo htmlspecialchars($item[0]); ?></strong>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($item[1]); ?>"><?php echo htmlspecialchars($item[0]); ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
        &nbsp;|&nbsp;
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
    </div>

```


## `_admin/admin_footer.php`

```php
<?php
// ============================================================
// /_admin/admin_footer.php
// Footer/layout condiviso di tutte le pagine dell'area admin.
// Chiude #templatemo_content, aggiunge il footer e chiude wrapper/body/html.
// ============================================================
?>
  </div>
  <div class="cleaner"></div>
  <div id="templatemo_footer">
    <p class="admin_footer_note">&copy; All on Wheel Ltd. &mdash; Restricted area</p>
  </div>
</div>
</body>
</html>

```


## `_admin/admin_vehicle_types.php`

```php
<?php
// ============================================================
// /_admin/admin_vehicle_types.php
// CRUD completo per la tabella vehicle_types, diviso per macro-categoria
// Road / Special (colonna vehicle_types.macro_category).
// Accessibile SOLO dopo AdminAuth::requireAdminSession().
// Layout uniforme via admin_header.php / admin_footer.php; solo classi
// del foglio di stile esistente (dir. 8), nessuno stile inline.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

$success = '';
$error   = '';
$allowed_macro = ['road', 'special'];

// ---- Gestione POST (add / edit / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $order = (int)($_POST['sort_order'] ?? 0);
        $macro = in_array($_POST['macro_category'] ?? '', $allowed_macro, true) ? $_POST['macro_category'] : 'special';
        $slug  = strtolower(preg_replace('/[^a-z0-9_]/', '_', $slug));

        if ($name === '' || $slug === '') {
            $error = 'Name and slug are required.';
        } elseif ($action === 'add') {
            try {
                $pdo->prepare('INSERT INTO vehicle_types (name, slug, sort_order, macro_category) VALUES (:name, :slug, :ord, :macro)')
                    ->execute([':name' => $name, ':slug' => $slug, ':ord' => $order, ':macro' => $macro]);
                $success = 'Vehicle type added successfully.';
            } catch (PDOException $e) {
                $error = 'Error adding record (slug may already exist).';
            }
        } else {
            $id = (int)($_POST['id'] ?? 0);
            try {
                $pdo->prepare('UPDATE vehicle_types SET name=:name, slug=:slug, sort_order=:ord, macro_category=:macro WHERE id=:id')
                    ->execute([':name' => $name, ':slug' => $slug, ':ord' => $order, ':macro' => $macro, ':id' => $id]);
                $success = 'Vehicle type updated.';
            } catch (PDOException $e) {
                $error = 'Error updating record.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM vehicle_types WHERE id = :id')->execute([':id' => $id]);
                $success = 'Vehicle type deleted.';
            } catch (PDOException $e) {
                $error = 'Error deleting record.';
            }
        }
    }
}

// ---- Record per l'edit inline ----
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM vehicle_types WHERE id = :id');
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ---- Liste separate per macro-categoria ----
$rows_road    = $pdo->query("SELECT * FROM vehicle_types WHERE macro_category='road'    ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$rows_special = $pdo->query("SELECT * FROM vehicle_types WHERE macro_category='special' ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

function aw_macro_sel($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}

$admin_title  = 'Vehicle Types';
$admin_active = 'vtypes';
require __DIR__ . '/admin_header.php';
?>

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($success); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($error); ?></p></div>
    <?php endif; ?>

    <!-- ===== Form aggiunta / modifica ===== -->
    <div class="post_box">
      <h2><?php echo $edit_item ? 'Edit vehicle type' : 'Add new vehicle type'; ?></h2>
      <form method="post" action="admin_vehicle_types.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>" />
        <?php if ($edit_item): ?>
        <input type="hidden" name="id" value="<?php echo (int)$edit_item['id']; ?>" />
        <?php endif; ?>

        <table class="admin_form" width="100%" border="0" cellpadding="6">
          <tr>
            <td width="200"><label>Macro-category:</label></td>
            <td>
              <select name="macro_category" class="input_field">
                <option value="road"<?php echo aw_macro_sel($edit_item['macro_category'] ?? 'special', 'road'); ?>>Road (closed list)</option>
                <option value="special"<?php echo aw_macro_sel($edit_item['macro_category'] ?? 'special', 'special'); ?>>Special (complement)</option>
              </select>
            </td>
          </tr>
          <tr>
            <td><label>Name (EN):</label></td>
            <td><input type="text" name="name" maxlength="100" class="input_field aw_in_l"
                       value="<?php echo htmlspecialchars($edit_item['name'] ?? ''); ?>" required /></td>
          </tr>
          <tr>
            <td><label>Slug (key):</label></td>
            <td>
              <input type="text" name="slug" maxlength="100" class="input_field aw_in_l"
                     value="<?php echo htmlspecialchars($edit_item['slug'] ?? ''); ?>" required
                     pattern="[a-z0-9_]+" title="Lowercase letters, numbers, underscores only" />
              <small> Lowercase letters, digits, underscores. Must match product_key in DB.</small>
            </td>
          </tr>
          <tr>
            <td><label>Sort order:</label></td>
            <td><input type="number" name="sort_order" class="input_field aw_in_s"
                       value="<?php echo (int)($edit_item['sort_order'] ?? 0); ?>" /></td>
          </tr>
          <tr>
            <td></td>
            <td>
              <input type="submit" class="submit_btn" value="<?php echo $edit_item ? 'Save changes' : 'Add'; ?>" />
              <?php if ($edit_item): ?>
              <a href="admin_vehicle_types.php" class="more">Cancel</a>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </form>
    </div>

    <?php
    function aw_vt_table(array $list, string $csrf_token): void {
        if (empty($list)) { echo '<p><em>No vehicle types in this category yet.</em></p>'; return; }
        ?>
        <table class="admin_table" border="1" cellpadding="4" cellspacing="0">
          <thead>
            <tr>
              <th width="40">ID</th><th>Name</th><th>Slug / Key</th><th width="80">Order</th><th width="160">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($list as $row): ?>
            <tr>
              <td align="center"><?php echo (int)$row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><code><?php echo htmlspecialchars($row['slug']); ?></code></td>
              <td align="center"><?php echo (int)$row['sort_order']; ?></td>
              <td align="center">
                <a class="more" href="admin_vehicle_types.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
                <form method="post" action="admin_vehicle_types.php" class="admin_inline_form"
                      onsubmit="return confirm('Delete \'<?php echo htmlspecialchars(addslashes($row['name'])); ?>\'?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
                  <input type="submit" class="more" value="Delete" />
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php
    }
    ?>

    <div class="post_box">
      <h2>Road &mdash; closed list (<?php echo count($rows_road); ?>)</h2>
      <?php aw_vt_table($rows_road, $csrf_token); ?>
    </div>

    <div class="post_box">
      <h2>Special &mdash; complement (<?php echo count($rows_special); ?>)</h2>
      <?php aw_vt_table($rows_special, $csrf_token); ?>
    </div>

<?php require __DIR__ . '/admin_footer.php'; ?>

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

$admin_title  = 'Premium Approvals';
$admin_active = 'users';
require __DIR__ . '/admin_header.php';
?>

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
      <table class="admin_table" border="1" cellpadding="1" cellspacing="0">
        <thead>
        <tr>
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
        <tr<?php echo $has_pend ? ' class="admin_row_pending"' : ''; ?>>
          <td align="center" valign="middle"><?php echo (int)$u['id_user']; ?></td>
          <td align="center" valign="middle"><?php echo htmlspecialchars($u['username']); ?></td>
          <td align="center" valign="middle"><?php echo htmlspecialchars($u['email']); ?></td>
          <td align="center" valign="middle">
            <?php if ($is_admin): ?>
            <strong>admin</strong>
            <?php elseif ($is_premium): ?>
            <strong>premium</strong>
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
            <form method="post" action="grant_premium.php"
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
            <form method="post" action="grant_premium.php">
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
  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/manage_records.php`

```php
<?php
// ============================================================
// /_admin/manage_records.php
// Hub del pannello "Records": punto unico da cui l'admin raggiunge i
// form di inserimento/modifica dei record nelle tabelle principali
// (annunci free, annunci premium, aziende, tipi veicolo, blog, utenti).
//
// Mostra il conteggio dei record per ciascuna tabella e i link agli editor.
// Pagina di sola lettura. Stile: solo classi del foglio esistente (dir. 8).
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

function aw_count(PDO $pdo, string $table): int
{
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$cards = [
    ['02_free_ads',   'Free Ads',      aw_count($pdo, '02_free_ads'),   'edit_ad.php?type=free',    'Insert / edit free classified ads.'],
    ['03_ads',        'Premium Ads',   aw_count($pdo, '03_ads'),        'edit_ad.php?type=premium', 'Insert / edit premium ads + technical details.'],
    ['06_company',    'Companies',     aw_count($pdo, '06_company'),    'edit_company.php',         'Insert / edit companies + products, special categories, services.'],
    ['vehicle_types', 'Vehicle Types', aw_count($pdo, 'vehicle_types'), 'admin_vehicle_types.php',  'Road / Special taxonomy used by the filters.'],
    ['blog',          'Blog posts',    aw_count($pdo, 'blog'),          'moderate_blog.php',        'Review and publish blog articles.'],
    ['users',         'Users',         aw_count($pdo, 'users'),         'dashboard.php',            'User tiers and premium approvals.'],
];

$admin_title  = 'Records';
$admin_active = 'records';
require __DIR__ . '/admin_header.php';
?>


    <div class="post_box">
      <h2>Insert / edit records</h2>
      <p>Pick a table to add new records or edit existing ones. Image upload, gallery and
         full deletion stay in the dedicated moderation pages and in the public flow, so
         the <code>upload</code> / <code>images</code> folders are never touched from here.</p>

      <table class="admin_table" border="1" cellpadding="6" cellspacing="0">
        <thead>
          <tr>
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
         action, target user, timestamp and IP. Child tables (galleries, products, services)
         are synced from their own editors.</p>
    </div>

  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/edit_ad.php`

```php
<?php
// ============================================================
// /_admin/edit_ad.php
// Inserimento e modifica record annunci (free + premium) da pannello admin.
//
//  ?type=free     -> tabella 02_free_ads (scadenza 45 giorni)
//  ?type=premium  -> tabella 03_ads     (scadenza 60 giorni) + 03_ads_tech_details
//  ?edit=ID       -> carica il record indicato nel form di modifica
//
// Per gli annunci PREMIUM viene gestita anche la riga 1:1 dei dettagli
// tecnici (03_ads_tech_details): upsert in transazione insieme all'annuncio.
//
// La cancellazione (con file/gallery/tech_details) resta a carico di
// moderate_ads.php: qui si fa solo INSERT/UPDATE, senza toccare
// upload/images (dir. 15) ne' la gallery.
//
// Stile: solo classi del foglio esistente (dir. 8), nessuno stile inline.
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$admin_id = AdminAuth::requireAdminSession();

// Tipo annuncio -> tabella (whitelist: niente nome tabella da input libero)
$type = (($_GET['type'] ?? $_POST['type'] ?? 'free') === 'premium') ? 'premium' : 'free';
$is_premium    = ($type === 'premium');
$table         = $is_premium ? '03_ads' : '02_free_ads';
$label         = $is_premium ? 'Premium Ad' : 'Free Ad';
$interval_days = $is_premium ? 60 : 45;

$success = '';
$error   = '';

$allowed_status     = ['pending', 'approved', 'rejected'];
$allowed_types      = ['New on sell', 'Used on sell', 'For rent', 'Project'];
$allowed_conditions = ['New', 'As good as new', 'Used', 'Poor', 'Project'];
$checkbox_fields    = ['racing', 'promotion', 'horse', 'hospitality', 'medical',
                       'military', 'motorhome', 'technology', 'street_food'];

// ------------------------------------------------------------
// Dettagli tecnici premium (03_ads_tech_details)
// ------------------------------------------------------------
// Campi testo con default (coerenti con lo schema)
$tech_text = [
    'cars' => '0', 'Lift_manufactorer' => '', 'Lift_length' => '', 'Lift_width' => '',
    'Lift_capacity' => '0 kg', 'painted' => '', 'Stickers' => '', 'axles' => '1',
    'MGW' => '', 'Saddle' => '', 'ext_length' => '', 'ext_width' => '', 'ext_height' => '',
];
// Campi booleani (tinyint 1/0)
$tech_bool = [
    'Awning', 'Workshop', 'Belly', 'Kitchen', 'Beds', 'Genset', 'Bathroom', 'SAT',
    'rails', 'LED', 'independent_entrance_cargo', 'Fixing', 'Cabinets', 'Adjustable',
    'Workbenches', 'HVAC', 'Telemetry', 'independent_entrance_office', 'Electrical',
    'office_other', 'Windows', 'TV', 'Main_panel', 'batteries', 'Charger', 'Connection',
    'Switchgear', 'electrical_other', 'Sockets', 'Rema', 'Plywood', 'Sandwich',
    'Special', 'Stepdeck', 'Straightline', 'chassis_special',
];

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
            $stmt = $pdo->prepare('SELECT username, email, phone FROM users WHERE id_user = :id LIMIT 1');
            $stmt->execute([':id' => $id_user]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$owner) {
                $error = 'The selected owner does not exist.';
            } else {
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

            // Valori dettagli tecnici (solo premium)
            $tech_values = [];
            if ($is_premium) {
                foreach ($tech_text as $f => $default) {
                    $val = trim((string)($_POST['tech_' . $f] ?? ''));
                    $tech_values[$f] = ($val === '') ? $default : $val;
                }
                foreach ($tech_bool as $f) {
                    $tech_values[$f] = isset($_POST['tech_' . $f]) ? 1 : 0;
                }
            }

            try {
                $pdo->beginTransaction();

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
                    $pdo->prepare($sql)->execute($params);
                    $rec_id = (int)$pdo->lastInsertId();
                    $audit_action = 'ad_create';
                } else {
                    $rec_id = (int)($_POST['id_ads'] ?? 0);
                    $params[':id_ads'] = $rec_id;
                    // expires_at NON viene toccato (preserva la scadenza)
                    $sql = "UPDATE `{$table}` SET
                        id_user=:id_user, author=:author, email=:email, phone=:phone,
                        title=:title, subtitle=:subtitle, list_price=:list_price, status=:status,
                        type=:type, conditions=:conditions, description=:description,
                        racing=:racing, promotion=:promotion, horse=:horse, hospitality=:hospitality,
                        medical=:medical, military=:military, motorhome=:motorhome, technology=:technology,
                        street_food=:street_food, item_kind=:item_kind, macro_category=:macro_category,
                        vehicle_type=:vehicle_type, image_original=:image_original, image_thumbnail=:image_thumbnail
                        WHERE id_ads=:id_ads LIMIT 1";
                    $pdo->prepare($sql)->execute($params);
                    $audit_action = 'ad_update';
                }

                // --- Upsert dettagli tecnici premium ---
                if ($is_premium && $rec_id > 0) {
                    $tparams = [':id_ads' => $rec_id];
                    foreach ($tech_values as $f => $val) { $tparams[':' . $f] = $val; }

                    $stmt = $pdo->prepare('SELECT id_tech FROM `03_ads_tech_details` WHERE id_ads = :id LIMIT 1');
                    $stmt->execute([':id' => $rec_id]);
                    $has_tech = (bool)$stmt->fetchColumn();

                    if ($has_tech) {
                        $sets = [];
                        foreach (array_keys($tech_values) as $f) { $sets[] = "`{$f}`=:{$f}"; }
                        $pdo->prepare("UPDATE `03_ads_tech_details` SET " . implode(', ', $sets)
                            . " WHERE id_ads=:id_ads LIMIT 1")->execute($tparams);
                    } else {
                        $cols = array_keys($tech_values);
                        $col_list = '`id_ads`, `' . implode('`, `', $cols) . '`';
                        $val_list = ':id_ads, :' . implode(', :', $cols);
                        $pdo->prepare("INSERT INTO `03_ads_tech_details` ({$col_list}) VALUES ({$val_list})")
                            ->execute($tparams);
                    }
                }

                $pdo->commit();

                UserTier::logAdminAction($pdo, $admin_id, $audit_action, $id_user,
                    $label . ' ' . ($action === 'add' ? 'created' : 'updated')
                    . ' (id_ads=' . $rec_id . ', table=' . $table . ')',
                    $_SERVER['REMOTE_ADDR'] ?? '');
                $_SESSION['admin_success'] = $label . ' ' . ($action === 'add' ? 'created' : 'updated')
                    . ' (ID ' . $rec_id . ').';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('[Allonwheel] admin ad save error: ' . $e->getMessage());
                $error = 'Database error while saving the ad.';
            }

            if ($error === '') {
                header('Location: edit_ad.php?type=' . $type);
                exit;
            }
        }
    }
}

if ($success === '') { $success = $_SESSION['admin_success'] ?? ''; }
unset($_SESSION['admin_success']);

// ------------------------------------------------------------
// Record da modificare (?edit=ID)
// ------------------------------------------------------------
$edit_item = null;
$edit_tech = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id_ads = :id LIMIT 1");
    $stmt->execute([':id' => $edit_id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($edit_item && $is_premium) {
        $stmt = $pdo->prepare('SELECT * FROM `03_ads_tech_details` WHERE id_ads = :id LIMIT 1');
        $stmt->execute([':id' => $edit_id]);
        $edit_tech = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$users = $pdo->query('SELECT id_user, username, email FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);

$road_types    = VehicleTaxonomy::typesByMacro(VehicleTaxonomy::MACRO_ROAD, $pdo);
$special_types = VehicleTaxonomy::typesByMacro(VehicleTaxonomy::MACRO_SPECIAL, $pdo);

$list = $pdo->query("SELECT id_ads, id_user, title, status, list_price, vehicle_type, created_at
                     FROM `{$table}` ORDER BY id_ads DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

function aw_sel($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}
function aw_chk($item, $field) {
    return (!empty($item) && (int)($item[$field] ?? 0) === 1) ? ' checked="checked"' : '';
}
$v = function ($key, $default = '') use ($edit_item) {
    return htmlspecialchars((string)($edit_item[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$vt = function ($key, $default = '') use ($edit_tech) {
    return htmlspecialchars((string)($edit_tech[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};

$admin_title  = 'Records — Ads (' . $label . ')';
$admin_active = 'records';
require __DIR__ . '/admin_header.php';
?>


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

        <table class="admin_form" width="100%" border="0" cellpadding="6">
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
              <td><input type="text" name="title" maxlength="200" class="input_field aw_in_l" value="<?php echo $v('title'); ?>" required /></td></tr>
          <tr><td><label>Subtitle:</label></td>
              <td><input type="text" name="subtitle" maxlength="200" class="input_field aw_in_l" value="<?php echo $v('subtitle'); ?>" /></td></tr>
          <tr><td><label>List price:</label></td>
              <td><input type="text" name="list_price" class="input_field admin_price" value="<?php echo $edit_item ? htmlspecialchars(number_format((float)$edit_item['list_price'], 2, '.', '')) : ''; ?>" />
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
          <tr><td><label>Vehicle type / category:</label></td>
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
              <small> Macro (road/special) is derived automatically.</small></td></tr>
          <tr><td><label>Tags:</label></td>
              <td>
                <?php foreach ($checkbox_fields as $chk): ?>
                <label class="admin_tag">
                  <input type="checkbox" name="<?php echo $chk; ?>" value="1"<?php echo aw_chk($edit_item, $chk); ?> />
                  <?php echo ucfirst(str_replace('_', ' ', $chk)); ?>
                </label>
                <?php endforeach; ?>
              </td></tr>
          <tr><td><label>Description:</label></td>
              <td><textarea name="description" rows="5" class="input_field admin_textarea" required><?php echo $v('description'); ?></textarea></td></tr>
          <tr><td><label>Contacts (optional):</label></td>
              <td>
                Author <input type="text" name="author" maxlength="100" class="input_field aw_in_s" value="<?php echo $v('author'); ?>" />
                Email <input type="text" name="email" maxlength="150" class="input_field aw_in_m" value="<?php echo $v('email'); ?>" />
                Phone <input type="text" name="phone" maxlength="30" class="input_field aw_in_s" value="<?php echo $v('phone'); ?>" />
                <br /><small>Leave empty to inherit from the owner's profile.</small>
              </td></tr>
          <tr><td><label>Image filenames:</label></td>
              <td>
                Original <input type="text" name="image_original" maxlength="255" class="input_field aw_in_m" value="<?php echo $v('image_original', 'no_image.jpg'); ?>" />
                Thumb <input type="text" name="image_thumbnail" maxlength="255" class="input_field aw_in_m" value="<?php echo $v('image_thumbnail', 'no_image.jpg'); ?>" />
                <br /><small>Filenames only &mdash; no upload here.</small>
              </td></tr>
        </table>

        <?php if ($is_premium): ?>
        <!-- ===== Dettagli tecnici (solo premium) ===== -->
        <fieldset class="admin_fieldset">
          <legend>Technical details (premium)</legend>
          <table class="admin_form" width="100%" border="0" cellpadding="6">
            <tr>
              <td width="180"><label>Numeric / text:</label></td>
              <td>
                <?php foreach ($tech_text as $f => $default): ?>
                <label class="admin_tag"><?php echo htmlspecialchars($f); ?>
                  <input type="text" name="tech_<?php echo $f; ?>" class="input_field aw_in_s" value="<?php echo $vt($f, $edit_tech ? '' : $default); ?>" />
                </label>
                <?php endforeach; ?>
              </td>
            </tr>
            <tr>
              <td><label>Features:</label></td>
              <td>
                <?php foreach ($tech_bool as $f): ?>
                <label class="admin_tag">
                  <input type="checkbox" name="tech_<?php echo $f; ?>" value="1"<?php echo aw_chk($edit_tech, $f); ?> />
                  <?php echo htmlspecialchars(str_replace('_', ' ', $f)); ?>
                </label>
                <?php endforeach; ?>
              </td>
            </tr>
          </table>
          <p><small>A technical-details row is created/updated together with the premium ad.</small></p>
        </fieldset>
        <?php endif; ?>

        <table class="admin_form" width="100%" border="0" cellpadding="6">
          <tr><td width="180"></td>
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
      <table class="admin_table" border="1" cellpadding="4" cellspacing="0">
        <thead>
          <tr>
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

  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/edit_company.php`

```php
<?php
// ============================================================
// /_admin/edit_company.php
// Inserimento e modifica record azienda (06_company) + associazioni
// (prodotti, servizi, categorie speciali) da pannello admin.
//
//  ?edit=ID  -> carica il record nel form di modifica
//
// Vincolo: max 1 azienda per utente (dir. 3).
// Le associazioni usano lo stesso modello del flusso utente
// (CompanyManager::$products / $services / $products_special) con sync
// "delete + reinsert" in transazione; note e flag prodotto esistenti
// vengono conservati per le chiavi che restano selezionate (dir. 9).
//
// Cancellazione completa (logo, gallery, prodotti, servizi) -> manage_companies.php.
// Nessun upload qui (dir. 15). Stile: solo classi esistenti (dir. 8), niente inline.
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/06_company.class.php';

$admin_id = AdminAuth::requireAdminSession();

$success = '';
$error   = '';

$text_fields = [
    'ragione_sociale'    => true,  'partita_iva'        => true,
    'codice_fiscale'     => false, 'indirizzo'          => true,
    'cap'                => true,  'citta'              => true,
    'provincia'          => true,  'nazione'            => true,
    'telefono'           => false, 'cellulare'          => false,
    'fax'                => false, 'email'              => true,
    'pec'                => false, 'sito_web'           => false,
    'referente_nome'     => false, 'referente_cognome'  => false,
    'referente_ruolo'    => false, 'referente_email'    => false,
    'referente_telefono' => false,
];

// Cataloghi associazioni (dal modello esistente)
$catalog_products = CompanyManager::$products;          // [key => label]
$catalog_services = CompanyManager::$services;          // [key => label]
$catalog_special  = CompanyManager::$products_special;  // [key => label]
// Flag booleani di 06_company_products (UI volutamente non li espone: default 0)
$product_flags = ['certificazioni_prodotto', 'campioni_gratuiti', 'assistenza_posa',
                  'progettazione_supporto', 'schede_tecniche'];

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

        // Associazioni selezionate (intersezione col catalogo: niente chiavi inventate)
        $sel_products = array_values(array_intersect((array)($_POST['products'] ?? []), array_keys($catalog_products)));
        $sel_services = array_values(array_intersect((array)($_POST['services'] ?? []), array_keys($catalog_services)));
        $sel_special  = array_values(array_intersect((array)($_POST['special']  ?? []), array_keys($catalog_special)));

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
            $stmt = $pdo->prepare('SELECT id_user FROM users WHERE id_user = :id LIMIT 1');
            $stmt->execute([':id' => $user_id]);
            if (!$stmt->fetchColumn()) {
                $error = 'The selected owner does not exist.';
            } else {
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
            foreach (array_keys($text_fields) as $f) { $params[':' . $f] = $data[$f]; }
            $params[':user_id']     = $user_id;
            $params[':descrizione'] = $descrizione;
            $params[':logo']        = ($logo === '') ? null : $logo;
            $params[':attiva']      = $attiva;

            try {
                $pdo->beginTransaction();

                if ($action === 'add') {
                    $cols = array_keys($text_fields);
                    $col_list = '`user_id`, `' . implode('`, `', $cols) . '`, `descrizione`, `logo`, `attiva`';
                    $val_list = ':user_id, :' . implode(', :', $cols) . ', :descrizione, :logo, :attiva';
                    $pdo->prepare("INSERT INTO `06_company` ({$col_list}) VALUES ({$val_list})")->execute($params);
                    $company_id   = (int)$pdo->lastInsertId();
                    $audit_action = 'company_create';
                } else {
                    $params[':id'] = $company_id;
                    $sets = [];
                    foreach (array_keys($text_fields) as $f) { $sets[] = "`{$f}`=:{$f}"; }
                    $sets[] = '`user_id`=:user_id';
                    $sets[] = '`descrizione`=:descrizione';
                    $sets[] = '`logo`=:logo';
                    $sets[] = '`attiva`=:attiva';
                    $pdo->prepare("UPDATE `06_company` SET " . implode(', ', $sets) . " WHERE id=:id LIMIT 1")->execute($params);
                    $audit_action = 'company_update';
                }

                // --- Conserva note/flag prodotto esistenti (dir. 9) ---
                $prev_products = [];
                $stmt = $pdo->prepare('SELECT * FROM `06_company_products` WHERE company_id = :id');
                $stmt->execute([':id' => $company_id]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $prev_products[$r['product_key']] = $r; }

                $prev_services = [];
                $stmt = $pdo->prepare('SELECT * FROM `06_company_services` WHERE company_id = :id');
                $stmt->execute([':id' => $company_id]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $prev_services[$r['service_key']] = $r; }

                $prev_special = [];
                $stmt = $pdo->prepare('SELECT * FROM `06_company_products_special` WHERE company_id = :id');
                $stmt->execute([':id' => $company_id]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $prev_special[$r['product_key']] = $r; }

                // --- Sync prodotti regolari ---
                $pdo->prepare('DELETE FROM `06_company_products` WHERE company_id = :id')->execute([':id' => $company_id]);
                if (!empty($sel_products)) {
                    $cols = array_merge(['company_id', 'product_key', 'note'], $product_flags);
                    $ph   = ':' . implode(', :', $cols);
                    $ins  = $pdo->prepare('INSERT INTO `06_company_products` (`' . implode('`, `', $cols) . "`) VALUES ({$ph})");
                    foreach ($sel_products as $key) {
                        $prev = $prev_products[$key] ?? [];
                        $row  = [':company_id' => $company_id, ':product_key' => $key, ':note' => $prev['note'] ?? null];
                        foreach ($product_flags as $fl) { $row[':' . $fl] = (int)($prev[$fl] ?? 0); }
                        $ins->execute($row);
                    }
                }

                // --- Sync servizi ---
                $pdo->prepare('DELETE FROM `06_company_services` WHERE company_id = :id')->execute([':id' => $company_id]);
                if (!empty($sel_services)) {
                    $ins = $pdo->prepare('INSERT INTO `06_company_services` (company_id, service_key, note) VALUES (:cid, :key, :note)');
                    foreach ($sel_services as $key) {
                        $ins->execute([':cid' => $company_id, ':key' => $key, ':note' => $prev_services[$key]['note'] ?? null]);
                    }
                }

                // --- Sync categorie speciali ---
                $pdo->prepare('DELETE FROM `06_company_products_special` WHERE company_id = :id')->execute([':id' => $company_id]);
                if (!empty($sel_special)) {
                    $ins = $pdo->prepare('INSERT INTO `06_company_products_special` (company_id, product_key, note) VALUES (:cid, :key, :note)');
                    foreach ($sel_special as $key) {
                        $ins->execute([':cid' => $company_id, ':key' => $key, ':note' => $prev_special[$key]['note'] ?? null]);
                    }
                }

                $pdo->commit();

                UserTier::logAdminAction($pdo, $admin_id, $audit_action, $user_id,
                    'Company ' . ($action === 'add' ? 'created' : 'updated') . ' (id=' . $company_id . ')',
                    $_SERVER['REMOTE_ADDR'] ?? '');
                $_SESSION['admin_success'] = 'Company ' . ($action === 'add' ? 'created' : 'updated') . ' (ID ' . $company_id . ').';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('[Allonwheel] admin company save error: ' . $e->getMessage());
                $error = 'Database error while saving the company (VAT number may already be in use).';
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

// Record da modificare + associazioni correnti
$edit_item    = null;
$cur_products = [];
$cur_services = [];
$cur_special  = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM `06_company` WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $edit_id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($edit_item) {
        $stmt = $pdo->prepare('SELECT product_key FROM `06_company_products` WHERE company_id = :id');
        $stmt->execute([':id' => $edit_id]);
        $cur_products = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare('SELECT service_key FROM `06_company_services` WHERE company_id = :id');
        $stmt->execute([':id' => $edit_id]);
        $cur_services = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare('SELECT product_key FROM `06_company_products_special` WHERE company_id = :id');
        $stmt->execute([':id' => $edit_id]);
        $cur_special = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$users = $pdo->query('SELECT id_user, username, email FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);
$list  = $pdo->query('SELECT id, user_id, ragione_sociale, partita_iva, citta, attiva
                      FROM `06_company` ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

$v = function ($key, $default = '') use ($edit_item) {
    return htmlspecialchars((string)($edit_item[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
function aw_sel_c($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}

$field_labels = [
    'ragione_sociale' => 'Company name', 'partita_iva' => 'VAT number',
    'codice_fiscale' => 'Tax code', 'indirizzo' => 'Address', 'cap' => 'Postal code',
    'citta' => 'City', 'provincia' => 'Province', 'nazione' => 'Country',
    'telefono' => 'Phone', 'cellulare' => 'Mobile', 'fax' => 'Fax', 'email' => 'E-mail',
    'pec' => 'PEC', 'sito_web' => 'Website', 'referente_nome' => 'Contact first name',
    'referente_cognome' => 'Contact last name', 'referente_ruolo' => 'Contact role',
    'referente_email' => 'Contact e-mail', 'referente_telefono' => 'Contact phone',
];

// Render di un gruppo di checkbox associazioni
function aw_assoc_group(string $field, array $catalog, array $current): void {
    foreach ($catalog as $key => $lbl) {
        $checked = in_array($key, $current, true) ? ' checked="checked"' : '';
        echo '<label class="admin_tag"><input type="checkbox" name="' . htmlspecialchars($field)
           . '[]" value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '"' . $checked . ' /> '
           . htmlspecialchars($lbl) . '</label>' . "\n";
    }
}

$admin_title  = 'Records — Companies';
$admin_active = 'records';
require __DIR__ . '/admin_header.php';
?>


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

        <table class="admin_form" width="100%" border="0" cellpadding="6">
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
            <td><input type="text" name="<?php echo $f; ?>" class="input_field aw_in_l"
                       value="<?php echo $v($f); ?>"<?php echo $required ? ' required' : ''; ?> /></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td><label>Description:</label></td>
            <td><textarea name="descrizione" rows="4" class="input_field admin_textarea"><?php echo $v('descrizione'); ?></textarea></td>
          </tr>
          <tr>
            <td><label>Logo filename:</label></td>
            <td><input type="text" name="logo" maxlength="255" class="input_field aw_in_m" value="<?php echo $v('logo'); ?>" />
                <br /><small>Filename only (in /uploads/06_company/) &mdash; no upload here.</small></td>
          </tr>
          <tr>
            <td><label>Visible in directory:</label></td>
            <td><label class="admin_tag"><input type="checkbox" name="attiva" value="1"<?php echo (!$edit_item || (int)$edit_item['attiva'] === 1) ? ' checked="checked"' : ''; ?> /> Active</label></td>
          </tr>
        </table>

        <fieldset class="admin_fieldset">
          <legend>Products (Road / Special vehicle types)</legend>
          <?php aw_assoc_group('products', $catalog_products, $cur_products); ?>
        </fieldset>

        <fieldset class="admin_fieldset">
          <legend>Special categories</legend>
          <?php aw_assoc_group('special', $catalog_special, $cur_special); ?>
        </fieldset>

        <fieldset class="admin_fieldset">
          <legend>Services</legend>
          <?php aw_assoc_group('services', $catalog_services, $cur_services); ?>
        </fieldset>

        <table class="admin_form" width="100%" border="0" cellpadding="6">
          <tr>
            <td width="180"></td>
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
      <table class="admin_table" border="1" cellpadding="4" cellspacing="0">
        <thead>
          <tr>
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

  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/moderate_ads.php`

```php
<?php
// ============================================================
// /_admin/moderate_ads.php
// Pannello moderazione annunci: visualizza tutti gli annunci
// (free e premium) con possibilità di approve/reject per l'admin.
//
// PRE-REQUISITO: eseguire sql/patch_v2_ads_status.sql prima di
// attivare questo file (aggiunge la colonna `status` alle tabelle).
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

// -------------------------------------------------------------------
// Gestione azioni POST (approve / reject)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $ad_id   = (int)($_POST['ad_id']   ?? 0);
  $ad_type = in_array($_POST['ad_type'] ?? '', ['free', 'premium'], true)
               ? $_POST['ad_type'] : '';
  $action  = in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)
               ? $_POST['action'] : '';

  if ($ad_id > 0 && $ad_type !== '' && $action !== '') {
    $table = $ad_type === 'free' ? '02_free_ads' : '03_ads';
    $label = $ad_type === 'free' ? 'Free Ad' : 'Premium Ad';

    if ($action === 'delete') {
      // ----- Cancellazione completa: annuncio + figli + file fisici -----
      $gallery_table = $table . '_gallery';
      $upload_base   = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/' . $table . '/';

      // Immagini di copertina (record principale) e gallery, PRIMA del DELETE
      $stmt = $pdo->prepare("SELECT image_original, image_thumbnail FROM `{$table}` WHERE id_ads = :id LIMIT 1");
      $stmt->execute([':id' => $ad_id]);
      $cover = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

      $stmt = $pdo->prepare("SELECT image_original, image_thumbnail FROM `{$gallery_table}` WHERE id_ads = :id");
      $stmt->execute([':id' => $ad_id]);
      $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

      try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM `{$gallery_table}` WHERE id_ads = :id")->execute([':id' => $ad_id]);
        if ($ad_type === 'premium') {
          // Dettagli tecnici (solo premium)
          $pdo->prepare("DELETE FROM `03_ads_tech_details` WHERE id_ads = :id")->execute([':id' => $ad_id]);
        }
        $pdo->prepare("DELETE FROM `{$table}` WHERE id_ads = :id LIMIT 1")->execute([':id' => $ad_id]);
        $pdo->commit();
      } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[Allonwheel] admin delete ad error (id=' . $ad_id . '): ' . $e->getMessage());
        $_SESSION['admin_error'] = 'Database error while deleting the ad.';
        header('Location: /_admin/moderate_ads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
        exit;
      }

      // Cleanup file DOPO il commit, con protezione path-traversal
      $delete_file = static function (string $dir, ?string $filename): void {
        $filename = basename((string)$filename);
        if ($filename === '' || $filename === 'no_image.jpg') { return; }
        $full = realpath($dir . $filename);
        $base = realpath($dir);
        if ($full === false || $base === false) { return; }
        if (strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) { return; }
        if (is_file($full)) { @unlink($full); }
      };
      $delete_file($upload_base . 'original/',  $cover['image_original']  ?? '');
      $delete_file($upload_base . 'thumbnail/', $cover['image_thumbnail'] ?? '');
      foreach ($gallery_images as $g) {
        $delete_file($upload_base . 'original/',  $g['image_original']  ?? '');
        $delete_file($upload_base . 'thumbnail/', $g['image_thumbnail'] ?? '');
      }

      $_SESSION['admin_success'] = "#{$ad_id} ({$label}) deleted permanently.";
      UserTier::logAdminAction(
        $pdo, $admin_id, 'delete_ad', null,
        $table . ' #' . $ad_id . ' deleted (with gallery' . ($ad_type === 'premium' ? ' + tech details' : '') . ')',
        $_SERVER['REMOTE_ADDR'] ?? ''
      );

    } else {
      // ----- Moderazione (approve / reject) -----
      $new_status = $action === 'approve' ? 'approved' : 'rejected';
      $stmt = $pdo->prepare("UPDATE `{$table}` SET `status` = :s WHERE `id_ads` = :id LIMIT 1");
      $stmt->execute([':s' => $new_status, ':id' => $ad_id]);

      $_SESSION['admin_success'] = "#{$ad_id} ({$label}) marked as {$new_status}.";
      UserTier::logAdminAction(
        $pdo, $admin_id, 'moderate_ad', null,
        $table . ' #' . $ad_id . ' marked as ' . $new_status,
        $_SERVER['REMOTE_ADDR'] ?? ''
      );
    }
  }

  header('Location: /_admin/moderate_ads.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
  exit;
}

// -------------------------------------------------------------------
// Filtro status
// -------------------------------------------------------------------
$allowed_filters = ['all', 'pending', 'approved', 'rejected'];
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'pending';
$where  = $filter === 'all' ? '' : "WHERE a.`status` = " . $pdo->quote($filter);

// -------------------------------------------------------------------
// Recupero annunci da entrambe le tabelle
// -------------------------------------------------------------------
$ads = [];

foreach ([['02_free_ads', 'free', 'Free Ad'], ['03_ads', 'premium', 'Premium Ad']] as [$table, $type, $label]) {
  $sql = "SELECT a.id_ads, a.title, a.status, a.created_at,
                 u.username, u.email AS user_email
          FROM `{$table}` a
          LEFT JOIN `users` u ON u.id_user = a.id_user
          {$where}
          ORDER BY a.created_at DESC
          LIMIT 200";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $ads[] = array_merge($row, ['ad_type' => $type, 'type_label' => $label, 'table' => $table]);
  }
}

// Ordina per data decrescente (le due tabelle arrivano già ordinate, ma mescolate)
usort($ads, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

// Contatori per filtro rapido
$count_sql = "
  SELECT
    SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected_count,
    COUNT(*) AS total
  FROM (
    SELECT status FROM `02_free_ads`
    UNION ALL
    SELECT status FROM `03_ads`
  ) AS combined
";
$counts = $pdo->query($count_sql)->fetch(PDO::FETCH_ASSOC);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Ad Moderation';
$admin_active = 'ads';
require __DIR__ . '/admin_header.php';
?>

    </div>

    <!-- Riepilogo e filtri -->
    <div class="post_box">
      <h2>Ads overview</h2>
      <p>
        <strong>Pending:</strong> <?php echo (int)$counts['pending_count']; ?> &nbsp;|&nbsp;
        <strong>Approved:</strong> <?php echo (int)$counts['approved_count']; ?> &nbsp;|&nbsp;
        <strong>Rejected:</strong> <?php echo (int)$counts['rejected_count']; ?> &nbsp;|&nbsp;
        <strong>Total:</strong> <?php echo (int)$counts['total']; ?>
      </p>
      <div class="post_meta">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $f => $fl): ?>
        <a href="?filter=<?php echo $f; ?>"<?php echo $filter === $f ? ' class="admin_nav_active"' : ''; ?>>
          <?php echo $fl; ?> (<?php echo $f === 'all' ? (int)$counts['total'] : (int)($counts[$f . '_count'] ?? 0); ?>)
        </a>&nbsp;|&nbsp;
        <?php endforeach; ?>
        <div class="cleaner"></div>
      </div>
    </div>

    <!-- Tabella annunci -->
    <div class="post_box">
      <h2>
        <?php echo ucfirst($filter); ?> ads
        <?php if (empty($ads)): ?>— none found<?php endif; ?>
      </h2>

      <?php if (!empty($ads)): ?>
      <table border="1" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row">
          <th>ID</th>
          <th>Type</th>
          <th>Title</th>
          <th>User</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad):
          $is_pending  = $ad['status'] === 'pending';
          $is_approved = $ad['status'] === 'approved';
          $is_rejected = $ad['status'] === 'rejected';
          $row_class = $is_pending ? 'admin_row_pending' : ($is_rejected ? 'admin_row_rejected' : '');
        ?>
        <tr<?php echo $row_class ? ' class="' . $row_class . '"' : ''; ?>>
          <td><?php echo (int)$ad['id_ads']; ?></td>
          <td><?php echo htmlspecialchars($ad['type_label'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars(mb_strtolower(mb_substr($ad['title'], 0, 60)) . (mb_strlen($ad['title']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php echo htmlspecialchars($ad['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?><br />
            <small><?php echo htmlspecialchars($ad['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($ad['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if ($is_approved): ?><strong class="admin_ok">approved</strong>
            <?php elseif ($is_rejected): ?><em class="admin_bad">rejected</em>
            <?php else: ?><strong>pending</strong><?php endif; ?>
          </td>
          <td>
            <?php if (!$is_approved): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" class="admin_inline_form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="approve" />
              <button type="submit" class="more">Approve</button>
            </form>
            <?php endif; ?>
            <?php if (!$is_rejected): ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form"
                  data-confirm="Reject ad #<?php echo (int)$ad['id_ads']; ?>?">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_ads.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form"
                  data-confirm="Permanently delete ad #<?php echo (int)$ad['id_ads']; ?> and all its images? This cannot be undone.">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="ad_id"   value="<?php echo (int)$ad['id_ads']; ?>" />
              <input type="hidden" name="ad_type"  value="<?php echo htmlspecialchars($ad['ad_type'], ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action"   value="delete" />
              <button type="submit" class="more">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="post_box">
      <h3>Notes</h3>
      <p>
        Every approve/reject action is logged in <code>admin_audit_log</code>.<br />
        To activate pre-publication moderation (new ads default to <em>pending</em>), run
        <code>UPDATE `02_free_ads` SET status='pending' WHERE ...</code> and set
        <code>DEFAULT 'pending'</code> in the schema after running the SQL migration.
      </p>
    </div>

  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/manage_companies.php`

```php
<?php
// ============================================================
// /_admin/manage_companies.php
// Pannello gestione aziende: elenco completo con possibilità di
// attivare/disattivare la visibilità pubblica (campo `attiva`).
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// Nessuna migrazione DB necessaria: il campo `attiva` esiste già
// nella tabella `06_company`.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

// -------------------------------------------------------------------
// Gestione azioni POST (activate / deactivate)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $company_id = (int)($_POST['company_id'] ?? 0);
    $action = in_array($_POST['action'] ?? '', ['activate', 'deactivate', 'delete'], true)
        ? $_POST['action']
        : '';

    if ($company_id > 0 && $action !== '') {

        if ($action === 'delete') {
            // ----- Cancellazione completa azienda + tabelle figlie + file -----
            $upload_base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/';

            // Logo + immagini gallery PRIMA del DELETE
            $stmt = $pdo->prepare("SELECT logo FROM `06_company` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $company_id]);
            $company_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmt = $pdo->prepare("SELECT immagine FROM `06_company_gallery` WHERE company_id = :id");
            $stmt->execute([':id' => $company_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            try {
                $pdo->beginTransaction();
                // Tabelle figlie (associazioni e gallery)
                $pdo->prepare("DELETE FROM `06_company_gallery` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products_special` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_services` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company` WHERE id = :id LIMIT 1")->execute([':id' => $company_id]);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[Allonwheel] admin delete company error (id=' . $company_id . '): ' . $e->getMessage());
                $_SESSION['admin_error'] = 'Database error while deleting the company.';
                header('Location: /_admin/manage_companies.php');
                exit;
            }

            // Cleanup file DOPO il commit, con protezione path-traversal
            $delete_file = static function (string $dir, ?string $filename): void {
                $filename = basename((string)$filename);
                if ($filename === '' || $filename === 'no_image.jpg') { return; }
                $full = realpath($dir . $filename);
                $base = realpath($dir);
                if ($full === false || $base === false) { return; }
                if (strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) { return; }
                if (is_file($full)) { @unlink($full); }
            };
            $logo = (string)($company_row['logo'] ?? '');
            foreach (['original/', 'thumbnail/'] as $sub) {
                $delete_file($upload_base . $sub, $logo);
                foreach ($gallery_images as $g) {
                    $delete_file($upload_base . $sub, $g['immagine'] ?? '');
                }
            }
            // Compatibilita' vecchi upload flat (pre-refactoring)
            $delete_file($upload_base, $logo);
            foreach ($gallery_images as $g) { $delete_file($upload_base, $g['immagine'] ?? ''); }

            $_SESSION['admin_success'] = "Company #{$company_id} deleted permanently.";
            UserTier::logAdminAction(
                $pdo, $admin_id, 'delete_company', null,
                '06_company #' . $company_id . ' deleted (gallery, products, special, services)',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

        } else {
            // ----- Attiva / disattiva visibilita' -----
            $new_attiva = $action === 'activate' ? 1 : 0;

            $stmt = $pdo->prepare("
                UPDATE `06_company`
                SET `attiva` = :a
                WHERE `id` = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':a'  => $new_attiva,
                ':id' => $company_id
            ]);

            $label = $action === 'activate' ? 'activated' : 'deactivated';

            $_SESSION['admin_success'] = "Company #{$company_id} {$label}.";

            UserTier::logAdminAction(
                $pdo, $admin_id, 'manage_company', null,
                '06_company #' . $company_id . ' ' . $label,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
        }
    }

    header('Location: /_admin/manage_companies.php');
    exit;
}

// -------------------------------------------------------------------
// Filtro attiva
// -------------------------------------------------------------------
$filter = in_array($_GET['filter'] ?? '', ['all', 'active', 'inactive'], true)
    ? $_GET['filter']
    : 'all';

$where = '';

switch ($filter) {
    case 'active':
        $where = 'WHERE c.attiva = 1';
        break;

    case 'inactive':
        $where = 'WHERE c.attiva = 0';
        break;

    default:
        $where = '';
        break;
}

// -------------------------------------------------------------------
// Recupero aziende
// -------------------------------------------------------------------
$sql = "
    SELECT
        c.id,
        c.ragione_sociale,
        c.citta,
        c.provincia,
        c.nazione,
        c.email,
        c.attiva,
        c.data_inserimento,
        c.logo,
        u.username,
        u.email AS user_email,

        (
            SELECT COUNT(*)
            FROM `06_company_gallery` g
            WHERE g.company_id = c.id
        ) AS gallery_count,

        (
            SELECT COUNT(*)
            FROM `06_company_products` p
            WHERE p.company_id = c.id
        ) AS products_count

    FROM `06_company` c

    LEFT JOIN `users` u
        ON u.id_user = c.user_id

    {$where}

    ORDER BY c.data_inserimento DESC
    LIMIT 500
";

$companies = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query("
    SELECT
        SUM(attiva = 1) AS active_count,
        SUM(attiva = 0) AS inactive_count,
        COUNT(*)        AS total
    FROM `06_company`
")->fetch(PDO::FETCH_ASSOC);

csrf_generate();

$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error'] ?? '';

unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Manage Companies';
$admin_active = 'companies';
require __DIR__ . '/admin_header.php';
?>

        </div>

        <!-- Riepilogo e filtri -->
        <div class="post_box">

            <h2>Companies overview</h2>

            <p>
                <strong>Active:</strong>
                <?php echo (int)$counts['active_count']; ?>

                &nbsp;|&nbsp;

                <strong>Inactive:</strong>
                <?php echo (int)$counts['inactive_count']; ?>

                &nbsp;|&nbsp;

                <strong>Total:</strong>
                <?php echo (int)$counts['total']; ?>
            </p>

            <div class="post_meta">

                <a href="?filter=all"<?php echo $filter === 'all' ? ' class="admin_nav_active"' : ''; ?>>
                    All (<?php echo (int)$counts['total']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=active"<?php echo $filter === 'active' ? ' class="admin_nav_active"' : ''; ?>>
                    Active (<?php echo (int)$counts['active_count']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=inactive"<?php echo $filter === 'inactive' ? ' class="admin_nav_active"' : ''; ?>>
                    Inactive (<?php echo (int)$counts['inactive_count']; ?>)
                </a>

                <div class="cleaner"></div>
            </div>
        </div>

        <!-- Tabella aziende -->
        <div class="post_box">

            <h2><?php echo ucfirst($filter); ?> companies</h2>

            <?php if (empty($companies)): ?>

                <p><em>No companies found.</em></p>

            <?php else: ?>

                <table border="1" cellpadding="6" cellspacing="0" class="admin_table">

                    <thead>
                        <tr class="admin_thead_row">
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Owner</th>
                            <th>Registered</th>
                            <th>Content</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($companies as $co): ?>

                        <?php $is_active = (int)$co['attiva'] === 1; ?>

                        <tr<?php echo !$is_active ? ' class="admin_row_inactive"' : ''; ?>>

                            <td><?php echo (int)$co['id']; ?></td>

                            <td>

                                <?php if (!empty($co['logo'])): ?>

                                    <a class="pirobox" href="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($co['ragione_sociale'] ?? 'Company logo', ENT_QUOTES, 'UTF-8'); ?>">
                                        <img
                                            src="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt=""
                                            class="admin_thumb"
                                        />
                                    </a>

                                <?php else: ?>

                                    <em class="admin_muted">&mdash;</em>

                                <?php endif; ?>

                            </td>

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars($co['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['email'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>

                                <?php echo htmlspecialchars($co['citta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                                <?php if (!empty($co['provincia'])): ?>
                                    (<?php echo htmlspecialchars($co['provincia'], ENT_QUOTES, 'UTF-8'); ?>)
                                <?php endif; ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['nazione'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>

                                <?php echo htmlspecialchars($co['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    date('Y-m-d', strtotime($co['data_inserimento'])),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>

                            <td>

                                <?php echo (int)$co['products_count']; ?> products

                                <br />

                                <small>
                                    <?php echo (int)$co['gallery_count']; ?> gallery images
                                </small>

                            </td>

                            <td>

                                <?php if ($is_active): ?>

                                    <strong class="admin_ok">active</strong>

                                <?php else: ?>

                                    <em class="admin_bad">inactive</em>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a href="/06_company/06_02_view_company.php?id=<?php echo (int)$co['id']; ?>" target="_blank">
                                    View
                                </a>

                                &nbsp;

                                <?php if ($is_active): ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        class="admin_inline_form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="deactivate"
                                        />

                                        <button type="submit" class="more">
                                            Deactivate
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        class="admin_inline_form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="activate"
                                        />

                                        <button type="submit" class="more">
                                            Activate
                                        </button>

                                    </form>

                                <?php endif; ?>

                                &nbsp;

                                <form
                                    method="post"
                                    action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                    class="admin_inline_form"
                                    data-confirm="Permanently delete &quot;<?php echo htmlspecialchars($co['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>&quot; (#<?php echo (int)$co['id']; ?>) with its logo, gallery, products and services? This cannot be undone."
                                >
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
                                    <input type="hidden" name="company_id" value="<?php echo (int)$co['id']; ?>" />
                                    <input type="hidden" name="action" value="delete" />
                                    <button type="submit" class="more">Delete</button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <div class="post_box">

            <h3>Notes</h3>

            <p>
                Inactive companies are hidden from the public Supplier Directory
                but their data is preserved.
                <br />
                Every activate/deactivate action is logged in
                <code>admin_audit_log</code>.
            </p>

        </div>

    
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `_admin/moderate_blog.php`

```php
<?php
// ============================================================
// /_admin/moderate_blog.php — Moderazione articoli del blog.
// Approve (-> published), Reject (-> rejected), Delete (rimuove anche le
// immagini). Audit via UserTier::logAdminAction (colonne corrette).
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/blog.class.php';

$admin_id = AdminAuth::requireAdminSession();
$blog = new BlogManager($pdo);

// ----- Azioni POST -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $blog_id = (int)($_POST['blog_id'] ?? 0);
    $action  = in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)
                 ? $_POST['action'] : '';

    if ($blog_id > 0 && $action !== '') {
        if ($action === 'delete') {
            $image = $blog->deleteArticle($blog_id);
            // Cleanup file con protezione path-traversal
            if ($image) {
                $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/blog/';
                $clean = static function (string $dir, string $filename): void {
                    $filename = basename($filename);
                    if ($filename === '') { return; }
                    $full = realpath($dir . $filename);
                    $root = realpath($dir);
                    if ($full === false || $root === false) { return; }
                    if (strpos($full, $root . DIRECTORY_SEPARATOR) !== 0) { return; }
                    if (is_file($full)) { @unlink($full); }
                };
                $clean($base . 'original/',  $image);
                $clean($base . 'thumbnail/', $image);
            }
            $_SESSION['admin_success'] = "Article #{$blog_id} deleted permanently.";
            UserTier::logAdminAction($pdo, $admin_id, 'delete_blog', null,
                'blog #' . $blog_id . ' deleted', $_SERVER['REMOTE_ADDR'] ?? '');
        } else {
            $new_status = $action === 'approve' ? 'published' : 'rejected';
            $blog->setStatus($blog_id, $new_status);
            $_SESSION['admin_success'] = "Article #{$blog_id} marked as {$new_status}.";
            UserTier::logAdminAction($pdo, $admin_id, 'moderate_blog', null,
                'blog #' . $blog_id . ' -> ' . $new_status, $_SERVER['REMOTE_ADDR'] ?? '');
        }
    }
    header('Location: /_admin/moderate_blog.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// ----- Filtro -----
$allowed_filters = ['all', 'pending', 'published', 'rejected'];
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'all';
$articles = $blog->listForModeration($filter);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';
$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Blog Moderation';
$admin_active = 'blog';
require __DIR__ . '/admin_header.php';
?>

    </div>

    <div class="post_box">
      <h2>Articles</h2>
      <p>
        <a href="?filter=all">All</a> &nbsp;|&nbsp;
        <a href="?filter=pending">Pending</a> &nbsp;|&nbsp;
        <a href="?filter=published">Published</a> &nbsp;|&nbsp;
        <a href="?filter=rejected">Rejected</a>
      </p>

      <?php if (empty($articles)): ?>
        <p>No articles<?php echo $filter !== 'all' ? ' with status "' . htmlspecialchars($filter) . '"' : ''; ?>.</p>
      <?php else: ?>
      <table border="0" cellpadding="6" cellspacing="0" class="admin_table">
        <thead>
        <tr class="admin_thead_row">
          <th align="left">#</th>
          <th align="left">Title</th>
          <th align="left">Author</th>
          <th align="left">Status</th>
          <th align="left">Date</th>
          <th align="left">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
        <tr>
          <td><?php echo (int)$a['id']; ?></td>
          <td><a href="/blog_post.php?id=<?php echo (int)$a['id']; ?>" target="_blank"><?php echo htmlspecialchars($a['title']); ?></a></td>
          <td><?php echo htmlspecialchars((string)($a['username'] ?? '—')); ?></td>
          <td><strong><?php echo htmlspecialchars($a['status']); ?></strong></td>
          <td><?php echo htmlspecialchars(date('j M Y', strtotime((string)$a['created_at']))); ?></td>
          <td>
            <?php if ($a['status'] !== 'published'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" class="admin_inline_form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="approve" />
              <button type="submit" class="more">Publish</button>
            </form>
            <?php endif; ?>
            <?php if ($a['status'] !== 'rejected'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" data-confirm="Reject article #<?php echo (int)$a['id']; ?>?">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  class="admin_inline_form" data-confirm="Permanently delete article #<?php echo (int)$a['id']; ?> and its image? This cannot be undone.">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="delete" />
              <button type="submit" class="more">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  
<?php require __DIR__ . '/admin_footer.php'; ?>

```


## `scripts/expire_ads.php`

```php
<?php
// ============================================================
// scripts/expire_ads.php
// Eliminazione automatica degli annunci scaduti dal database.
//
// COMPORTAMENTO:
//   Cancella SOLO i record dal database quando l'inserzione ha
//   raggiunto la propria scadenza. NON tocca i file fisici su disco.
//   La pulizia dei file orfani è delegata a cleanup_unused_uploads.php.
//
// SCADENZE:
//   02_free_ads  → 45 giorni da created_at (colonna expires_at)
//   03_ads       → 60 giorni da created_at (colonna expires_at)
//
// UTILIZZO (cron job giornaliero):
//   0 3 * * * php /home/<user>/htdocs/scripts/expire_ads.php >> /var/log/templatemo_expire.log 2>&1
//
// Può essere eseguito anche manualmente da CLI:
//   php scripts/expire_ads.php
//   php scripts/expire_ads.php --dry-run   ← solo simulazione, nessuna cancellazione
//
// SICUREZZA:
//   - Questo script NON deve essere raggiungibile via HTTP.
//   - Proteggere con .htaccess (già presente in scripts/) o
//     spostandolo fuori dalla webroot.
// ============================================================

// ---- Percorsi + bootstrap (carica .env, costanti, PDO) ----
// Il bootstrap va caricato PRIMA del controllo accessi perche' il token
// cron e' definito in .env (CRON_TOKEN) e letto via getenv().
$webroot = dirname(__DIR__);
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';

// ---- Controllo accessi ----
// Consentito da: (a) riga di comando (cron locale) oppure (b) HTTP con token
// valido, per permettere a servizi esterni come cron-job.org di richiamarlo.
// Il token va impostato in .env come CRON_TOKEN e inviato dal cron esterno
// nell'header 'X-Cron-Token' (consigliato) o come parametro ?token=.
$is_cli = (PHP_SAPI === 'cli');
if (!$is_cli) {
    $expected = (string) getenv('CRON_TOKEN');
    $provided = (string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? $_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        exit('403 Forbidden' . PHP_EOL);
    }
    header('Content-Type: text/plain');
}

// ---- Modalità dry-run (CLI: --dry-run | HTTP: ?dry-run=1) ----
$dry_run = (in_array('--dry-run', $argv ?? [], true)) || isset($_GET['dry-run']);

```


## `scripts/.htaccess`

```apache
# Blocca l'accesso HTTP diretto a tutti i file nella cartella scripts/.
# Questi script sono eseguibili via CLI (cron locale).
# ECCEZIONE: expire_ads.php e' raggiungibile via HTTP per il cron esterno
# (cron-job.org), ma e' protetto da token CRON_TOKEN (vedi .env) all'interno
# dello script stesso. Senza token valido risponde 403.

Require all denied

<Files "expire_ads.php">
    Require all granted
</Files>

```


## `config/env`

```bash
# ============================================================
# Allonwheel — Variabili d'ambiente (TEMPLATE)
#
# ⚠️  ATTENZIONE SICUREZZA:
#   - Questo è un TEMPLATE. NON inserire qui credenziali reali.
#   - Il file con i valori reali va salvato come `.env` UNA directory
#     SOPRA la webroot (vedi config/bootstrap.php), MAI dentro la webroot.
#   - Le credenziali reali precedentemente presenti qui devono essere
#     considerate COMPROMESSE e vanno RUOTATE (DB e mail).
# ============================================================

# --- Database ---
DB_HOST=__SET_ON_SERVER__
DB_NAME=__SET_ON_SERVER__
DB_USER=__SET_ON_SERVER__
DB_PASSWORD=__SET_ON_SERVER__

# --- Applicazione ---
APP_ENV=production
APP_URL=https://www.allonwheel.com

# --- Email ---
MAIL_HOST=__SET_ON_SERVER__
MAIL_PORT=587
MAIL_USERNAME=__SET_ON_SERVER__
MAIL_PASSWORD=__SET_ON_SERVER__
MAIL_FROM_NAME=All on Wheel
MAIL_ENCRYPTION=tls

# --- Upload ---
UPLOAD_MAX_SIZE=10485760

# --- Cron esterno (cron-job.org) ---
# Token segreto per autorizzare l'esecuzione via HTTP di scripts/expire_ads.php.
# Genera un valore lungo e casuale (es. 48+ caratteri) e impostalo qui nel .env reale.
# cron-job.org deve inviarlo nell'header 'X-Cron-Token' (consigliato) o come ?token=.
CRON_TOKEN=

```


## `allonwheel_style.css`

```css
body {
	margin: 0;
	padding: 0;
	color: #636363;
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 13px;
	line-height: 1.5em; 
	background-color: #fff;
	background-position: top;
	background-repeat: repeat-x;
}

a, a:link, a:visited { 
	color: #ff0000; 
	font-weight: normal; 
}

a:hover { 
	text-decoration: underline; 
}

a.more,
button.more { 
	display: block; 
	margin-top: 0px; 
	width: 110px; 
	height: 26px; 
	line-height: 26px; 
	text-align: left; 
	padding-left: 10px; 
	text-decoration: none; 
	background: url(images/more.png) center right;
	color: #fff;
	border: 0;
	cursor: pointer;
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 13px;
}

a.back,
button.back
{
	display: block;
	margin-top: 0px;
	width: 110px;
	height: 26px;
	line-height: 26px;
	text-align: right;
	padding-right: 10px;
	text-decoration: none;
	background: url(images/back.png) center right;
	color: #fff;
	border: 0;
	cursor: pointer;
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 13px;
}

p { 
	margin: 0 0 10px 0; 
	padding: 0; 
}

img { 
	border: none;
}

blockquote { 
	border: 1px solid #ccc; 
	border-left: 5px solid #000; 
	padding: 19px;
	margin: 20px 0 0 0;
}

cite { 
	font-weight: bold; 
	color:#f00; 
}

cite a, cite a:link, cite a:visited {
	color:#f00; 
	text-decoration: none;
}

cite span {
	color: #636363;
}

em { color: #000; }

h1, h2, h3, h4, h5, h6 { color: #000; font-weight: normal; font-family: Georgia, "Times New Roman", Times, serif }
h1 { font-size: 34px; margin: 0 0 30px; padding: 5px 0 }
h2 { font-size: 24px; margin: 0 0 20px; padding: 5px 0; }
h3 { font-size: 18px; margin: 0 0 10px; padding: 0; }
h4 { font-size: 14px; margin: 0 0 15px; padding: 0; }
h5 { font-size: 13px; margin: 0 0 10px; padding: 0; }
h6 { font-size: 12px; margin: 0 0 5px; padding: 0; }

.cleaner { clear: both }
.h10 { height: 10px }
.h20 { height: 20px }
.h30 { height: 30px }
.h40 { height: 40px }
.h50 { height: 50px }
.h60 { height: 60px }

.float_l { float: left }
.float_r { float: right }

.image_frame { 
	margin-bottom: 10px; 
	padding: 5px; 
	border: 1px solid #ccc; 
}

.image_fl { 
	float: left; 
	margin: 3px 30px 0 0; 
}

.image_fr { 
	float: right; 
	margin: 3px 0 0 30px; 
}

.templatemo_list { 
	margin: 10px 0 10px 0; 
	padding: 0; 
	list-style: none; 
}

.templatemo_list li {
	color: #636363;
	margin: 0 0 5px 0;
	padding-top: 0;
	padding-right: 0;
	padding-bottom: 0;
	background: url("images/punto_sidebar.png") no-repeat scroll 0 7px;
	padding-left: 20px;
}

.templatemo_list li a { 
	color: #636363; 
	font-weight: normal; 
}

.templatemo_list li a:hover { color: #000 }

#templatemo_wrapper {
	width: 960px;
	margin: 0 auto;
	padding: 30px 10px;
}

#templatemo_header {
	width: 900px;
	height: 30px;
	padding: 30px 40px 30px 20px;
	background: url(images/templatemo_header.jpg) no-repeat;
}

#site_title { 
	float: left; 
}

#site_title h1 { 
	margin: 0; 
	padding: 0; 
}

#site_title h1 a { 
	display: block; 
	width: 230px; 
	height: 27px; 
	color: #000; 
	text-indent: -10000px; 
	/* background: url(images/factory_name.png) no-repeat top left; */
}

#templatemo_menu { float: right; }

#templatemo_slider { clear: both; margin: 10px 0 }

#templatemo_main {
	clear: both;
}

#content_top { 	
	padding: 40px 0 5px; 
	margin-bottom: 40px; 
	border-bottom: 4px solid #000; 
}

#page_title { 
	float: left; 
	font-size: 40px; 
	padding-bottom: 14px; 
	font-family: Georgia, "Times New Roman", Times, serif; 
	color: #000; 
} 

#search_box { 
	float: right; 
	width: 200px; 
	height: 30px; 
	background: url(images/search.png) no-repeat; 
}

#search_box form { 
	clear: both; 
	width: 200px; 
	height: 28px; 
	padding: 0; 
	margin: 0; 
} 

#searchfield { 
	float: left; 
	display: block; 
	height: 20px;
	width: 220px; 
	padding: 4px; 
	font-size: 12px; 
	color: #fff; 
	background: none; 
	border: none; 
} 

#searchbutton { 
	float: right; 
	display: block; 
	height: 28px; 
	width: 40px; 
	padding: 0; 
	margin: 0; 
	cursor: pointer; 
	background: none; 
	border: none; 
}

#templatemo_content {
	float: left;
	width: 650px;
}

#templatemo_sidebar {
	float: right;
	width: 280px
}

.col_3 { 
	float: left; 
	width: 280px; 
	margin-right: 59px; 
}

.col_4 { 
	float: left; 
	width: 225px; 
	margin-right: 20px; 
}

.rmc { margin-right: 0 }

.gallery_box { 
	clear: both; 
	margin-bottom: 40px; 
}

.gallery_box h2 { 
	padding-bottom: 8px; 
	margin-bottom: 10px; 
	border-bottom: 2px solid #000; 
}

.gallery { 
	margin: 0; 
	padding: 0; 
	list-style: none; 
}

.gallery li { 
	margin: 0; 
	padding: 0; 
	display: block; 
	float: left; 
	padding: 5px; 
	margin: 0 8px 8px 0; 
	width: 220px; 
	height: 150px; 
	border: 1px solid #ccc; 
}

.gallery li a img { 
	display: block; 
	float: left; 
	width: 220px; 
	height: 150px; 
	margin: 0 2px 2px 0; 
}

.post_box {
	clear: both;
	margin-bottom: 10px;
	padding-bottom: 10px;
	background: url(images/templatemo_divider.png) repeat-x bottom;
}

.post_box img { 
	float: left; 
	margin-right: 40px; 
}

.post_box h2 { 
	font-size: 30px; 
	padding-bottom: 10px; 
	border-bottom: 2px solid #000; 
}

.post_meta {
	clear: both;
	margin-top: 30px;
	height: 30px;
	padding: 0 10px;
	background: url(images/templatemo_footer_bottom.jpg) repeat-x center;
	line-height: 30px;
	margin-bottom: 10px;
}

.post_meta a.more,
.post_meta button.more {
	margin-right: 0;
	margin-left: 0;
	margin-bottom: 0
}

.post_meta a.back,
.post_meta button.back {
	margin-right: 0;
	margin-left: 0;
	margin-bottom: 0
}

#comment_section {
	clear: both;
	margin-bottom: 60px;
	width: 618px;
}

.first_level {
	margin: 0; padding: 0;
}

.comments {
	list-style: none; 
}

.comments li { 
	margin-bottom: 10px; 
	list-style:none; 
}

.comments li .commentbox1 { 
	background: #ccc; 
	border: 1px solid #999; 
}

.comments li .commentbox2 { 
	background: #999; 
	border: 1px solid #666; 
}

.comments li .comment_box { 
	clear: both; 
	width:100%; 
	padding: 15px; 
}

.comment_box .gravatar { 
	float: left; 
	width: 50px; 
	margin-right: 15px; 
	background: #000; 
}

.comment_box .gravatar img { 
	margin: 0; 
	width: 50px; 
	height: 50px; 
}

.comment_box .comment_text { margin: 0 0 0 65px; }

.comment_box .comment_text p { 
	margin: 0; 
	color: #000; 
}

.comment_text .comment_author { 
	font-size: 13px; 
	font-weight: bold; 
	color: #000; 
	margin-bottom: 10px; 
}

.comment_text .date { 
	font-size: 11px; 
	font-weight: normal; 
	color: #000; 
	padding-left: 10px; 
}

.comment_text .time { 
	font-size: 11px; 
	font-weight: normal; 
	color: #000; 
	padding-left: 10px; 
}

.comment_text .reply a { 
	display: block; 
	clear: both; 
	float: right; 
	color: #000; 
	font-weight: bold; 
} 

#comment_form {
	clear: both;
}

#comment_form h3 {
	font-size: 20px;
	border-bottom: 2px solid #000;
	margin-bottom: 15px;
	padding-bottom: 10px;
}

#comment_form form {
	padding: 20px;
	background: #ccc; border: 1px solid #999;
}

#comment_form textarea {
	color: #fff;
	background:#666 none repeat fixed 0 0;
	border: 1px solid #333;
	display:block;
	height:150px;
	padding:5px;
	width: 360px;
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 12px;
	margin-top: 5px;
}

#comment_form .form_row {
	width: 100%;
	margin-bottom: 15px;
}

#comment_form form input {
	color: #fff;
	padding: 5px;
	width: 200px;
	background: #666 none repeat fixed 0 0;
	border: 1px solid #333;
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 12px;
	margin-top: 5px;
}

#comment_form .submit_btn {
	width: 80px;
	padding: 5px 20px;
	background: #0c0c0c;
	border: 1px solid #000
}

.templatemo_paging { 
	margin: 0 0 20px; 
	padding: 0; 
}

.templatemo_paging ul { 
	margin: 0; 
	padding: 0; 
	list-style: none; 
}

.templatemo_paging ul li { 
	margin: 0; 
	padding: 0; 
	display: inline; 
}

.templatemo_paging ul li a { 
	float: left; 
	display: block; 
	color: #666; 
	text-decoration: none; 
	margin-right: 5px; 
	padding: 5px 10px; 
	background-color: #ccc; 
	border: 1px solid #999; 
}

.templatemo_paging ul li a:hover { 
	background: #f00; 
	border: 1px solid #333; 
	color: #fff; 
}

#contact_form {
	clear: both;
	padding: 0;
	margin-top: 20px;
}

#contact_form form { 
	margin: 0px; 
	padding: 0px; 
}

#contact_form form .input_field { 
	width: 280px; 
	padding: 5px; 
	color: #222; 
	border: 1px solid #ccc; 
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 12px;
	margin-top: 5px;
}

#contact_form form label { 
	display: block; 
	width: 200px; 
	margin-right: 10px; 
	font-size: 13px; 
}

#contact_form form textarea { 
	width: 638px; 
	height: 200px; 
	padding: 5px; 
	border: 1px solid #ccc; 
	font-family: Tahoma, Geneva, sans-serif;
	font-size: 12px;
	margin-top: 5px;
}

#contact_form form .submit_btn { 
	display: block; 
	padding: 10px 20px; 
	text-align: center; 
	text-decoration: none; 
	font-weight: bold; 
	background: #f00; 
	color: #fff; 
	border: none; 
	font-size:11px; 
	cursor: pointer; 
}

.sb_box { 
	margin-bottom: 30px; 
}

.sb_box h3 { 
	padding-bottom: 4px; 
	border-bottom: 2px solid #000; 
}

.sb_list { 
	padding: 0; 
	margin: 0; 
}

.sb_list li { 
	padding: 0 0 3px 0; 
	margin: 0 0 1px 5px; /* Nota: aggiunto "px" mancante all'originale */
	list-style: none; 
	border-bottom: 1px solid #ccc; 
}

.sb_list li a {
	color: #636363;
	text-decoration: none;
	padding-left: 0px;
	background: url(images/templatemo_list.png) no-repeat scroll 0 7px;
}

#templatemo_bottom {
	clear: both;
	margin: 10px 0 10px;
	padding: 10px 0;
	font-size: 11px;
	background: #ededed url(images/templatemo_bottom.jpg) top repeat-x;
}

.col_f { 
	padding-left: 15px; 
	width: 210px; 
}

.col_l { 
	padding-right: 15px; 
	width: 210px; 
}

.footer_link { 
	margin: 0; 
	padding: 0; 
}

.footer_link li { 
	margin-bottom: 5px; 
	padding-bottom: 3px; 
	border-bottom: 1px solid #666; 
	list-style: none; 
}

.footer_link li a { 
	color: #000; 
	text-decoration: none; 
}

.footer_link li .social { 
	padding-left: 30px; 
}

.footer_link li .facebook { 
	background: url(images/facebook.png) left center no-repeat; 
}

.footer_link li .linkedin {
	background: url(images/linkedin.png) left center no-repeat; 
}

.footer_link li .instagram { 
	background: url(images/instagram.png) left center no-repeat; 
}

.footer_link li .youtube { 
	background: url(images/youtube.png) left center no-repeat; 
}

.footer_link li .vimeo { 
	background: url(images/vimeo.png) left center no-repeat; 
}

#templatemo_footer {
	clear: both;
	text-align: center;
	line-height: 40px;
	width: 960px;
	height: 50px;
	background: url(images/templatemo_footer_bottom.jpg) repeat-x
}

/* --- Classi Modulo / Form e Griglie (Ottimizzate e Unificate) --- */

.step-bar { display:flex; gap:0; margin-bottom:20px; }
.step-bar .step { flex:1; text-align:center; padding:8px 4px; font-size:12px; background:#ddd; color:#666; border-right:2px solid #fff; }
.step-bar .step.active { background:#1D275A; color:#fff; font-weight:bold; }
.step-bar .step.done { background:#4a7; color:#fff; }

.error-msg { color:red; font-size:13px; margin-bottom:10px; }
.section-title { font-size:15px; font-weight:bold; color:#1D275A; border-bottom:2px solid #1D275A; padding-bottom:4px; margin:16px 0 10px; }

.cat-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:6px 16px; margin-bottom:16px; }
.cat-grid label { font-size:13px; cursor:pointer; }
.cat-grid label input { margin-right:5px; }

.select-all-row { margin-bottom:10px; font-size:12px; }

.pref-grid { display:flex; gap:24px; flex-wrap:wrap; margin-bottom:16px; }
.pref-grid label { font-size:14px; cursor:pointer; }
.pref-grid label input { margin-right:6px; }

.required-note { color:red; font-size:12px; }

/* ============================================================ */
/* Admin panel "Records" - classi condivise (nessuno stile inline) */
/* Aggiunte al foglio di stile esistente; nessun file CSS nuovo.   */
/* ============================================================ */
#templatemo_content.admin_full { width:100%; }
.admin_table { width:100%; border-collapse:collapse; }
.admin_table th { background:#1D275A; color:#fff; }
.admin_row_pending td { background:#FFF8DC; }
.admin_footer_note { text-align:center; padding:10px 0; }
.admin_form td { vertical-align:top; }
.aw_in_s { width:120px; }
.aw_in_m { width:200px; }
.aw_in_l { width:340px; max-width:100%; }
.admin_textarea { width:340px; max-width:100%; }
.admin_tag { display:inline-block; width:170px; }
.admin_price { width:140px; }
.admin_fieldset { border:1px solid #c9d0e8; padding:10px 12px; margin:0; }
.admin_fieldset legend { font-weight:bold; color:#1D275A; padding:0 6px; }
.admin_inline_form { display:inline; margin:0; }
.admin_table input.more, .admin_table .more { margin:2px; }
.admin_thead_row td, .admin_thead_row th { background:#1D275A; color:#fff; }
.admin_row_inactive td { background:#FDE8E8; }
.admin_row_pending td { background:#FFF8DC; }
.admin_row_rejected td { background:#FDE8E8; }
.admin_ok { color:#1D275A; font-weight:bold; }
.admin_bad { color:#c00; font-style:italic; }
.admin_muted { color:#999; }
.admin_thumb { max-width:60px; max-height:45px; }
.admin_nav_active { font-weight:bold; }

```
