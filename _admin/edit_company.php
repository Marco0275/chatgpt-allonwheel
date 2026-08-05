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
// Cataloghi DB-driven (vehicle_types / special_types). Guardia legacy: si
// includono i product_key gia' salvati in DB cosi' nessuna azienda perde
// associazioni storiche non piu' in tassonomia.
try { $aow_keep_reg = $pdo->query('SELECT DISTINCT product_key FROM `06_company_products`')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) { $aow_keep_reg = []; }
try { $aow_keep_spe = $pdo->query('SELECT DISTINCT product_key FROM `06_company_products_special`')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) { $aow_keep_spe = []; }
$catalog_products = CompanyManager::productsRoad($pdo, $aow_keep_reg);          // [key => label]
$catalog_services = CompanyManager::$services;          // [key => label]
$catalog_special  = CompanyManager::productsSpecial($pdo, $aow_keep_spe);  // [key => label]
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
        $founding    = isset($_POST['founding_partner']) ? 1 : 0; // M2: badge Founding partner
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
            $params[':founding_partner'] = $founding;

            try {
                $pdo->beginTransaction();

                if ($action === 'add') {
                    $cols = array_keys($text_fields);
                    $col_list = '`user_id`, `' . implode('`, `', $cols) . '`, `descrizione`, `logo`, `attiva`, `founding_partner`';
                    $val_list = ':user_id, :' . implode(', :', $cols) . ', :descrizione, :logo, :attiva, :founding_partner';
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
                    $sets[] = '`founding_partner`=:founding_partner';
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
          <tr>
            <td><label>Founding partner:</label></td>
            <td><label class="admin_tag"><input type="checkbox" name="founding_partner" value="1"<?php echo ($edit_item && (int)($edit_item['founding_partner'] ?? 0) === 1) ? ' checked="checked"' : ''; ?> /> &#9733; Founding partner badge (launch program)</label></td>
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
            <th width="5%" style="text-align: center">ID</th><th style="text-align: center">Company name</th><th style="text-align: center">VAT</th><th style="text-align: center">City</th><th style="text-align: center">Owner</th><th style="text-align: center">Active</th><th width="14%" style="text-align: center">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $row): ?>
          <tr>
            <td align="center" style="text-align: center"><?php echo (int)$row['id']; ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars($row['ragione_sociale']); ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars($row['partita_iva']); ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars($row['citta']); ?></td>
            <td align="center" style="text-align: center"><?php echo (int)$row['user_id']; ?></td>
            <td align="center" style="text-align: center"><?php echo ((int)$row['attiva'] === 1) ? 'Yes' : 'No'; ?></td>
            <td align="center" style="text-align: center">
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
