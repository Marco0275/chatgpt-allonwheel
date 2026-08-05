<?php
// ============================================================
// 02_free_ads/02_modify_shelter.php
// Modifica annuncio Free - sezione Shelter / Container.
//
// 23 lug 2026. Ogni sezione ha il suo file di modifica, coerente con il
// wizard di inserimento: si vedono SOLO le variabili riconducibili alla
// sezione. Qui: shelter e container: struttura statica, quindi NIENTE assi,
// telaio o sponda idraulica; restano impianti, arredo, finiture e misure.
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

$aow_lt             = 'free';
$aow_expect_section = 'shelter';
require __DIR__ . '/../shared/ad_modify_page.php';
