<?php
// ============================================================
// 03_ads/03_gallery.php — thin wrapper
// Logica in shared/gallery.php
// ============================================================

$module = [
  'table_main'  => '03_ads',
  'table_gallery' => '03_ads_gallery',
  'upload_path' => '/upload_image/03_ads/',
  'detail_url'  => '03_view_ad.php',
  'page_title'  => 'Premium ad gallery',
];

require __DIR__ . '/../shared/gallery.php';
