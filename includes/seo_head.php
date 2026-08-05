<?php
// ============================================================
// includes/seo_head.php — SEO per il <head> (M1, lancio).
// Emette: canonical assoluto + tag hreflang + JSON-LD opzionale.
// Uso (prima di </head>):
//   $seo_canonical = 'road_vehicles.php?vtype=x';   // path relativo alla root, gia' filtrato
//   $seo_jsonld    = [ ... ];                       // opzionale, array -> script ld+json
//   include __DIR__ . '/includes/seo_head.php';     // (o ../includes/ dalle sottocartelle)
// Robusto: nessun output se manca il necessario; non puo' rompere la pagina.
// ============================================================
if (!defined('BASE_URL')) { @require_once __DIR__ . '/../config/bootstrap.php'; }
if (!function_exists('aow_hreflang_tags')) { @require_once __DIR__ . '/../config/i18n.php'; }

$aow_seo_base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');

// Canonical (solo se pagina e base note)
if (!empty($seo_canonical) && $aow_seo_base !== '') {
    $aow_seo_href = $aow_seo_base . '/' . ltrim((string)$seo_canonical, '/');
    echo '<link rel="canonical" href="' . htmlspecialchars($aow_seo_href, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
}

// Hreflang (en/it/fr/de + x-default) — funzioni gia' presenti in i18n.php
if (function_exists('aow_hreflang_tags')) { echo aow_hreflang_tags(); }

// JSON-LD schema.org (facoltativo)
if (!empty($seo_jsonld) && is_array($seo_jsonld)) {
    $aow_seo_json = json_encode($seo_jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($aow_seo_json !== false) {
        echo '<script type="application/ld+json">' . $aow_seo_json . '</script>' . "\n";
    }
}
unset($seo_canonical, $seo_jsonld, $aow_seo_base, $aow_seo_href, $aow_seo_json);
