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
