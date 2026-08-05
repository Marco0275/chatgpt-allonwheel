<?php
// ============================================================
// mobile_clinics.php  Pagina DEDICATA alla famiglia "Mobile Clinics".
//
// Dir. 21 (16 lug 2026): una pagina = un argomento. Nessun filtro nel
// corpo pagina: la famiglia e' fissata qui e non e' modificabile via
// query string. I filtri stanno solo nelle sidebar.
//
// Il rendering e' nel partial condiviso shared/family_page.php: le
// pagine famiglia restano thin e allineate fra loro (una sola modifica
// per cambiarle tutte).
// ============================================================
$aow_family_slug  = 'mobile-clinic';
$aow_family_self  = 'mobile_clinics.php';
$aow_family_title = 'Mobile clinics and medical units for sale - All on Wheel';
$aow_family_desc  = 'Mobile clinics, medical and diagnostic units built on truck, trailer or van base. Browse listings or request a quotation from specialised builders.';
require __DIR__ . '/shared/family_page.php';
