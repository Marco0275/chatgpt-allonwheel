# Allonwheel v0.0.10 — Bundle Histats (consolidato + consent-gated, dir. 20)
*2026-06-13 — Histats reintrodotto correttamente. Lint PHP 8.3 OK · JS valido · CRLF (LF su cookie_consent.js).*

> `includes/histats.php` è NUOVO. Imposta il tuo ID via costante `HISTATS_ID`, env, o fallback nel partial. Senza ID il contatore non emette nulla.

## `includes/histats.php`  *(nuovo)*
```php
<?php
// ============================================================
// includes/histats.php — Contatore Histats CONSOLIDATO e consent-gated.
//
// dir. 20: il contatore Histats e' una dotazione PERMANENTE del sito e NON
// va rimosso. Qui e' implementato "al meglio":
//   - UNICO punto di inclusione (questo partial, incluso da footer.php):
//     niente piu' snippet duplicato in decine di pagine.
//   - Caricamento ASINCRONO (script async, non blocca il rendering).
//   - CONSENT-GATED: parte SOLO se l'utente ha accettato i cookie
//     'analytics' (cookie aow_consent + Consent Mode v2). Conforme GDPR.
//   - ID parametrico: nessun ID hard-coded sparso nel codice.
//
// COME CONFIGURARE L'ID:
//   - definisci la costante HISTATS_ID (es. in config/bootstrap.php), oppure
//   - imposta la variabile d'ambiente HISTATS_ID, oppure
//   - in ultima istanza scrivi l'ID nel fallback qui sotto.
// Senza ID il partial non produce nulla (no-op sicuro).
// ============================================================

$histats_id = '';
if (defined('HISTATS_ID')) {
    $histats_id = (string) HISTATS_ID;
} elseif (getenv('HISTATS_ID')) {
    $histats_id = (string) getenv('HISTATS_ID');
}
// Fallback manuale (inserisci qui il tuo ID Histats se non usi costante/env):
if ($histats_id === '') {
    $histats_id = ''; // es. '4891234'
}

// Accetta solo ID numerico (difesa input); se vuoto/non valido -> nessun output.
$histats_id = trim($histats_id);
if ($histats_id === '' || !ctype_digit($histats_id)) {
    return;
}
?>
<!-- Histats counter (dir. 20) — caricato SOLO con consenso 'analytics'. -->
<div id="histats_counter"></div>
<script>
(function () {
  'use strict';
  if (window.aowLoadHistats) { return; } // definito una sola volta
  var loaded = false;
  // Loader idempotente: inietta lo snippet async ufficiale Histats.
  window.aowLoadHistats = function () {
    if (loaded) { return; }
    loaded = true;
    window._Hasync = window._Hasync || [];
    _Hasync.push(['Histats.start', '1,<?php echo $histats_id; ?>,4,0,0,0,00010000']);
    _Hasync.push(['Histats.fasi', '1']);
    _Hasync.push(['Histats.track_hits', '']);
    var hs = document.createElement('script');
    hs.type = 'text/javascript';
    hs.async = true;
    hs.src = 'https://s10.histats.com/js15_as.js';
    (document.getElementsByTagName('head')[0] || document.body).appendChild(hs);
  };
  // Se il consenso 'analytics' e' GIA' presente (visitatore di ritorno), parte
  // subito; altrimenti restera' in attesa che cookie_consent.js lo richiami
  // al momento dell'accettazione (hook in applyConsent).
  try {
    var m = document.cookie.match(/(?:^|; )aow_consent=([^;]+)/);
    var c = m ? JSON.parse(decodeURIComponent(m[1])) : null;
    if (c && c.analytics) { window.aowLoadHistats(); }
  } catch (e) {}
})();
</script>
```

