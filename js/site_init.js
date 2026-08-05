/* ============================================================
 * js/site_init.js
 * Inizializzazione condivisa del sito.
 * Sostituisce gli inline-script <script>...</script> presenti in ogni
 * pagina, in modo che la CSP possa rimuovere 'unsafe-inline' da script-src.
 *
 * INCLUDERE in <head> dopo jquery.min.js, ddsmoothmenu.js e piroBox.1_2.js:
 * <script type="text/javascript" src="../js/site_init.js"></script>
 *
 * Le funzioni che erano inline (clearText, ddsmoothmenu.init, piroBox)
 * sono qui dentro. La rimozione degli inline dal singolo file PHP è
 * un'operazione manuale: cancella i blocchi <script> inline e aggiungi
 * il riferimento a questo file.
 * ============================================================ */

/* ----------------------------------------------------
 * Helper: clearText (placeholder leggero per i campi search)
 * ---------------------------------------------------- */
window.clearText = function (field) {
  if (field.defaultValue === field.value) {
    field.value = '';
  } else if (field.value === '') {
    field.value = field.defaultValue;
  }
};

/* P3.16 - Menu senza jQuery/ddsmoothmenu: apertura via CSS (:hover/:focus-within),
 * accessibilita' (ARIA/Esc) gestita piu' sotto. Nessun init necessario. */

/* Inizializzazione piroBox (lightbox immagini). Richiede jQuery + piroBox. */
if (typeof jQuery !== 'undefined') {
  jQuery(function ($) {
    if (typeof $.fn.piroBox !== 'undefined' &&
        $('a.pirobox, a[class^="pirobox_gall"]').length > 0) {
    $().piroBox({
      my_speed:  600, bg_alpha:  0.5, radius: 4, scrollImage: false,
      pirobox_next: 'piro_next', pirobox_prev: 'piro_prev',
      close_all: '.piro_close', slideShow: 'slideshow', slideSpeed: 4
    });
    }
  });
}

/* P1.9 - Accessibilita menu: role, aria-haspopup/expanded, Esc. Apertura tastiera in CSS. */
(function () {
  var menu = document.getElementById('templatemo_menu'); if (!menu) return; menu.setAttribute('role', 'navigation');
  [].forEach.call(menu.querySelectorAll('li'), function (li) {
    var sub = li.querySelector('ul'), a = li.querySelector('a'); if (!sub || !a) return;
    a.setAttribute('aria-haspopup', 'true'); a.setAttribute('aria-expanded', 'false');
    li.addEventListener('focusin', function () { a.setAttribute('aria-expanded', 'true'); });
    li.addEventListener('focusout', function (e) { if (!li.contains(e.relatedTarget)) a.setAttribute('aria-expanded', 'false'); });
    li.addEventListener('keydown', function (e) { if (e.key === 'Escape') { a.setAttribute('aria-expanded', 'false'); a.blur(); } });
  });
})();

/* ----------------------------------------------------
 * Conferma di sicurezza prima delle azioni distruttive.
 * Aggiungere a un <form> l'attributo data-confirm="messaggio" per
 * attivare la conferma senza usare onsubmit inline.
 *
 * Esempio:
 * <form data-confirm="Delete permanently?" ...>
 * ---------------------------------------------------- */
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (!form || !form.dataset || !form.dataset.confirm) return;
  if (!window.confirm(form.dataset.confirm)) {
    e.preventDefault();
  }
});

// P0.2: pulsanti "Back" del wizard (ex onclick inline) via delega eventi.
document.addEventListener("click",function(e){var b=e.target.closest("[data-aow-wizard-back]");if(b&&b.form){b.form.wizard_step.value=b.getAttribute("data-aow-wizard-back");}});

/* P0.2/P1.10 - Fallback immagini SENZA onerror inline (CSP-safe).
 * Cattura l'errore di caricamento e sostituisce con no_image.jpg. */
document.addEventListener('error', function (e) {
  var t = e.target;
  if (t && t.tagName === 'IMG' && !t.getAttribute('data-fb')) {
    t.setAttribute('data-fb', '1'); t.src = '/images/no_image.jpg';
  }
}, true);
