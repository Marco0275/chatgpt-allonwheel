<?php
// ============================================================
// sidebar_vtype_search.php — Ricerca veicoli in sidebar (uniforme sito-wide).
// Due box gemelli con MENU A TENDINA (niente checkbox, richiesta 5 lug 2026):
//   - Special vehicles -> special_vehicles.php?vtype=<slug>
//   - Road vehicles    -> road_vehicles.php?vtype=<slug>
// Le voci vengono da `vehicle_types` (macro_category), solo dati reali (dir. 14).
// Nessuno stile nuovo: sb_box / submit_btn esistenti (dir. 8).
// ============================================================
require_once __DIR__ . '/config/session_helper.php';
if (!function_exists('t')) { @require_once __DIR__ . '/config/i18n.php'; }
if (!isset($pdo) || !($pdo instanceof PDO)) { @require_once __DIR__ . '/config/database.php'; }

// Base path automatico (stesso pattern di sidebar_user_box.php)
if (!isset($base_url)) {
    $base_url = '';
    $_vs_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_vs_script, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_vs_script);
}

// Tipi veicolo per macro (una sola query, split in PHP)
$_vs_special = [];
$_vs_road    = [];
// Nuova tassonomia (24 lug 2026): road da vehicle_types, special da
// special_types. Chi decide quale tabella e' VehicleTaxonomy; qui si
// riempiono solo i due elenchi da mostrare.
try {
    require_once __DIR__ . '/libs/vehicle_taxonomy.class.php';
    $_vs_grouped = VehicleTaxonomy::allTypesGrouped($pdo);
    $_vs_road    = $_vs_grouped[VehicleTaxonomy::CAT_ROAD]    ?? [];
    $_vs_special = $_vs_grouped[VehicleTaxonomy::CAT_SPECIAL] ?? [];
	
	// Normalizzazione compatibilità: VehicleTaxonomy può restituire slug semplici
$normalize_vtypes = static function(array $items): array {

    $out = [];

    foreach ($items as $item) {

        if (is_array($item)) {
            $out[] = $item;
        } else {
            $slug = (string)$item;

            $out[] = [
                'slug' => $slug,
                'name' => ucfirst(str_replace('_', ' ', $slug))
            ];
        }
    }

    return $out;
};

$_vs_road = $normalize_vtypes($_vs_road);
$_vs_special = $normalize_vtypes($_vs_special);
	
} catch (Throwable $e) { /* sidebar non deve mai rompere la pagina */ }

// Preselezione: solo sulla pagina di destinazione corrispondente
$_vs_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$_vs_cur  = trim($_GET['vtype'] ?? '');
// dir. 21: una pagina puo' dirottare la ricerca Special su se stessa
// (es. la vetrina noleggi) impostando $aow_special_search_action.
$_vs_special_hijack = isset($aow_special_search_action) && $aow_special_search_action !== '';
$_vs_special_action = $_vs_special_hijack ? $aow_special_search_action : 'special_vehicles.php';
$_vs_cur_special = ($_vs_page === 'special_vehicles.php' || $_vs_special_hijack) ? $_vs_cur : '';
$_vs_cur_road    = ($_vs_page === 'road_vehicles.php')    ? $_vs_cur : '';
?>
<!-- ===== Ricerca Special vehicles (tendina) ===== -->
<div class="sb_box">
  <h3><?php te('b2b.special', 'Special vehicles'); ?></h3>
  <form method="get" action="<?php echo $base_url . $_vs_special_action; ?>">
    <select name="vtype">
      <option value=""><?php te('filter.all_types', 'All types'); ?></option>
      <?php foreach ($_vs_special as $vt): ?>
      <option value="<?php echo htmlspecialchars($vt['slug']); ?>"<?php echo $_vs_cur_special === $vt['slug'] ? ' selected' : ''; ?>><?php te('vt.' . $vt['slug'], $vt['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <div class="cleaner h10"></div>
    <input type="submit" class="submit_btn" value="<?php te('facet.apply', 'Search'); ?>" />
  </form>
</div>
<div class="cleaner h20"></div>

<!-- ===== Ricerca Road vehicles (tendina, stesse caratteristiche) ===== -->
<div class="sb_box">
  <h3><?php te('b2b.road', 'Road vehicles'); ?></h3>
  <form method="get" action="<?php echo $base_url; ?>road_vehicles.php">
    <select name="vtype">
      <option value=""><?php te('filter.all_types', 'All types'); ?></option>
      <?php foreach ($_vs_road as $vt): ?>
      <option value="<?php echo htmlspecialchars($vt['slug']); ?>"<?php echo $_vs_cur_road === $vt['slug'] ? ' selected' : ''; ?>><?php te('vt.' . $vt['slug'], $vt['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <div class="cleaner h10"></div>
    <input type="submit" class="submit_btn" value="<?php te('facet.apply', 'Search'); ?>" />
  </form>
</div>
<div class="cleaner h20"></div>
