<?php
// ============================================================
// custom_projects.php  Pagina DEDICATA alla famiglia "Custom Projects".
//
// Dir. 21 (16 lug 2026): una pagina = un argomento. Nessun filtro nel
// corpo pagina: la famiglia e' fissata qui e non e' modificabile via
// query string. I filtri stanno solo nelle sidebar.
//
// Il rendering e' nel partial condiviso shared/family_page.php: le
// pagine famiglia restano thin e allineate fra loro (una sola modifica
// per cambiarle tutte).
// ============================================================
$aow_family_slug  = 'custom-projects';
$aow_family_self  = 'custom_projects.php';
$aow_family_title = 'Custom vehicle projects - All on Wheel';
$aow_family_desc  = 'Bespoke vehicle projects and special conversions. Browse listings or request a quotation from specialised European builders.';
require __DIR__ . '/shared/family_page.php';
