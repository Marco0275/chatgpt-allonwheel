<?php
// ============================================================
// 01_login/export_data.php — Esportazione dati personali (GDPR Art. 20)
//
// L'utente loggato scarica tutti i propri dati in formato JSON
// (formato strutturato, di uso comune e leggibile da macchina, Art. 20(1)).
//
// Sicurezza: require_user_logged_in() — un utente esporta SOLO i propri
// dati. La password (hash) NON viene mai inclusa nell'export.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

$user_id = require_user_logged_in();

$export = [
    'export_info' => [
        'generated_at' => date('c'),
        'subject_id'   => $user_id,
        'format'       => 'JSON',
        'note'         => 'Personal data export pursuant to GDPR Art. 20. Password hashes are intentionally excluded.',
    ],
];

try {
    // Account (senza password)
    $st = $pdo->prepare('SELECT id_user, username, email, phone, is_verified, created_at, user_tier FROM users WHERE id_user = ?');
    $st->execute([$user_id]);
    $export['account'] = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    // Annunci free + gallery
    $st = $pdo->prepare('SELECT * FROM `02_free_ads` WHERE id_user = ?');
    $st->execute([$user_id]);
    $export['free_ads'] = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare('SELECT g.* FROM `02_free_ads_gallery` g JOIN `02_free_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?');
    $st->execute([$user_id]);
    $export['free_ads_gallery'] = $st->fetchAll(PDO::FETCH_ASSOC);

    // Annunci premium + gallery + tech
    $st = $pdo->prepare('SELECT * FROM `03_ads` WHERE id_user = ?');
    $st->execute([$user_id]);
    $export['premium_ads'] = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare('SELECT g.* FROM `03_ads_gallery` g JOIN `03_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?');
    $st->execute([$user_id]);
    $export['premium_ads_gallery'] = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare('SELECT t.* FROM `03_ads_tech_details` t JOIN `03_ads` a ON a.id_ads = t.id_ads WHERE a.id_user = ?');
    $st->execute([$user_id]);
    $export['premium_ads_tech_details'] = $st->fetchAll(PDO::FETCH_ASSOC);

    // Azienda + figli
    $st = $pdo->prepare('SELECT * FROM `06_company` WHERE user_id = ?');
    $st->execute([$user_id]);
    $export['company'] = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare('SELECT * FROM `06_company_gallery` WHERE user_id = ?');
    $st->execute([$user_id]);
    $export['company_gallery'] = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] export_data error (user=' . $user_id . '): ' . $e->getMessage());
    http_response_code(500);
    exit('Could not generate your data export. Please try again later.');
}

$json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="allonwheel_my_data_' . date('Ymd') . '.json"');
header('Content-Length: ' . strlen($json));
echo $json;
exit;
