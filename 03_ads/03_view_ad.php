<?php
// ============================================================
// 03_ads/03_view_ad.php — thin wrapper
// Logica in shared/view_ad.php
// ============================================================

$module = [
  'table'   => '03_ads',
  'upload_path' => '/upload_image/03_ads/',
  'list_url'  => '03_view_ads.php',
  'gallery_url' => '03_gallery.php',
  'tech_url'  => '03_view_tech_details.php',  // Premium ads hanno tech details
  'page_title'  => 'View premium ad',
];

require __DIR__ . '/../shared/view_ad.php';
