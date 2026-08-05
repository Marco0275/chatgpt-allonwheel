<?php
// shared/tech_details_fields.php
// SORGENTE UNICA dei campi tecnici annuncio premium (tabella 03_ads_tech_details).
// Usato sia dal CONFIGURATORE RFQ (mode 'form', checkbox/campi compilabili) sia dal
// PDF/stampa (mode 'print', sola lettura): stessi campi + stessa impaginazione.
// Variabili attese: $mode ('form'|'print'), $tech (array valori, opzionale).
// Solo classi CSS esistenti (tbl_collapse, thead_row, checkbox, control, input_field).

if (!isset($mode) || ($mode !== 'form' && $mode !== 'print')) { $mode = 'form'; }
$tech = (isset($tech) && is_array($tech)) ? $tech : [];

$AOW_TECH_GROUPS = [
  'General options' => ['check', [
     'Awning'=>'Awning','Workshop'=>'Workshop','Belly'=>'Belly','Kitchen'=>'Kitchen',
     'Beds'=>'Beds','Genset'=>'Genset','Bathroom'=>'Bathroom','SAT'=>'SAT',
  ]],
  'Lift facilities' => ['text', [
     'Lift_manufactorer'=>'Manufacturer','Lift_length'=>'Length','Lift_width'=>'Width','Lift_capacity'=>'Capacity',
  ]],
  'Cargo facilities' => ['check', [
     'rails'=>'Rails','LED'=>'LED','independent_entrance_cargo'=>'Independent entrance (cargo)',
     'Fixing'=>'Fixing','Cabinets'=>'Cabinets','Adjustable'=>'Adjustable',
  ]],
  'Office furniture' => ['check', [
     'Workbenches'=>'Workbenches','HVAC'=>'HVAC','Telemetry'=>'Telemetry',
     'independent_entrance_office'=>'Independent entrance (office)','Electrical'=>'Electrical',
     'office_other'=>'Other','Windows'=>'Windows','TV'=>'TV',
  ]],
  'Electrical system' => ['check', [
     'Main_panel'=>'Main panel','batteries'=>'Batteries','Charger'=>'Charger','Connection'=>'Connection',
     'Switchgear'=>'Switchgear','electrical_other'=>'Other','Sockets'=>'Sockets','Rema'=>'Rema',
  ]],
  'Outside finishing' => ['mixed', [
     'Plywood'=>['check','Plywood'],'painted'=>['text','Painted in color'],
     'Sandwich'=>['check','Sandwich'],'Stickers'=>['text','Stickers'],'Special'=>['check','Special body'],
  ]],
  'Chassis' => ['mixed', [
     'Stepdeck'=>['check','Step deck'],'axles'=>['text','Number of axles'],
     'Straightline'=>['check','Straight line'],'MGW'=>['text','Maximum Gross Weight'],
     'chassis_special'=>['check','Special chassis'],'Saddle'=>['text','Saddle height'],
  ]],
  'External dimension' => ['text', [
     'ext_length'=>'Length','ext_width'=>'Width','ext_height'=>'Height',
  ]],
];

$aow_h  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$aow_on = function ($v) { return (!empty($v) && $v !== '0'); };

// Render di una singola cella (checkbox o testo) in base al mode
$aow_cell = function ($name, $type, $label) use ($mode, $tech, $aow_h, $aow_on) {
    if ($type === 'check') {
        $on = $aow_on($tech[$name] ?? 0);
        if ($mode === 'form') {
            return '<td><span class="checkbox"><input type="checkbox" class="control control--checkbox" id="' . $name
                 . '" name="tech[' . $name . ']" value="1"' . ($on ? ' checked="checked"' : '') . ' /> ' . $aow_h($label) . '</span></td>';
        }
        return '<td><span class="checkbox">' . ($on ? '&#9745;' : '&#9744;') . ' ' . $aow_h($label) . '</span></td>';
    }
    // text
    $val = (string)($tech[$name] ?? '');
    if ($mode === 'form') {
        return '<td><span class="checkbox">' . $aow_h($label) . ': <input type="text" class="input_field" name="tech['
             . $name . ']" id="' . $name . '" value="' . $aow_h($val) . '" size="14" /></span></td>';
    }
    return '<td><span class="checkbox">' . $aow_h($label) . ': <strong>' . ($val !== '' ? $aow_h($val) : '&mdash;') . '</strong></span></td>';
};
?>
<table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
  <tr class="checkbox">
    <td colspan="3"><span class="checkbox" style="text-align: left">Number of cars carried: 
    <?php if ($mode === 'form'): ?>
      <input type="text" class="input_field" name="tech[cars]" id="cars" value="<?php echo $aow_h($tech['cars'] ?? ''); ?>" size="6" />
    <?php else: ?>
      <strong><?php echo ($tech['cars'] ?? '') !== '' ? $aow_h($tech['cars']) : '&mdash;'; ?></strong>
    <?php endif; ?>
    </span></td>
  </tr>
<?php
// Filtro di SEZIONE (23 lug 2026, facoltativo): se chi include imposta
// $aow_tech_section ('road'|'special'|'shelter'), si mostrano solo i gruppi e
// i campi pertinenti a quella sezione (AdSectionFields). Se NON e' impostato
// il comportamento resta quello di prima - tutti i campi - cosi' il
// configuratore RFQ e il PDF non cambiano di una virgola.
$aow_sec_filter = (isset($aow_tech_section) && $aow_tech_section !== '') ? (string)$aow_tech_section : null;
if ($aow_sec_filter !== null && !class_exists('AdSectionFields')) {
    require_once __DIR__ . '/../libs/ad_section_fields.class.php';
}
?>
<?php foreach ($AOW_TECH_GROUPS as $group => $def):
    if ($aow_sec_filter !== null && !AdSectionFields::hasTechGroup($aow_sec_filter, $group)) { continue; }
    list($gtype, $fields) = $def;
    $cells = [];
    foreach ($fields as $name => $meta) {
        if ($aow_sec_filter !== null && !AdSectionFields::hasTechField($aow_sec_filter, $group, $name)) { continue; }
        if ($gtype === 'mixed') { $cells[] = $aow_cell($name, $meta[0], $meta[1]); }
        else { $cells[] = $aow_cell($name, $gtype, $meta); }
    }
    if (empty($cells)) { continue; }
?>
  <tr class="thead_row"><td colspan="3" class="checkbox"><strong><?php echo $aow_h($group); ?></strong></td></tr>
  <?php foreach (array_chunk($cells, 3) as $row): ?>
  <tr class="checkbox"><?php echo implode('', $row); for ($i = count($row); $i < 3; $i++) { echo '<td>&nbsp;</td>'; } ?></tr>
  <?php endforeach; ?>
<?php endforeach; ?>
</table>
