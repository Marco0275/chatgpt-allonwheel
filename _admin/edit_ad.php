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
                         item_kind, macro_category, vehicle_type, image_original, image_thumbnail, expires_at)
                        VALUES
                        (:id_user, :author, :email, :phone, :title, :subtitle, :list_price, :status, :type, :conditions, :description,
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
                        item_kind=:item_kind, macro_category=:macro_category,
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
            <th width="5%" style="text-align: center">ID</th><th style="text-align: center">Title</th><th style="text-align: center">Owner</th><th style="text-align: center">Status</th><th style="text-align: center">Price</th><th style="text-align: center">Type</th><th width="14%" style="text-align: center">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $row): ?>
          <tr>
            <td align="center" style="text-align: center"><?php echo (int)$row['id_ads']; ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars($row['title']); ?></td>
            <td align="center" style="text-align: center"><?php echo (int)$row['id_user']; ?></td>
            <td align="center" style="text-align: center"><?php echo htmlspecialchars($row['status']); ?></td>
            <td align="right" style="text-align: center"><?php echo number_format((float)$row['list_price'], 2); ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars(VehicleTaxonomy::label((string)$row['vehicle_type'], $pdo)); ?></td>
            <td align="center" style="text-align: center">
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
