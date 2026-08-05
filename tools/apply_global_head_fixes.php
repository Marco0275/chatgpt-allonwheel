<?php
/**
 * tools/apply_global_head_fixes.php — patch meccanica, una tantum, del <head>.
 *
 * Applica a tutte le pagine PHP del sito (escluse vendor/ e libs/PHPMailer/)
 * tre correzioni che erano ripetute file per file:
 *
 *  1. <html> senza attributo lang. Screen reader e motori di ricerca non
 *     sanno in che lingua e' la pagina; con quattro versioni linguistiche
 *     (en/it/fr/de) e' anche un segnale hreflang incoerente.
 *     -> <html lang="<?= aow_locale() ?>" xmlns=...>
 *
 *  2. I quattro <script> in <head> (jquery, ddsmoothmenu, piroBox, site_init)
 *     sono bloccanti: 83 KB scaricati ed eseguiti prima di disegnare
 *     qualsiasi pixel. Nessuno di essi serve al primo paint.
 *     -> defer (che preserva l'ordine di esecuzione, quindi site_init.js
 *        continua a trovare jQuery gia' caricato).
 *
 *  3. <html> duplicato dell'attributo lang gia' presente: nessuna modifica.
 *
 * Uso:  php tools/apply_global_head_fixes.php [--dry-run]
 */

$root   = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);
$skip   = ['/vendor/', '/libs/PHPMailer/', '/node_modules/', '/tools/'];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$changedLang = $changedDefer = 0;
$files = [];

foreach ($it as $f) {
    /** @var SplFileInfo $f */
    if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') { continue; }
    $path = $f->getPathname();
    foreach ($skip as $s) { if (strpos($path, $s) !== false) { continue 2; } }
    $files[] = $path;
}
sort($files);

$langReplacement = '<html lang="<?php echo function_exists(\'aow_locale\') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : \'en\'; ?>" xmlns="http://www.w3.org/1999/xhtml">';

foreach ($files as $path) {
    $src = file_get_contents($path);
    if ($src === false) { continue; }
    $out = $src;

    // 1. lang mancante
    $out = str_replace('<html xmlns="http://www.w3.org/1999/xhtml">', $langReplacement, $out);

    // 2. defer sugli script bloccanti del template
    $out = preg_replace_callback(
        '#<script([^>]*?)src="([^"]*(?:jquery\.min|ddsmoothmenu|piroBox\.1_2|site_init)\.js[^"]*)"([^>]*)></script>#i',
        static function (array $m): string {
            $attrs = $m[1] . $m[3];
            if (stripos($attrs, 'defer') !== false || stripos($attrs, 'async') !== false) { return $m[0]; }
            return '<script' . rtrim($m[1]) . ' src="' . $m[2] . '"' . rtrim($m[3]) . ' defer></script>';
        },
        $out
    );

    // 3. Immagini di contenuto: lazy loading e decodifica asincrona.
    //    Senza, il browser scarica e decodifica anche le foto che stanno sotto
    //    la piega prima di finire di disegnare la pagina. Esclusi il logo
    //    (sempre visibile subito) e le immagini gia' annotate a mano.
    //    I blocchi PHP dentro il tag vanno trattati come atomici: con un
    //    semplice [^>]* la ricerca si fermerebbe sul '>' del tag di chiusura
    //    PHP e spezzerebbe il codice a meta'.
    $imgRe = '#<img\s((?:[^<>]|<\?(?:[^?]|\?(?!>))*\?>)*?)(/?)>#i';
    $out = preg_replace_callback($imgRe, static function (array $m): string {
        $attrs = $m[1];
        if (stripos($attrs, 'loading=') !== false) { return $m[0]; }
        if (stripos($attrs, 'brand_logo') !== false) { return $m[0]; }
        return '<img ' . rtrim($attrs) . ' loading="lazy" decoding="async"' . ($m[2] === '/' ? ' />' : '>');
    }, $out);
    if ($out === null) { $out = $src; } // backtracking fallito: nessuna modifica

    // 4. Bersaglio del link "Skip to content" (header.php lo punta a #main).
    //    Un div vuoto prima del contenuto: nessun impatto visivo, nessun
    //    id duplicato (ogni pagina usa o templatemo_content o no_sidebar).
    if (strpos($out, 'id="main"') === false) {
        foreach (['<div id="templatemo_content">', '<div id="no_sidebar">'] as $anchor) {
            if (strpos($out, $anchor) !== false) {
                $out = preg_replace('/' . preg_quote($anchor, '/') . '/', '<div id="main"></div>' . $anchor, $out, 1);
                break;
            }
        }
    }

    if ($out === $src) { continue; }

    if (substr_count($out, 'lang="<?php echo function_exists') > substr_count($src, 'lang="<?php echo function_exists')) { $changedLang++; }
    if (substr_count($out, 'defer></script>') > substr_count($src, 'defer></script>')) { $changedDefer++; }

    if (!$dryRun) { file_put_contents($path, $out); }
    echo ($dryRun ? '[dry] ' : '[ok ] ') . substr($path, strlen($root) + 1) . "\n";
}

echo "\nFile con lang aggiunto:  $changedLang\nFile con defer aggiunto: $changedDefer\n";
