<?php
// ============================================================
// config/i18n.php - Fondamenta internazionalizzazione (architettura /en/).
//
// Architettura scelta: SOTTOCARTELLA con rewrite (/en/, /it/).
//  - Default (nessun prefisso) = inglese (la UI nasce in EN, dir. 0).
//  - .htaccess riscrive /en/... e /it/... sul file reale e passa la
//    locale nell'env AOW_LOCALE; qui la leggiamo e validiamo.
//
// API per le pagine (adozione INCREMENTALE, nessuna pagina e' obbligata):
//   t('chiave', 'Default EN')   -> stringa tradotta (o il default se assente)
//   aow_locale()                -> 'en' | 'it'
//   aow_locale_url('browse.php')-> URL con prefisso locale corretto
//   aow_hreflang_tags()         -> <link rel="alternate" hreflang=...> per il <head>
// ============================================================

if (!defined('AOW_I18N_LOADED')) {
define('AOW_I18N_LOADED', true);

// Locale supportate; la prima e' il default.
$GLOBALS['AOW_LOCALES'] = ['en', 'it', 'fr', 'de'];

/** Locale attiva, derivata dal prefisso URL (env AOW_LOCALE da .htaccess). */
function aow_locale(): string {
    static $loc = null;
    if ($loc !== null) return $loc;
    // Nota Apache: dopo una rewrite interna la env puo' arrivare prefissata
    // con REDIRECT_ — senza questo fallback /it/... servirebbe l'inglese.
    $cand = $_SERVER['AOW_LOCALE']
        ?? $_SERVER['REDIRECT_AOW_LOCALE']
        ?? $_SERVER['REDIRECT_REDIRECT_AOW_LOCALE']
        ?? (getenv('AOW_LOCALE') ?: '');
    $cand = strtolower(trim((string)$cand));
    $loc = in_array($cand, $GLOBALS['AOW_LOCALES'], true) ? $cand : $GLOBALS['AOW_LOCALES'][0];
    return $loc;
}

/** Carica (una volta) il dizionario della locale attiva. */
function aow_dict(): array {
    static $d = null;
    if ($d !== null) return $d;
    $loc  = aow_locale();
    $file = __DIR__ . '/../lang/' . $loc . '.php';
    $d = is_file($file) ? (array)include $file : [];
    return $d;
}

/** Traduce una chiave; se assente ritorna $default (o la chiave). */
function t(string $key, string $default = ''): string {
    $d = aow_dict();
    return $d[$key] ?? ($default !== '' ? $default : $key);
}

/** Alias comodo. */
function __(string $key, string $default = ''): string { return t($key, $default); }

/** Campo DB nella locale attiva: <base>_it se IT e valorizzato, altrimenti <base>. */
function aow_i18n_field(array $row, string $base): string {
    if (aow_locale() === 'it') {
        $it = trim((string)($row[$base . '_it'] ?? ''));
        if ($it !== '') { return $it; }
    }
    return (string)($row[$base] ?? '');
}

/** Prefisso URL della locale: '' per il default (en), '/it' per le altre. */
function aow_locale_prefix(?string $loc = null): string {
    $loc = $loc ?? aow_locale();
    return ($loc === $GLOBALS['AOW_LOCALES'][0]) ? '' : '/' . $loc;
}

/** Costruisce un URL (relativo alla root) col prefisso locale corrente. */
function aow_locale_url(string $path, ?string $loc = null): string {
    $path = '/' . ltrim($path, '/');
    return aow_locale_prefix($loc) . $path;
}

/** Tag hreflang per la pagina corrente (da inserire nel <head>). */
function aow_hreflang_tags(): string {
    $base   = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/'; // path reale (senza prefisso locale)
    $out = '';
    foreach ($GLOBALS['AOW_LOCALES'] as $loc) {
        $href = $base . aow_locale_prefix($loc) . $script;
        $out .= '<link rel="alternate" hreflang="' . $loc . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '" />' . "\n";
    }
    $out .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($base . $script, ENT_QUOTES) . '" />' . "\n";
    return $out;
}

/** Echo + escape: stampa la traduzione gia' resa sicura per HTML. */
function te(string $key, string $default = ''): void {
    echo htmlspecialchars(t($key, $default), ENT_QUOTES, 'UTF-8');
}

/** Etichetta categoria (vtype) localizzata per chiave stabile. */
function tcat(string $key, string $fallback = ''): string {
    return t('vtype.' . $key, $fallback);
}

/** Etichetta servizio accessorio localizzata per chiave stabile. */
function tsvc(string $key, string $fallback = ''): string {
    return t('svc.' . $key, $fallback);
}

/** Switcher di lingua per la pagina corrente (EN | IT). */
function aow_lang_switcher(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $qs  = (string)($_SERVER['QUERY_STRING'] ?? '');
    $qs  = $qs !== '' ? '?' . $qs : '';
    $cur = aow_locale();
    $lbl = ['en' => 'EN', 'it' => 'IT'];
    $out = [];
    foreach ($GLOBALS['AOW_LOCALES'] as $loc) {
        $name = $lbl[$loc] ?? strtoupper($loc);
        if ($loc === $cur) {
            $out[] = '<strong>' . $name . '</strong>';
        } else {
            $href = aow_locale_prefix($loc) . $script . $qs;
            $out[] = '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . $name . '</a>';
        }
    }
    return implode(' | ', $out);
}

} // AOW_I18N_LOADED
