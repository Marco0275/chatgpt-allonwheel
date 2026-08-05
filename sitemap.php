<?php
// sitemap.php — Sitemap XML dinamica con hreflang (en/it/fr/de).
// URL canonico = senza prefisso lingua (= en). Alternate it/fr/de con prefisso.
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');

$base    = rtrim(defined('BASE_URL') ? BASE_URL : 'https://www.allonwheel.com', '/');
$locales = $GLOBALS['AOW_LOCALES'] ?? ['en', 'it', 'fr', 'de'];

$urls = [];
// Deduplica per path: shelter_container.php e' sia pagina statica sia pagina
// di famiglia, e un <loc> ripetuto e' un errore segnalato in Search Console.
$add = function (string $path, ?string $lastmod = null) use (&$urls) {
    foreach ($urls as $u) { if ($u['path'] === $path) { return; } }
    $urls[] = ['path' => $path, 'lastmod' => $lastmod];
};

// Pagine statiche principali
foreach ([
    '', 'browse.php', '06_company/06_30_company_directory.php',
    '05_wanted/wanted_list.php', '04_request_offer/04_request_offer.php',
    'road_vehicles.php', 'special_vehicles.php', 'shelter_container.php',
    'about.php', 'what_we_do.php', 'blog.php', 'FAQ.php', 'Conditions.php',
    'contact.php', 'portfolio.php',
] as $p) { $add($p); }

// Macro -> PAGINA DEDICATA della famiglia (dir. 21, 16 lug 2026).
// Prima si emetteva browse.php?macro=<slug>: ora quell'URL risponde 301 verso
// la pagina dell'argomento, e una sitemap non deve contenere URL che
// redirigono. Si dichiara direttamente l'URL canonico di destinazione.
$aow_family_pages = [
    'race-trailer'      => 'race_trailers.php',
    'hospitality'       => 'hospitality.php',
    'mobile-clinic'     => 'mobile_clinics.php',
    'shelter-container' => 'shelter_container.php',
    'custom-projects'   => 'custom_projects.php',
];
foreach ($pdo->query('SELECT slug FROM `product_macros` ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN) as $slug) {
    // Solo famiglie con una pagina reale (dir. 14: niente URL inventati).
    if (isset($aow_family_pages[(string)$slug])) {
        $add($aow_family_pages[(string)$slug]);
    }
}
// Annunci approvati (premium + free)
foreach ($pdo->query("SELECT id_ads, created_at FROM `03_ads` WHERE status='approved'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $add('03_ads/03_view_ad.php?id_ads=' . (int)$r['id_ads'], substr((string)$r['created_at'], 0, 10));
}
foreach ($pdo->query("SELECT id_ads, created_at FROM `02_free_ads` WHERE status='approved'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $add('02_free_ads/02_view_ad.php?id_ads=' . (int)$r['id_ads'], substr((string)$r['created_at'], 0, 10));
}
// Wanted attive
foreach ($pdo->query("SELECT id, created_at FROM `wanted_ads` WHERE status='active'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $add('05_wanted/wanted_view.php?id=' . (int)$r['id'], substr((string)$r['created_at'], 0, 10));
}
// Blog pubblicati
foreach ($pdo->query("SELECT id, updated_at FROM `blog` WHERE status NOT IN ('pending','rejected','draft','hidden')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $lm = substr((string)($r['updated_at'] ?? ''), 0, 10);
    $add('blog_post.php?id=' . (int)$r['id'], $lm !== '' ? $lm : null);
}

$x = function (string $s) { return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
$locUrl = function (string $path, string $loc) use ($base) {
    $prefix = ($loc === '' ? '' : '/' . $loc);
    return ($path === '') ? $base . $prefix . '/' : $base . $prefix . '/' . $path;
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($urls as $u) {
    $p = $u['path'];
    echo "  <url>\n";
    echo '    <loc>' . $x($locUrl($p, '')) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . $x($u['lastmod']) . "</lastmod>\n";
    }
    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $x($locUrl($p, '')) . '"/>' . "\n";
    foreach ($locales as $loc) {
        $href = ($loc === 'en') ? $locUrl($p, '') : $locUrl($p, $loc);
        echo '    <xhtml:link rel="alternate" hreflang="' . $x((string)$loc) . '" href="' . $x($href) . '"/>' . "\n";
    }
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
