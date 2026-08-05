<?php
// ============================================================
// 03_ads/03_error_insert_ad.php — thin wrapper
// ============================================================

$module = [
  'page_title' => 'Premium ad — error',
  // Wizard unificato (15 lug 2026): il vecchio 03_insert_ad.php e' stato
  // rimosso; il retry riparte dal wizard unico con il premium preselezionato.
  'retry_url'  => '../03_ads/03_00_select_type.php?listing=prem',
  'list_url' => '03_view_ads.php',
];

require __DIR__ . '/../shared/error_insert_ad.php';
