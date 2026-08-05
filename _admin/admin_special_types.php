<?php
// ============================================================
// _admin/admin_special_types.php
// Gestione della lista SPECIAL (veicoli speciali e shelter).
//
// 24 lug 2026. La tassonomia del sito e' divisa cosi':
//   ROAD    -> vehicle_types  : la lista estratta dal codice della strada
//              italiano. Elenco chiuso di riferimento, non si cura a mano.
//   SPECIAL -> special_types  : QUESTA lista, decisa dall'amministratore.
//   SHELTER -> ancora special_types: uno shelter e' un allestimento speciale
//              costruito su container invece che su un veicolo, quindi
//              condivide le stesse tipologie.
//
// Da qui si puo':
//   - aggiungere una tipologia speciale scritta a mano;
//   - DUPLICARE una o piu' voci da vehicle_types (utile quando una tipologia
//     stradale esiste anche come allestimento speciale: es. "Ambulanze");
//   - rinominare, riordinare, eliminare.
//
// Solo classi CSS esistenti (dir. 8), CSRF su ogni azione, nessuno stile
// inline.
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$admin_id = AdminAuth::requireAdminSession();

$msg = '';
$msg_ok = false;

// Slug: minuscolo, senza spazi. Stessa convenzione di vehicle_types, perche'
// lo slug e' anche la chiave con cui si agganciano i prodotti dei fornitori.
$aow_slugify = static function (string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim((string)$s, '_');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    try {
        // ---- Duplica voci scelte da vehicle_types -------------------
        if ($action === 'duplicate') {
            $slugs = (array)($_POST['dup'] ?? []);
            $done = 0; $skip = 0;
            $ins = $pdo->prepare(
                'INSERT IGNORE INTO `special_types` (name, slug, sort_order, macro_category, source_slug)
                 SELECT v.name, v.slug, v.sort_order, :sp, v.slug
                   FROM `vehicle_types` v WHERE v.slug = :s'
            );
            foreach ($slugs as $s) {
                $s = trim((string)$s);
                if ($s === '') { continue; }
                $ins->execute([':sp' => VehicleTaxonomy::MACRO_SPECIAL, ':s' => $s]);
                if ($ins->rowCount() > 0) { $done++; } else { $skip++; }
            }
            $msg = "Duplicated {$done} type(s)." . ($skip ? " {$skip} already present, skipped." : '');
            $msg_ok = ($done > 0);
        }

        // ---- Aggiunta manuale --------------------------------------
        elseif ($action === 'add') {
            $name = trim((string)($_POST['name'] ?? ''));
            $slug = $aow_slugify((string)($_POST['slug'] ?? '')) ?: $aow_slugify($name);
            $sort = (int)($_POST['sort_order'] ?? 0);
            if ($name === '' || $slug === '') {
                $msg = 'Name is required.';
            } else {
                $st = $pdo->prepare(
                    'INSERT IGNORE INTO `special_types` (name, slug, sort_order, macro_category, source_slug)
                     VALUES (:n, :s, :o, :sp, NULL)'
                );
                $st->execute([':n' => $name, ':s' => $slug, ':o' => $sort, ':sp' => VehicleTaxonomy::MACRO_SPECIAL]);
                if ($st->rowCount() > 0) { $msg = 'Type added.'; $msg_ok = true; }
                else { $msg = 'A type with that key already exists.'; }
            }
        }

        // ---- Rinomina / riordina -----------------------------------
        elseif ($action === 'update') {
            $id   = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $sort = (int)($_POST['sort_order'] ?? 0);
            if ($id > 0 && $name !== '') {
                $st = $pdo->prepare('UPDATE `special_types` SET name = :n, sort_order = :o WHERE id = :id');
                $st->execute([':n' => $name, ':o' => $sort, ':id' => $id]);
                $msg = 'Type updated.'; $msg_ok = true;
            } else {
                $msg = 'Nothing to update.';
            }
        }

        // ---- Eliminazione ------------------------------------------
        elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            // Non si elimina una tipologia ancora usata da un annuncio: si
            // lascerebbe l'annuncio con una classificazione inesistente.
            $chk = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM `02_free_ads` a JOIN `special_types` t ON t.slug = a.vehicle_type WHERE t.id = :i1)
                      + (SELECT COUNT(*) FROM `03_ads` b JOIN `special_types` t2 ON t2.slug = b.vehicle_type WHERE t2.id = :i2)'
            );
            $chk->execute([':i1' => $id, ':i2' => $id]);
            $used = (int)$chk->fetchColumn();
            if ($used > 0) {
                $msg = "Cannot delete: {$used} listing(s) still use this type.";
            } elseif ($id > 0) {
                $pdo->prepare('DELETE FROM `special_types` WHERE id = :id')->execute([':id' => $id]);
                $msg = 'Type deleted.'; $msg_ok = true;
            }
        }
    } catch (Throwable $e) {
        error_log('[Allonwheel] admin_special_types: ' . $e->getMessage());
        $msg = 'Operation failed. Check that the special_types table exists.';
    }
}

