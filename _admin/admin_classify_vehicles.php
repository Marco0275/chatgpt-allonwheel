<?php
// ============================================================
// /_admin/admin_classify_vehicles.php
// Classificazione macro-categoria dei vehicle_types: Special vs Road.
// Riepilogo a due colonne (Special a sinistra, Road a destra) + form con
// checkbox: le voci SELEZIONATE diventano "special", le altre "road".
// Aggiorna vehicle_types.macro_category in blocco (transazione).
// Solo classi del foglio di stile esistente (dir. 8). CSRF + AdminAuth.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

// ============================================================
// SUPERATA dalla nuova tassonomia (24 lug 2026).
//
// Questa pagina serviva a marcare road/special le righe di UNA sola tabella
// (vehicle_types.macro_category). Da oggi le liste sono due e separate:
//   Road    -> vehicle_types  (lista del codice della strada, non si cura
//                              a mano: e' un elenco di riferimento)
//   Special -> special_types  (lista curata dall'admin, con la possibilita'
//                              di duplicarci voci prese dai road)
// Non esiste piu' niente da "classificare": una tipologia e' speciale
// perche' STA in special_types, non perche' ha una colonna marcata.
//
// Il file NON viene cancellato (dir. 19: niente rimozioni silenziose) e non
// viene lasciato attivo, perche' scrivere su macro_category adesso non
// avrebbe alcun effetto sulle liste mostrate: manderebbe solo fuori strada.
// Chi ci arriva da un vecchio segnalibro viene indirizzato alla pagina
// giusta.
// ============================================================
require __DIR__ . '/admin_header.php';
?>
<h2>Vehicle classification</h2>
<p class="admin_muted">
  This page is no longer in use. Types are not classified with a flag any
  more: they live in two separate lists.
</p>
<table class="admin_table" border="1" cellpadding="6" cellspacing="0">
  <thead><tr class="admin_thead_row"><th>Category</th><th>Where it lives</th><th>Managed from</th></tr></thead>
  <tbody>
    <tr><td>Road</td><td><code>vehicle_types</code> &mdash; the Italian highway code list</td>
        <td><a href="admin_vehicle_types.php">Vehicle Types</a></td></tr>
    <tr><td>Special</td><td><code>special_types</code> &mdash; curated by you</td>
        <td><a href="admin_special_types.php">Special types</a></td></tr>
    <tr><td>Shelter</td><td><code>special_types</code>, the same list: a shelter is the same build on a container</td>
        <td><a href="admin_special_types.php">Special types</a></td></tr>
  </tbody>
</table>
<p class="admin_footer_note">
  To make a road type available as a special build too, open
  <a href="admin_special_types.php">Special types</a> and copy it over with
  the checkboxes: the original stays in the road list.
</p>
<?php
require __DIR__ . '/admin_footer.php';
return;


$success = '';
$error   = '';

// ---- POST: salva la classificazione ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (trim($_POST['action'] ?? '') === 'classify') {
        // ID selezionati = SPECIAL; tutti gli altri = ROAD.
        $special_ids = array_map('intval', (array)($_POST['special'] ?? []));
        try {
            $all_ids = $pdo->query('SELECT id FROM `vehicle_types`')->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            $up = $pdo->prepare('UPDATE `vehicle_types` SET macro_category = :m WHERE id = :id');
            $n_sp = 0; $n_rd = 0;
            foreach ($all_ids as $vid) {
                $vid = (int)$vid;
                $macro = in_array($vid, $special_ids, true) ? 'special' : 'road';
                if ($macro === 'special') { $n_sp++; } else { $n_rd++; }
                $up->execute([':m' => $macro, ':id' => $vid]);
            }
            $pdo->commit();
            $success = "Classification saved: {$n_sp} special, {$n_rd} road. "
                     . "Re-run gen_sidebars.py to refresh the per-page sidebars.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = 'Error saving classification.';
        }
    }
}

// ---- Carico tutti i tipi, divisi per macro per il riepilogo ----
$rows = $pdo->query('SELECT id, name, slug, macro_category FROM `vehicle_types` ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$rows_special = array_values(array_filter($rows, static fn($r) => $r['macro_category'] === 'special'));
$rows_road    = array_values(array_filter($rows, static fn($r) => $r['macro_category'] === 'road'));

$page_title = 'Classify Road / Special';
require __DIR__ . '/admin_header.php';
?>

<div class="post_box">
  <h2>Road / Special classification</h2>
  <p class="muted_small">Vehicle bodies and shelters split into two macro categories.
     Tick a type to mark it as <strong>Special</strong>; everything left unticked is
     treated as <strong>Road</strong>. Saving updates <code>vehicle_types.macro_category</code>,
     which drives the Road/Special filters on browse, road and special pages.</p>
</div>

<?php if ($success !== ''): ?>
  <div class="post_box"><p class="flash flash_ok"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
  <div class="post_box"><p class="flash flash_err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
<?php endif; ?>

<!-- ===== Riepilogo a due colonne ===== -->
<div class="post_box">
  <h3>Current classification</h3>
  <table class="admin_table" border="1" cellpadding="6" cellspacing="0" width="100%">
    <tr>
      <th width="50%" align="center"><span style="text-align: left"></span>Special (<?php echo count($rows_special); ?>)</th>
      <th width="50%" align="center"><span style="text-align: left"></span>Road (<?php echo count($rows_road); ?>)</th>
    </tr>
    <tr>
      <td valign="top">
        <?php if (!$rows_special): ?><em>None</em><?php else: ?>
        <ul class="templatemo_list" style="text-align: left">
          <?php foreach ($rows_special as $r): ?>
            <li><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </td>
      <td valign="top">
        <?php if (!$rows_road): ?><em>None</em><?php else: ?>
        <ul class="templatemo_list" style="text-align: left">
          <?php foreach ($rows_road as $r): ?>
            <li><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </td>
    </tr>
  </table>
</div>

<!-- ===== Form di selezione (checkbox = Special) ===== -->
<div class="post_box">
  <h3>Edit classification</h3>
  <form method="post" action="admin_classify_vehicles.php">
    <?php echo csrf_generate(); ?>
    <input type="hidden" name="action" value="classify" />
    <table class="admin_table" border="1" cellpadding="6" cellspacing="0" width="100%">
      <tr><th width="8%" align="left">Special</th>
        <th align="center">Vehicle type</th><th width="25%" align="center">Slug</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td align="left">
          <span style="text-align: left"></span>
          <input type="checkbox" name="special[]" value="<?php echo (int)$r['id']; ?>"<?php echo $r['macro_category'] === 'special' ? ' checked="checked"' : ''; ?> /> 
        </td>
        <td style="text-align: left"><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><code style="text-align: left"><?php echo htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div class="cleaner h10"></div>
    <button type="submit" name="save" value="1" class="more">Save classification</button>
  </form>
</div>

<?php require __DIR__ . '/admin_footer.php'; ?>
