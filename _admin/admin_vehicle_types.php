<?php
// ============================================================
// /_admin/admin_vehicle_types.php
// CRUD della tabella vehicle_types, che nella nuova tassonomia contiene SOLO
// la lista Road (codice della strada). Le tipologie Special/Shelter vivono in
// special_types e si gestiscono da admin_special_types.php.
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

// ---- Gestione POST (add / edit / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $order = (int)($_POST['sort_order'] ?? 0);
        // Nuova tassonomia: vehicle_types e' road-only (gli special stanno in
        // special_types, gestiti da admin_special_types.php). La macro qui e'
        // quindi sempre 'road'.
        $macro = 'road';
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

// ---- Lista Road (vehicle_types e' road-only) ----
$rows_road = $pdo->query("SELECT * FROM vehicle_types WHERE macro_category='road' ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);

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
            <td><strong>Road</strong> &mdash; <small>Special &amp; shelter types are managed in <a href="admin_special_types.php">Special types</a>.</small></td>
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
              <th width="40" style="text-align: center">ID</th><th style="text-align: center">Name</th><th style="text-align: center">Slug / Key</th><th width="80" style="text-align: center">Order</th><th width="160" style="text-align: center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($list as $row): ?>
            <tr>
              <td align="center" style="text-align: center"><?php echo (int)$row['id']; ?></td>
              <td style="text-align: center"><?php echo htmlspecialchars($row['name']); ?></td>
              <td style="text-align: center"><code><?php echo htmlspecialchars($row['slug']); ?></code></td>
              <td align="center" style="text-align: center"><?php echo (int)$row['sort_order']; ?></td>
              <td align="center" style="text-align: center">
                <a class="more" href="admin_vehicle_types.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
                <form method="post" action="admin_vehicle_types.php" class="admin_inline_form" >
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
      <h2>Special &amp; shelter types</h2>
      <p>Special and shelter types now live in their own table.
         <a class="more" href="admin_special_types.php">Manage special types</a></p>
    </div>

<?php require __DIR__ . '/admin_footer.php'; ?>
