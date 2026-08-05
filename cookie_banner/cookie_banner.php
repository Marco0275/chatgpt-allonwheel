<?php
// ============================================================
// cookie_banner/cookie_banner.php
// Banner cookie conforme alle Linee guida del Garante (2021/2025).
// Da includere PRIMA della </body> (es. in index.php o footer.php).
//
// Principi implementati:
//  - Blocco PREVENTIVO: nessuno script non tecnico parte senza consenso.
//  - Granularita': categorie separate (analytics / marketing).
//  - Parita' delle scelte: "Accept all" e "Reject all" con pari evidenza.
//  - Revoca: pulsante "Cookie preferences" sempre disponibile.
//  - Prova del consenso: POST a /cookie_banner/consent_log.php.
//  - Google Consent Mode v2: default 'denied', update al consenso.
//
// NB: testo UI in inglese (lingua del sito). Stile scoped in questo file
//     (non modifica il foglio di stile globale del sito).
// ============================================================
?>
<!-- Cookie consent (Garante 2025) -->
<script src="/js/cookie_consent.js" defer></script>

<div id="aow-cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie consent" hidden>
  <div class="aow-cc-body">
    <p>We use technical cookies that are necessary for the site to work. With your
       consent we will also use analytics and marketing cookies. You can accept them,
       reject them, or choose which ones to enable. See our
       <a href="/cookie-policy.php">Cookie Policy</a>.</p>
    <div class="aow-cc-cats">
      <label><input type="checkbox" checked disabled> Technical (always on)</label>
      <label><input type="checkbox" id="aow-cc-analytics"> Analytics</label>
      <label><input type="checkbox" id="aow-cc-marketing"> Marketing</label>
    </div>
    <div class="aow-cc-actions">
      <button type="button" id="aow-cc-reject">Reject all</button>
      <button type="button" id="aow-cc-save">Save choices</button>
      <button type="button" id="aow-cc-accept">Accept all</button>
    </div>
  </div>
</div>
<button type="button" id="aow-cc-manage" hidden>Cookie preferences</button>
