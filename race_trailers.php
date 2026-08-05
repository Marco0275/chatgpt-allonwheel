<?php
// ============================================================
// race_trailers.php  Pagina DEDICATA alla famiglia "Race Trailers".
//
// Dir. 21 (16 lug 2026): una pagina = un argomento. Nessun filtro nel
// corpo pagina: la famiglia e' fissata qui e non e' modificabile via
// query string. I filtri stanno solo nelle sidebar.
//
// Il rendering e' nel partial condiviso shared/family_page.php: le
// pagine famiglia restano thin e allineate fra loro (una sola modifica
// per cambiarle tutte).
// ============================================================
$aow_family_slug  = 'race-trailer';
$aow_family_self  = 'race_trailers.php';
$aow_family_title = 'Race trailers for sale - All on Wheel';
$aow_family_desc  = 'Race trailers and transporters for motorsport teams: single and double deck, workshop layouts, awnings. Browse listings or request a quotation from specialised European builders.';
require __DIR__ . '/shared/family_page.php';
