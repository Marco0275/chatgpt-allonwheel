<?php
// ============================================================
// shared/ad_modify_fields.php
// Campi BASE del form di modifica, filtrati per sezione.
//
// 23 lug 2026. Rispecchia 02_free_ads/02_insert_ad.php: stessi campi, stesse
// etichette, stesse classi (dir. 8, solo classi esistenti). L'unica differenza
// e' che i valori arrivano dall'annuncio salvato invece che dalla bozza, e che
// i campi non pertinenti alla sezione non compaiono (AdSectionFields).
//
// Variabili attese:
//   $aow_ad      array  riga dell'annuncio (valori correnti)
//   $aow_section string road|special|shelter
//   $aow_vts     array  tipi veicolo della macro (vuoto per shelter)
// ============================================================

if (!isset($aow_ad) || !is_array($aow_ad)) { return; }
$aow_section = isset($aow_section) ? (string)$aow_section : AdSectionFields::SEC_SPECIAL;
$aow_vts     = (isset($aow_vts) && is_array($aow_vts)) ? $aow_vts : [];

// Valore corrente di un campo, gia' pronto per l'attributo HTML.
$aow_cv = static function (string $k) use ($aow_ad) {
    return htmlspecialchars((string)($aow_ad[$k] ?? ''), ENT_QUOTES, 'UTF-8');
};
$aow_sel = static function (string $k, string $v) use ($aow_ad) {
    return ((string)($aow_ad[$k] ?? '') === $v) ? ' selected' : '';
};
?>
        <div class="form_row">
          <label for="title"><strong>Title:</strong></label>
          <input type="text" name="title" id="title" class="input_field" required maxlength="200" value="<?php echo $aow_cv('title'); ?>" />
        </div>
        <div class="form_row">
          <label for="subtitle"><br>
          Subtitle:</label>
          <input type="text" name="subtitle" id="subtitle" class="input_field" maxlength="200" value="<?php echo $aow_cv('subtitle'); ?>" />
        </div>
        <div class="form_row">
          <label for="list_price"><br>
          List price (&euro;):</label>
          <input type="number" min="1" step="0.01" name="list_price" id="list_price" class="input_field" value="<?php echo $aow_cv('list_price'); ?>"/>
        </div>

        <?php // Misure in metri: identiche all'inserimento (decimali ammessi). ?>
        <div class="form_row">
          <label for="length_mt"><br>
          Length (mt):</label>
          <input type="number" min="0" max="9999.99" step="0.01" name="length_mt" id="length_mt" class="input_field" value="<?php echo $aow_cv('length_mt'); ?>" />
        </div>
        <div class="form_row">
          <label for="width_mt"><br>
          Width (mt):</label>
          <input type="number" min="0" max="9999.99" step="0.01" name="width_mt" id="width_mt" class="input_field" value="<?php echo $aow_cv('width_mt'); ?>" />
        </div>
        <div class="form_row">
          <label for="height_mt"><br>
          Height (mt):</label>
          <input type="number" min="0" max="9999.99" step="0.01" name="height_mt" id="height_mt" class="input_field" value="<?php echo $aow_cv('height_mt'); ?>" />
        </div>

        <?php // Assi: solo veicoli. Uno shelter/container non ne ha. ?>
        <?php if (AdSectionFields::hasAxles($aow_section)): ?>
        <div class="form_row">
          <label for="axles_n"><br>
          Axles:</label>
          <input type="number" min="0" max="20" name="axles_n" id="axles_n" class="input_field" value="<?php echo $aow_cv('axles_n'); ?>" />
        </div>
        <?php endif; ?>

        <div class="form_row">
          <label for="type"><br>
          Type:</label>
          <select name="type" id="type" class="input_field">
          <option value="New on sell"<?php echo $aow_sel('type','New on sell'); ?>>New on sell</option>
          <option value="Used on sell"<?php echo $aow_sel('type','Used on sell'); ?>>Used on sell</option>
          <option value="For rent"<?php echo $aow_sel('type','For rent'); ?>>For rent</option>
          <option value="Project"<?php echo $aow_sel('type','Project'); ?>>Project</option>
          </select>
        </div>
        <div class="form_row">
          <label for="conditions"><br>
          Condition:</label>
          <select name="conditions" id="conditions" class="input_field">
          <option value="New"<?php echo $aow_sel('conditions','New'); ?>>New</option>
          <option value="As good as new"<?php echo $aow_sel('conditions','As good as new'); ?>>As good as new</option>
          <option value="Used"<?php echo $aow_sel('conditions','Used'); ?>>Used</option>
          <option value="Poor"<?php echo $aow_sel('conditions','Poor'); ?>>Poor</option>
          <option value="Project"<?php echo $aow_sel('conditions','Project'); ?>>Project</option>
          </select>
        </div>

        <?php // Classificazione: la sezione NON si cambia dalla modifica (come
              // nell'inserimento, dove e' decisa allo step 1). item_kind e
              // macro_category viaggiano hidden; il tipo si puo' affinare, ma
              // solo dentro la macro dell'annuncio. ?>
        <input type="hidden" name="item_kind"      value="<?php echo $aow_cv('item_kind'); ?>" />
        <input type="hidden" name="macro_category" value="<?php echo $aow_cv('macro_category'); ?>" />

        <div class="form_row">
          <?php if (AdSectionFields::hasVehicleTypeChoice($aow_section) && !empty($aow_vts)): ?>
          <label for="vehicle_type"><br>
          Vehicle type:</label>
          <select name="vehicle_type" id="vehicle_type" class="input_field">
            <?php foreach ($aow_vts as $aow_v): ?>
            <option value="<?php echo htmlspecialchars($aow_v['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $aow_sel('vehicle_type', (string)$aow_v['slug']); ?>><?php echo htmlspecialchars($aow_v['name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <?php else: ?>
          <label><br>
          Vehicle type:</label>
          <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars(VehicleTaxonomy::SHELTER_SLUG, ENT_QUOTES, 'UTF-8'); ?>" />
          <em>Shelter &amp; Container</em>
          <?php endif; ?>
        </div>

        <div class="form_row">
          <label for="description"><br>
          Description:</label>
          <textarea name="description" id="description" class="required" rows="6" required><?php echo htmlspecialchars((string)($aow_ad['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
