<?php
// ============================================================
// includes/aow_media.php — immagini: fallback, WebP, srcset.
//
// Perche' esiste (27 lug 2026):
//  1) L'hero della home veniva letto da site_settings e stampato senza
//     verificare che il file esistesse davvero. In produzione il file
//     upload_image/hero/hero-1784972590.png non e' mai stato caricato:
//     la home apriva su un blocco vuoto (404 sull'immagine di apertura).
//     aow_img_src() verifica su disco e ricade su un asset garantito.
//  2) Gli asset sono PNG da 2-2,6 MB serviti a tutta pagina. aow_picture()
//     usa la variante .webp quando esiste, con fallback trasparente al
//     file originale: nessuna immagine si rompe se la conversione non e'
//     ancora stata eseguita.
//
// Nessuna dipendenza: si puo' includere ovunque.
// ============================================================

if (!function_exists('aow_doc_root')) {
    /** Radice del sito su disco (funziona anche da CLI/cron). */
    function aow_doc_root(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($root === '' || !is_dir($root)) { $root = dirname(__DIR__); }
        return rtrim($root, '/');
    }
}

if (!function_exists('aow_file_exists')) {
    /** L'asset (path relativo alla root del sito) esiste su disco? */
    function aow_file_exists(string $rel): bool
    {
        $rel = ltrim(trim($rel), '/');
        if ($rel === '' || strpos($rel, '..') !== false) { return false; }
        return is_file(aow_doc_root() . '/' . $rel);
    }
}

if (!function_exists('aow_img_src')) {
    /**
     * Restituisce il primo path esistente tra quelli passati.
     * Se nessuno esiste restituisce l'ultimo (che deve essere un asset
     * versionato nel repository, quindi sempre presente).
     */
    function aow_img_src(string $preferred, string ...$fallbacks): string
    {
        $candidates = array_merge([$preferred], $fallbacks);
        foreach ($candidates as $c) {
            $c = ltrim(trim((string)$c), '/');
            if ($c !== '' && aow_file_exists($c)) { return $c; }
        }
        return ltrim((string)end($candidates), '/');
    }
}

if (!function_exists('aow_webp')) {
    /** Variante .webp dell'asset se esiste su disco, altrimenti ''. */
    function aow_webp(string $rel): string
    {
        $rel  = ltrim(trim($rel), '/');
        $webp = preg_replace('/\.(png|jpe?g)$/i', '.webp', $rel);
        if ($webp === null || $webp === $rel) { return ''; }
        return aow_file_exists($webp) ? $webp : '';
    }
}

if (!function_exists('aow_css_bg')) {
    /**
     * Valore per background-image: preferisce la webp quando disponibile.
     * Gia' pronto per l'attributo style (le virgolette sono escapate).
     */
    function aow_css_bg(string $rel, string $fallback = 'images/00_first/race_trailer.jpg'): string
    {
        $src  = aow_img_src($rel, $fallback);
        $webp = aow_webp($src);
        return htmlspecialchars($webp !== '' ? $webp : $src, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('aow_picture')) {
    /**
     * <picture> con sorgente WebP + fallback originale, lazy e dimensioni
     * esplicite (evita il layout shift, che Google misura come CLS).
     *
     * @param array{class?:string,loading?:string,width?:int,height?:int,sizes?:string} $opt
     */
    function aow_picture(string $rel, string $alt, array $opt = []): string
    {
        $src = aow_img_src($rel);
        if ($src === '') { return ''; }
        $webp    = aow_webp($src);
        $class   = isset($opt['class'])   ? ' class="' . htmlspecialchars($opt['class'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $loading = ($opt['loading'] ?? 'lazy') === 'eager' ? 'eager' : 'lazy';
        $dim     = '';
        if (!empty($opt['width']))  { $dim .= ' width="'  . (int)$opt['width']  . '"'; }
        if (!empty($opt['height'])) { $dim .= ' height="' . (int)$opt['height'] . '"'; }
        if ($dim === '') {
            $size = @getimagesize(aow_doc_root() . '/' . $src);
            if (is_array($size)) { $dim = ' width="' . (int)$size[0] . '" height="' . (int)$size[1] . '"'; }
        }
        $sizes = isset($opt['sizes']) ? ' sizes="' . htmlspecialchars($opt['sizes'], ENT_QUOTES, 'UTF-8') . '"' : '';

        $img = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
             . ' alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"'
             . $class . $dim . $sizes
             . ' loading="' . $loading . '" decoding="async" />';

        if ($webp === '') { return $img; }

        return '<picture><source type="image/webp" srcset="' . htmlspecialchars($webp, ENT_QUOTES, 'UTF-8') . '" />' . $img . '</picture>';
    }
}
