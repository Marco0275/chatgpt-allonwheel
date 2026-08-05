<?php
// ============================================================
// 02_free_ads/02_view_ad.php — thin wrapper
// Logica in shared/view_ad.php
// ============================================================

$module = [
  'table'   => '02_free_ads',
  'upload_path' => '/upload_image/02_free_ads/',
  'list_url'  => '02_view_ads.php',
  'gallery_url' => '02_gallery.php',
  'tech_url'  => null,       // Free ads non hanno tech details
  'page_title'  => 'View free ad',
];

require __DIR__ . '/../shared/view_ad.php';
