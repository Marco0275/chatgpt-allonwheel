<?php
// ============================================================
// 02_free_ads/02_gallery.php — thin wrapper
// Logica in shared/gallery.php
// ============================================================

$module = [
  'table_main'  => '02_free_ads',
  'table_gallery' => '02_free_ads_gallery',
  'upload_path' => '/upload_image/02_free_ads/',
  'detail_url'  => '02_view_ad.php',
  'page_title'  => 'Free ad gallery',
];

require __DIR__ . '/../shared/gallery.php';
