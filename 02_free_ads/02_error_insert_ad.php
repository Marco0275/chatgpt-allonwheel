<?php
// ============================================================
// 02_free_ads/02_error_insert_ad.php — thin wrapper
// ============================================================

$module = [
  'page_title' => 'Free ad — error',
  'retry_url'  => '02_insert_ad.php',
  'list_url' => '02_view_ads.php',
];

require __DIR__ . '/../shared/error_insert_ad.php';
