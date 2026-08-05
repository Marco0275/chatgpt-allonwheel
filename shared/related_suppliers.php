<?php
// ============================================================
// Ponte tassonomico (single-source) - All on Wheel
// Hub = MACRO. Dato il macro di un annuncio/contesto, elenca i
// fornitori (produttori) corrispondenti:
//   ProductMacro::supplierKeysFor($macro)  -> chiavi product_key
//   CompanyManager::getCompaniesByProducts -> aziende (regular+special)
// Solo dati reali (dir. 14). Nessun campo inventato.
// ============================================================

if (!function_exists('aow_related_suppliers')) {
    /**
     * @param object $cm    Istanza CompanyManager (mysqli)
     * @param string $macro Slug macro (ProductMacro)
     * @return array        Righe azienda (id, ragione_sociale, ...)
     */
    function aow_related_suppliers($cm, string $macro, int $limit = 8): array
    {
        if ($macro === '' || !is_object($cm) || !class_exists('ProductMacro')) {
            return [];
        }
        if (!ProductMacro::exists($macro)) {
            return [];
        }
        $k = ProductMacro::supplierKeysFor($macro);
        try {
            $rows = $cm->getCompaniesByProducts($k['regular'] ?? [], $k['special'] ?? []);
        } catch (Throwable $e) {
            return [];
        }
        return array_slice($rows, 0, max(1, $limit));
    }
}

if (!function_exists('aow_render_related_suppliers')) {
    /**
     * Rende il box "Verified suppliers" con link alle schede azienda.
     * @param array  $rows          Output di aow_related_suppliers()
     * @param string $base          Prefisso path verso la root ('' oppure '../')
     * @param string $macro_label   Etichetta macro (per il titolo)
     * @param string $directory_url URL directory completa (facoltativo)
     */
    function aow_render_related_suppliers(array $rows, string $base, string $macro_label, string $directory_url = ''): void
    {
        if (!$rows) {
            return;
        }
        ?>
    <div class="rel_suppliers">
      <h3><?php te('bridge.suppliers_for', 'Verified suppliers'); ?><?php echo $macro_label !== '' ? ' &mdash; ' . htmlspecialchars($macro_label) : ''; ?></h3>
      <p class="rel_sub"><?php te('bridge.suppliers_sub', 'Manufacturers and outfitters for this category.'); ?></p>
      <ul class="rel_list">
      <?php foreach ($rows as $co): ?>
        <li><a href="<?php echo $base; ?>06_company/06_02_view_company.php?id=<?php echo (int)$co['id']; ?>"><?php echo htmlspecialchars((string)$co['ragione_sociale']); ?></a></li>
      <?php endforeach; ?>
      </ul>
      <?php if ($directory_url !== ''): ?>
      <p><a class="more" href="<?php echo htmlspecialchars($directory_url); ?>"><?php te('bridge.view_directory', 'Browse the full supplier directory'); ?></a></p>
      <?php endif; ?>
    </div>
        <?php
    }
}
