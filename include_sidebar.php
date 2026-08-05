<?php
// ============================================================
// include_sidebar.php — Sidebar UNIFORME sito-wide (richiesta 5 lug 2026).
//
// Su TUTTE le pagine con sidebar compaiono, nell'ordine:
//   1. Ricerca Special vehicles (menu a tendina)
//   2. Ricerca Road vehicles   (menu a tendina, stesse caratteristiche)
//   3. Faccette marketplace    (condizione + fascia di prezzo) - dir. 21
//   4. Box utente login-aware  (My account / Login)
//
// Il vecchio dispatcher per-pagina (sidebar_<nomepagina>.php, generati da
// gen_sidebars.py) e' DISATTIVATO ma i file restano su disco: per tornare
// al modello per-pagina basta ripristinare la versione precedente di
// questo file. Nessuno stile nuovo (dir. 8).
// ============================================================

require_once __DIR__ . '/config/session_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('t')) { @require_once __DIR__ . '/config/i18n.php'; }

include __DIR__ . '/sidebar_user_box.php';  // Dir.: box utente in cima alla sidebar.
include __DIR__ . '/sidebar_vtype_search.php';
// Dir. 21: i filtri vivono SOLO nelle sidebar. Il box si nasconde da solo
// se non ci sono ancora annunci da filtrare (niente tendine vuote).
include __DIR__ . '/sidebar_facets.php';
// CTA verso la RFQ della sezione corrente: compare SOLO se la pagina ha
// impostato $aow_rfq_section (road/special/shelter).
include __DIR__ . '/sidebar_rfq_cta.php';