// Dati per la pagina
$specials = [];
$roads    = [];
try {
    $specials = $pdo->query('SELECT id, name, slug, sort_order, source_slug FROM `special_types` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
    // Le road ancora NON duplicate: quelle gia' presenti non si ripropongono.
    $roads = $pdo->query(
        'SELECT v.slug, v.name FROM `vehicle_types` v
          WHERE NOT EXISTS (SELECT 1 FROM `special_types` s WHERE s.slug = v.slug)
          ORDER BY v.sort_order, v.name'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] admin_special_types load: ' . $e->getMessage());
    $msg = $msg ?: 'Could not read the tables. Apply 2026-07-24c_special_types.sql first.';
}

csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
require __DIR__ . '/admin_header.php';
?>
<h2>Special types</h2>

<?php if ($msg !== ''): ?>
<p class="<?php echo $msg_ok ? 'admin_ok' : 'admin_bad'; ?>"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p class="admin_muted">
  This is the list of <strong>special</strong> types. It is used both for
  special vehicles and for shelters, because a shelter is the same kind of
  build made on a container instead of a vehicle.
  Road types come from a different list (the Italian highway code one) and are
  managed under <a href="admin_vehicle_types.php">Vehicle types</a>.
</p>

<h3>Current special types</h3>
<table class="admin_table" border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr class="admin_thead_row">
      <th>Name</th><th>Key</th><th>Order</th><th>Duplicated from</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($specials as $s): ?>
    <tr>
      <form method="post" action="admin_special_types.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>" />
        <td><input type="text" name="name" class="input_field" value="<?php echo htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><code><?php echo htmlspecialchars((string)$s['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
        <td><input type="number" name="sort_order" class="input_field" value="<?php echo (int)$s['sort_order']; ?>" /></td>
        <td><?php echo $s['source_slug'] ? htmlspecialchars((string)$s['source_slug'], ENT_QUOTES, 'UTF-8') : '&mdash;'; ?></td>
        <td><input type="submit" class="more" value="Save" /></td>
      </form>
    </tr>
    <tr>
      <td colspan="5">
        <form method="post" action="admin_special_types.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>" />
          <input type="submit" class="more" value="Delete this type" />
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h3>Add a special type</h3>
<form method="post" action="admin_special_types.php">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
  <input type="hidden" name="action" value="add" />
  <p><label>Name: <input type="text" name="name" class="input_field" required /></label></p>
  <p><label>Key (optional, derived from the name if empty):
     <input type="text" name="slug" class="input_field" /></label></p>
  <p><label>Order: <input type="number" name="sort_order" class="input_field" value="0" /></label></p>
  <p><input type="submit" class="more" value="Add type" /></p>
</form>

<h3>Copy from the road list</h3>
<p class="admin_muted">
  Tick the road types that should also exist as a special build, then copy
  them here. Types already present are not shown.
</p>
<?php if (empty($roads)): ?>
<p class="admin_muted">Every road type has already been copied.</p>
<?php else: ?>
<form method="post" action="admin_special_types.php">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
  <input type="hidden" name="action" value="duplicate" />
  <table class="admin_table" border="1" cellpadding="6" cellspacing="0">
    <thead><tr class="admin_thead_row"><th>Copy</th><th>Name</th><th>Key</th></tr></thead>
    <tbody>
    <?php foreach ($roads as $r): ?>
      <tr>
        <td><input type="checkbox" name="dup[]" value="<?php echo htmlspecialchars((string)$r['slug'], ENT_QUOTES, 'UTF-8'); ?>" /></td>
        <td><?php echo htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><code><?php echo htmlspecialchars((string)$r['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
	<div class="cleaner h10"></div>
  <p><input type="submit" class="more" value="Copy selected into special types" /></p>
  <div class="cleaner h10"></div>
</form>
<?php endif; ?>

<p class="admin_footer_note">
  The <code>on_demand</code> type is the escape hatch shown to users who
  cannot find their case in either list: leave it in place.
</p>
<?php require __DIR__ . '/admin_footer.php'; ?>
