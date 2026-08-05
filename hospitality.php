<?php
// ============================================================
// hospitality.php  Pagina DEDICATA alla famiglia "Hospitality".
//
// Dir. 21 (16 lug 2026): una pagina = un argomento. Nessun filtro nel
// corpo pagina: la famiglia e' fissata qui e non e' modificabile via
// query string. I filtri stanno solo nelle sidebar.
//
// Il rendering e' nel partial condiviso shared/family_page.php: le
// pagine famiglia restano thin e allineate fra loro (una sola modifica
// per cambiarle tutte).
// ============================================================
$aow_family_slug  = 'hospitality';
$aow_family_self  = 'hospitality.php';
$aow_family_title = 'Motorsport hospitality units for sale - All on Wheel';
$aow_family_desc  = 'Motorsport hospitality units, paddock lounges and mobile suites. Browse listings or request a quotation from specialised European builders.';
require __DIR__ . '/shared/family_page.php';
