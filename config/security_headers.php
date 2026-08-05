<?php
// ============================================================
// config/security_headers.php
// Header HTTP di sicurezza, applicati globalmente su ogni pagina.
//
// USO: includere questo file il PIÙ PRESTO POSSIBILE in ogni pagina
//  (idealmente prima di qualsiasi output). Il modo più semplice è
//  includerlo da config/bootstrap.php — così copre automaticamente
//  ogni pagina che già usa bootstrap.
//
// HEADER APPLICATI:
//  - X-Frame-Options: SAMEORIGIN — blocca clickjacking via iframe
//  - X-Content-Type-Options: nosniff — blocca MIME sniffing
//  - Referrer-Policy: strict-origin-when-cross-origin
//  - X-XSS-Protection: 1; mode=block (legacy ma innocuo)
//  - Strict-Transport-Security (HSTS) — solo se in HTTPS
//  - Content-Security-Policy (CSP) — restrittiva ma compatibile con
//  le librerie esterne usate dal sito (jQuery CDN, ddsmoothmenu, pirobox)
//  - Permissions-Policy — disattiva camera/microfono/geolocation
//
// CSP NOTE:
// La CSP è "report-only" di default per non rompere il sito in produzione
// se ci sono inline-script residui. Quando hai verificato i log che
// non ci sono violazioni, cambia templatemo_CSP_ENFORCE a true.
// ============================================================

if (defined('templatemo_SECURITY_HEADERS_LOADED')) {
  return;
}
define('templatemo_SECURITY_HEADERS_LOADED', true);

// Nonce CSP per-request: usato negli <script> inline al posto di 'unsafe-inline'.
if (!defined('AOW_CSP_NONCE')) { define('AOW_CSP_NONCE', base64_encode(random_bytes(16))); }

// Se headers già inviati (output già iniziato) non possiamo fare nulla
if (headers_sent($file, $line)) {
  error_log("[Allonwheel] security_headers: headers già inviati da $file:$line");
  return;
}

// Set in modalità ENFORCE (false = report-only, sicuro per il primo deploy)
if (!defined('templatemo_CSP_ENFORCE')) {
  define('templatemo_CSP_ENFORCE', true);
}

// ------------------------------------------------------------
// 1. Anti-clickjacking
// ------------------------------------------------------------
header('X-Frame-Options: SAMEORIGIN');

// ------------------------------------------------------------
// 2. Anti-MIME-sniffing
// ------------------------------------------------------------
header('X-Content-Type-Options: nosniff');

// ------------------------------------------------------------
// 3. Referrer policy: invia origin solo verso lo stesso schema
// ------------------------------------------------------------
header('Referrer-Policy: strict-origin-when-cross-origin');

// ------------------------------------------------------------
// 4. Legacy XSS filter (innocuo nei browser moderni)
// ------------------------------------------------------------
header('X-XSS-Protection: 1; mode=block');

// ------------------------------------------------------------
// 5. HSTS — solo su HTTPS
// ------------------------------------------------------------
$is_https = (
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
  (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

if ($is_https) {
  // 1 anno + includeSubDomains. Aggiungi 'preload' SOLO dopo aver
  // sottomesso il dominio a hstspreload.org
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ------------------------------------------------------------
// 6. Content Security Policy
// ------------------------------------------------------------
// Compatibile con le risorse esterne in uso:
//  - jQuery / ddsmoothmenu / pirobox (servite localmente da /js/)
//  - Tracker Histats reintrodotto (dir. 20): caricato SOLO col consenso 'analytics'
//    (consent-gated); host Histats consentiti in CSP qui sotto.
//  - L'init degli script (ddsmoothmenu.init, piroBox, clearText) e' stato
//  spostato in /js/site_init.js. 'unsafe-inline' in script-src resta
//  ancora necessario per i gestori inline residui (onfocus/onsubmit).
//  TODO futuro: convertire anche questi handler in delega eventi e
//  rimuovere 'unsafe-inline'.
$csp = implode('; ', [
  "default-src 'self'",
  "script-src 'self' 'unsafe-inline' https://histats.com https://*.histats.com",
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data: https://histats.com https://*.histats.com",
  "font-src 'self' data:",
  "connect-src 'self' https://histats.com https://*.histats.com",
  "frame-ancestors 'self'",
  "form-action 'self'",
  "base-uri 'self'",
  "object-src 'none'",
]);

if (templatemo_CSP_ENFORCE) {
  header('Content-Security-Policy: ' . $csp);
} else {
  // Mode "report-only": logga le violazioni nel browser console ma
  // non blocca il caricamento. Sicuro per il primo deploy.
  header('Content-Security-Policy-Report-Only: ' . $csp);
}

// ------------------------------------------------------------
// 7. Permissions-Policy: il sito non usa camera/mic/geolocation
// ------------------------------------------------------------
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');

// ------------------------------------------------------------
// 8. Rimuovi header che rivelano lo stack tecnologico
// ------------------------------------------------------------
header_remove('X-Powered-By');
header_remove('Server');
?>
