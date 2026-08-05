<?php
// ============================================================
// scripts/check_schema.php  Verifica quali patch SQL sono applicate
//
// 20 lug 2026.
//
// A COSA SERVE
// Il codice PHP della V3.1 include gia' tutto (bozze, RFQ mirate, wanted,
// hero admin...), ma il codice funziona solo se le TABELLE e COLONNE che usa
// esistono davvero nel DB. Le patch changelog vanno applicate a mano, e su
// questo progetto un trasferimento al server e' gia' fallito in silenzio.
// Questo script guarda il DB REALE e dice, patch per patch, cosa c'e' e cosa
// manca - cosi' non devi indovinare ne' aprire phpMyAdmin a mano.
//
// USO (CLI):
//   php scripts/check_schema.php
// Esce con codice 0 se tutto a posto, 1 se manca qualcosa.
// ============================================================

$webroot = dirname(__DIR__);
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';

// Ogni voce: descrizione + una closure che ritorna true se l'oggetto esiste.
function aow_table_exists(PDO $pdo, string $t): bool {
    try {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t"
        );
        $st->execute([':t' => $t]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function aow_col_exists(PDO $pdo, string $t, string $c): bool {
    try {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c"
        );
        $st->execute([':t' => $t, ':c' => $c]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

// Checklist: le cose che il codice della V3.1 si aspetta di trovare.
$checks = [
    'quote_requests (lead engine)'          => fn() => aow_table_exists($pdo, 'quote_requests'),
    'quote_request_recipients'              => fn() => aow_table_exists($pdo, 'quote_request_recipients'),
    '  -> match_score (RFQ punteggio)'      => fn() => aow_col_exists($pdo, 'quote_request_recipients', 'match_score'),
    '  -> rank_pos (RFQ graduatoria)'       => fn() => aow_col_exists($pdo, 'quote_request_recipients', 'rank_pos'),
    'product_macros (5 famiglie)'           => fn() => aow_table_exists($pdo, 'product_macros'),
    '  -> 02_free_ads.product_macro'        => fn() => aow_col_exists($pdo, '02_free_ads', 'product_macro'),
    '  -> 03_ads.product_macro'             => fn() => aow_col_exists($pdo, '03_ads', 'product_macro'),
    'wanted_ads (wanted board)'             => fn() => aow_table_exists($pdo, 'wanted_ads'),
    'saved_searches'                        => fn() => aow_table_exists($pdo, 'saved_searches'),
    'ad_drafts (registrazione dopo wizard)' => fn() => aow_table_exists($pdo, 'ad_drafts'),
    'site_settings (hero admin)'            => fn() => aow_table_exists($pdo, 'site_settings'),
    'users.is_verified (verifica email)'    => fn() => aow_col_exists($pdo, 'users', 'is_verified'),
];

echo "== Allonwheel - stato schema DB ==\r\n";
$missing = [];
foreach ($checks as $label => $test) {
    $ok = false;
    try { $ok = (bool)$test(); } catch (Throwable $e) { $ok = false; }
    printf("  [%s] %s\r\n", $ok ? 'OK ' : '!! ', $label);
    if (!$ok) { $missing[] = $label; }
}

echo "\r\n";
if (empty($missing)) {
    echo "Tutto a posto: ogni tabella/colonna attesa e' presente.\r\n";
    exit(0);
}

echo count($missing) . " oggetti mancanti. Applica le patch corrispondenti da sql/Changelog/:\r\n";
$hint = [
    'match_score'   => '  2026-07-17_rfq_match_score.sql',
    'rank_pos'      => '  2026-07-17_rfq_match_score.sql',
    'ad_drafts'     => '  2026-07-17_ad_drafts.sql',
    'site_settings' => '  2026-07-20_site_settings.sql',
    'product_macro' => '  product_macros.sql',
    'quote_requests'=> '  quote_requests.sql',
    'wanted_ads'    => '  2026-06-18_leadcentric_core.sql',
    'saved_searches'=> '  2026-07-06_saved_searches.sql',
];
$seen = [];
foreach ($missing as $m) {
    foreach ($hint as $needle => $file) {
        if (stripos($m, $needle) !== false && !isset($seen[$file])) {
            echo $file . "\r\n";
            $seen[$file] = true;
        }
    }
}
exit(1);
