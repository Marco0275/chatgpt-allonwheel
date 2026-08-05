<?php
// ============================================================
// shared/category_hierarchy.php
// Selettore gerarchico Road / Special / Shelter, condiviso.
//
// 24 lug 2026. Rispecchia la gerarchia del wizard di inserimento annunci
// (02_free_ads/02_00_select_type.php): prima si sceglie la CATEGORIA, poi
// la scelta fatta determina quali TIPOLOGIE sono disponibili. Chi compila
// non vede mai voci che non appartengono alla categoria scelta.
//
// -----------------------------------------------------------------
// COSA SIGNIFICANO LE TRE CATEGORIE
// -----------------------------------------------------------------
// ROAD    Veicoli stradali di uso comune: quelli che si incontrano
//         abitualmente per strada. Ambulanze, cassoni, furgoni frigoriferi,
//         minibus, scuolabus, spazzatrici, carri attrezzi e simili.
//         Nel database: vehicle_types.macro_category = 'road'.
//
// SPECIAL Veicoli speciali, cioe' allestimenti fuori dall'uso comune,
//         decisi dall'amministratore (si gestiscono da
//         _admin/admin_vehicle_types.php). Race trailer, hospitality,
//         paddock trailer, uffici e laboratori mobili, motorhome.
//         Nel database: tabella dedicata special_types (slug).
//
// SHELTER Le stesse funzioni degli Special, ma costruite SU CONTAINER
//         invece che su un veicolo: sono strutture, non mezzi su ruote.
//         Per questo non hanno assi ne' telaio.
//         Nel database: item_kind = 'shelter_container'.
// -----------------------------------------------------------------
//
// Variabili attese (facoltative):
//   $aow_ch_name_cat   name del campo categoria   (default 'category')
//   $aow_ch_name_type  name del campo tipologia   (default 'vehicle_type')
//   $aow_ch_cat        valore preselezionato categoria
//   $aow_ch_type       valore preselezionato tipologia
//   $aow_ch_required   true se la tipologia e' obbligatoria (default false)
//
// Richiede $pdo. Solo classi CSS esistenti (dir. 8), nessuno stile inline.
// ============================================================

if (!isset($pdo)) { return; }
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$aow_ch_name_cat  = $aow_ch_name_cat  ?? 'category';
$aow_ch_name_type = $aow_ch_name_type ?? 'vehicle_type';
$aow_ch_cat       = (string)($aow_ch_cat  ?? '');
$aow_ch_type      = (string)($aow_ch_type ?? '');
$aow_ch_required  = !empty($aow_ch_required);

// Tipologie: ROAD da vehicle_types (codice della strada), SPECIAL e SHELTER
// da special_types (lista curata dall'admin). Chi decide quale tabella e'
// VehicleTaxonomy: qui non si sceglie nulla a mano.
$aow_ch_types = [
    'road'    => VehicleTaxonomy::typesForCategory(VehicleTaxonomy::CAT_ROAD, $pdo),
    'special' => VehicleTaxonomy::typesForCategory(VehicleTaxonomy::CAT_SPECIAL, $pdo),
];
// Shelter usa la stessa lista degli special: uno shelter e' un allestimento
// speciale costruito su container.
$aow_ch_types['shelter'] = $aow_ch_types['special'];

$aow_ch_cats = [
    'road'    => ['Road vehicles',      'Standard road vehicles, from the Italian highway code list.'],
    'special' => ['Special vehicles',   'Special builds, from the list curated by our team.'],
    'shelter' => ['Shelter & Container','The same builds as a special vehicle, but on a container: a structure, not a vehicle on wheels.'],
];
?>
<div class="cleaner h10"></div>
<div class="form_row">
  <label><strong>1. What are you looking for?</strong></label>
	<div class="cleaner h10"></div>
  <?php foreach ($aow_ch_cats as $ck => $cv): ?>
  <p>
    <label for="aow_ch_<?php echo $ck; ?>">
      <input type="radio" name="<?php echo htmlspecialchars($aow_ch_name_cat, ENT_QUOTES, 'UTF-8'); ?>"
             id="aow_ch_<?php echo $ck; ?>" value="<?php echo $ck; ?>"
             class="aow_ch_cat"<?php echo $aow_ch_cat === $ck ? ' checked' : ''; ?> />
      <strong><?php echo htmlspecialchars($cv[0], ENT_QUOTES, 'UTF-8'); ?></strong>
      &mdash; <?php echo htmlspecialchars($cv[1], ENT_QUOTES, 'UTF-8'); ?>
    </label>
  </p>
  <?php endforeach; ?>
</div>

<?php // La tipologia compare solo dopo la scelta, e solo con le voci della
      // categoria selezionata. Per Shelter non c'e' tipologia da scegliere:
      // la categoria coincide con il tipo, come nel wizard di inserimento. ?>
<div class="form_row" id="aow_ch_type_row">
  <label for="<?php echo htmlspecialchars($aow_ch_name_type, ENT_QUOTES, 'UTF-8'); ?>">
    <strong>2. Which type?</strong></label>
  <select name="<?php echo htmlspecialchars($aow_ch_name_type, ENT_QUOTES, 'UTF-8'); ?>"
          id="<?php echo htmlspecialchars($aow_ch_name_type, ENT_QUOTES, 'UTF-8'); ?>"
          class="input_field"<?php echo $aow_ch_required ? ' required' : ''; ?>>
    <option value="">-- choose a category first --</option>
  </select>
</div>

<script type="text/javascript">
/* Filtro della tipologia in base alla categoria scelta: la prima scelta
   determina la seconda, come negli step del wizard di inserimento.
   Vanilla JS: nessuna dipendenza da jQuery (bloccato alla 1.3.2). */
(function () {
  var TYPES = <?php echo json_encode($aow_ch_types, JSON_UNESCAPED_UNICODE); ?>;
  var PRESEL = <?php echo json_encode($aow_ch_type, JSON_UNESCAPED_UNICODE); ?>;
  var sel = document.getElementById(<?php echo json_encode($aow_ch_name_type); ?>);
  var row = document.getElementById('aow_ch_type_row');
  var radios = document.querySelectorAll('.aow_ch_cat');
  if (!sel || !row) { return; }

  function fill(cat) {
    sel.innerHTML = '';
    row.style.display = '';
    var list = TYPES[cat] || [];
    var blank = document.createElement('option');
    blank.value = ''; blank.text = list.length ? '-- choose --' : '-- choose a category first --';
    sel.appendChild(blank);
    for (var i = 0; i < list.length; i++) {
      var op = document.createElement('option');
      op.value = list[i].slug; op.text = list[i].name;
      if (list[i].slug === PRESEL) { op.selected = true; }
      sel.appendChild(op);
    }
  }

  for (var i = 0; i < radios.length; i++) {
    radios[i].onclick = function () { fill(this.value); };
  }
  var checked = document.querySelector('.aow_ch_cat:checked');
  if (checked) { fill(checked.value); } else { row.style.display = 'none'; }
})();
</script>