## `footer.php`
```php
<?php
// footer.php — Piè di pagina globale
$footer_base = '';
$footer_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', '_admin', 'shared'] as $folder) {
    if (strpos($footer_script, '/' . $folder . '/') !== false) {
        $footer_base = '../';
        break;
    }
}
?>
<!-- Footer -->
<div id="templatemo_bottom"><div class="col_4 col_f">
  <h5>Browse</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>browse.php">All listings</a></li>
    <li><a href="<?php echo $footer_base; ?>road_vehicles.php">Road vehicles</a></li>
    <li><a href="<?php echo $footer_base; ?>special_vehicles.php">Special vehicles</a></li>
    <li><a href="<?php echo $footer_base; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $footer_base; ?>04_request_offer/04_request_offer.php">Request a quotation</a></li>
  </ul>
</div>
<div class="col_4">
  <h5>Marketplace</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>02_free_ads/02_view_ads.php">Browse free ads</a></li>
    <li><a href="<?php echo $footer_base; ?>03_ads/03_view_ads.php">Browse premium ads</a></li>
    <li><a href="<?php echo $footer_base; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $footer_base; ?>portfolio.php">Portfolio</a></li>
    <li><a href="<?php echo $footer_base; ?>blog.php">Blog</a></li>
  </ul>
</div>
<div class="col_4">
  <h5>Useful links</h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>about.php">About us</a></li>
    <li><a href="<?php echo $footer_base; ?>what_we_do.php">What we do</a></li>
    <li><a href="<?php echo $footer_base; ?>FAQ.php">F.A.Q.</a></li>
    <li><a href="<?php echo $footer_base; ?>Conditions.php">Conditions &amp; rules</a></li>
    <li><a href="<?php echo $footer_base; ?>contact.php">Contact us</a></li>
  </ul>
</div>
<div class="col_4 col_l rmc">
  <h5>Follow us</h5>
  <ul class="footer_link">
    <li><a href="https://www.facebook.com/profile.php?id=61590545821976" class="facebook social">Facebook</a></li>
    <li><a href="https://www.instagram.com/allonwheel/" class="instagram social">Instagram</a></li>
  </ul>
</div>
<div class="cleaner"></div>
</div>
<div id="templatemo_footer">
  Copyright &copy; <?php echo date('Y'); ?> | <a href="https://www.allonwheel.com">All on Wheel Ltd.</a>
  | <a href="<?php echo $footer_base; ?>privacy.php">Privacy policy</a>
  | <a href="<?php echo $footer_base; ?>cookie-policy.php">Cookie policy</a>
</div>
</div>
<!-- End footer -->
<?php /* dir. 20: contatore Histats consolidato e consent-gated */ include __DIR__ . '/includes/histats.php'; ?>
```

## `config/security_headers.php`
```php
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

// Se headers già inviati (output già iniziato) non possiamo fare nulla
if (headers_sent($file, $line)) {
  error_log("[Allonwheel] security_headers: headers già inviati da $file:$line");
  return;
}

// Set in modalità ENFORCE (false = report-only, sicuro per il primo deploy)
if (!defined('templatemo_CSP_ENFORCE')) {
  define('templatemo_CSP_ENFORCE', false);
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
  "script-src 'self' 'unsafe-inline' https://s10.histats.com https://sstatic1.histats.com",
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data: https://sstatic1.histats.com https://s10.histats.com",
  "font-src 'self' data:",
  "connect-src 'self'",
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
```

## `cookie_banner/cookie_consent.js`
```javascript
/* cookie_banner/cookie_consent.js — gestione consenso (no dipendenze) */
(function () {
  'use strict';
  var COOKIE = 'aow_consent';
  var VERSION = '1.0';

  // Google Consent Mode v2: default tutto DENIED finche' non c'e' consenso
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('consent', 'default', {
    ad_storage: 'denied', analytics_storage: 'denied',
    ad_user_data: 'denied', ad_personalization: 'denied'
  });

  function readConsent() {
    var m = document.cookie.match(/(?:^|; )aow_consent=([^;]+)/);
    try { return m ? JSON.parse(decodeURIComponent(m[1])) : null; } catch (e) { return null; }
  }
  function writeConsent(c) {
    c.v = VERSION;
    document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(c)) +
      ';path=/;max-age=' + (60 * 60 * 24 * 180) + ';SameSite=Lax' +
      (location.protocol === 'https:' ? ';Secure' : '');
  }
  function applyConsent(c) {
    gtag('consent', 'update', {
      analytics_storage: c.analytics ? 'granted' : 'denied',
      ad_storage:        c.marketing ? 'granted' : 'denied',
      ad_user_data:      c.marketing ? 'granted' : 'denied',
      ad_personalization:c.marketing ? 'granted' : 'denied'
    });
    // Prova del consenso lato server (registro consensi)
    try {
      fetch('/cookie_banner/consent_log.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ categories: c, version: VERSION })
      });
    } catch (e) {}

    // Statistiche: carica i tracker 'analytics' SOLO col consenso (es. Histats, dir. 20).
    if (c.analytics && typeof window.aowLoadHistats === 'function') {
      window.aowLoadHistats();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var banner = document.getElementById('aow-cookie-banner');
    var manage = document.getElementById('aow-cc-manage');
    var existing = readConsent();
    if (existing) { applyConsent(existing); } else if (banner) { banner.hidden = false; }

    function close(c) { writeConsent(c); applyConsent(c); if (banner) banner.hidden = true; if (manage) manage.hidden = false; }

    var accept = document.getElementById('aow-cc-accept');
    var reject = document.getElementById('aow-cc-reject');
    var save   = document.getElementById('aow-cc-save');
    if (accept) accept.onclick = function () { close({ analytics: true,  marketing: true  }); };
    if (reject) reject.onclick = function () { close({ analytics: false, marketing: false }); };
    if (save)   save.onclick   = function () {
      close({
        analytics: !!document.getElementById('aow-cc-analytics').checked,
        marketing: !!document.getElementById('aow-cc-marketing').checked
      });
    };
    if (manage) manage.onclick = function () { if (banner) banner.hidden = false; manage.hidden = true; };
  });
})();
```
