<?php
// ============================================================
// 03_ads/03_modify_special.php
// Modifica annuncio Premium - sezione Special vehicle.
//
// 23 lug 2026. Ogni sezione ha il suo file di modifica, coerente con il
// wizard di inserimento: si vedono SOLO le variabili riconducibili alla
// sezione. Qui: veicoli speciali (race trailer, hospitality, motorhome,
// uffici e laboratori mobili): e' la sezione piu' completa, prevede tutti i
// gruppi tecnici.
//
// Il file e' volutamente sottile: proprieta', validazione e layout stanno in
// shared/ad_modify_page.php, cosi' le tre sezioni non possono divergere nel
// tempo. Le variabili di ciascuna sezione sono dichiarate una volta sola in
// libs/ad_section_fields.class.php.
//
// Se l'annuncio aperto NON appartiene a questa sezione si viene reindirizzati
// al file della sezione giusta: non si modifica un veicolo stradale da una
// pagina che mostra i campi degli shelter.
// ============================================================

$aow_lt             = 'prem';
$aow_expect_section = 'special';
require __DIR__ . '/../shared/ad_modify_page.php';
