# Allonwheel — Intervento PiroBox (lightbox thumbnail)
*Data: 28 maggio 2026*

## Obiettivo
Far sì che **le thumbnail si aprano tramite PiroBox** (lightbox) su tutte le pagine,
aggiornare il codice, eseguire il debug e organizzare i file necessari nelle cartelle.

## Diagnosi (debug)
1. **Bug bloccante a livello di sito.** Il sito carica **jQuery 1.3.2**, che NON espone
   i metodi `.on()` / `.off()` (introdotti in jQuery 1.7). La build precedente di
   `js/piroBox.1_2.js` (rev. B) usava proprio `.on()`/`.off()`: a runtime lanciava
   `TypeError: ...off is not a function` e **la lightbox era completamente inattiva
   su ogni pagina**. (Verificato in test: `$(...).off()` lancia eccezione con `.off` assente.)
   - Nota: non è possibile aggiornare jQuery, perché `ddsmoothmenu.js` usa `$.browser`
     (rimosso in jQuery 1.9). La correzione è stata fatta lato plugin.
2. **Bug visivo.** Il contenitore immagine (`.c_c div`) restava fisso a 380×180 px:
   le foto venivano schiacciate o sbordavano dalla cornice.
3. **Guard incompleta** in `js/site_init.js`: rilevava solo `.pirobox` e non le gallerie
   (`pirobox_gall`).
4. **Pagine con thumbnail reali senza asset PiroBox** (CSS/JS non inclusi), o con ancora
   PiroBox presente ma libreria non caricata.

## Modifiche
| File | Intervento |
|---|---|
| `js/piroBox.1_2.js` | **Riscritto (rev. C).** Shim `_bind`/`_unbind`: usa `.on/.off` se presenti (jQuery ≥1.7), altrimenti `.bind/.unbind` (jQuery 1.3.2). Chiusura legata direttamente a overlay e bottone close (no delega via `document`, non disponibile in 1.3.2). Contenitore immagine dimensionato sulla taglia reale della foto con cap al viewport. Spinner di caricamento + gestione errore immagine. |
| `js/site_init.js` | Guard estesa: rileva sia `a.pirobox` sia `a[class^="pirobox_gall"]`. |
| `00_first/service.php` | Aggiunti CSS+JS PiroBox (l'ancora c'era ma la libreria non veniva caricata). |
| `template/full_blog_post.php` | Aggiunti CSS+JS PiroBox. |
| `06_company/06_20_modify_company.php` | Aggiunti asset + logo aziendale incapsulato in ancora PiroBox. |
| `06_company/06_14_company_gallery.php` | Galleria convertita a `pirobox_gall` (navigazione avanti/indietro tra le foto). |
| `shared/gallery.php` | Galleria annuncio convertita a `pirobox_gall` (navigazione tra le foto). |
| `blog_post.php` | Aggiunti asset + immagine dell'articolo incapsulata in ancora PiroBox. |
| `_admin/manage_companies.php` | Aggiunti asset + thumbnail logo incapsulata in ancora PiroBox. |

Le pagine di listing (es. `shared/view_ads.php`) mantengono `class="pirobox"` (apertura
singola, corretta: una thumbnail per riga). Le pagine galleria dedicate usano
`pirobox_gall` per la navigazione tra più foto della stessa scheda.

## File / cartelle PiroBox (tutti presenti e organizzati)
- Plugin: `js/piroBox.1_2.js` (+ `js/jquery.min.js`, `js/site_init.js`, `js/ddsmoothmenu.js`).
- Temi: `css_pirobox/white/`, `css_pirobox/black/`, `css_pirobox/shadow/` — verificate
  **0 immagini mancanti** rispetto agli `url()` dei rispettivi `style.css`.
  - `white` usato da 49 pagine, `black` da `portfolio.php`.
- Le cartelle `upload_image/` e `images/` non sono state modificate né svuotate.

## Verifica (doppio controllo)
- **Lint PHP:** 149/149 file senza errori di sintassi (PHP 8.3) — eseguito due volte.
- **Sintassi JS:** `piroBox.1_2.js` e `site_init.js` validi (`node --check`).
- **Coerenza asset:** ogni pagina con ancora PiroBox carica CSS+JS — 0 pagine incoerenti.
  Percorsi corretti per profondità di cartella (root vs sottocartelle).
- **Test funzionale (DOM headless):** apertura lightbox, iniezione immagine corretta,
  **navigazione galleria** ("2 of 3" → immagine corretta) e chiusura con ESC —
  PASS sia con jQuery moderno sia nell'emulazione fedele di jQuery 1.3.2 (`.on`/`.off`
  assenti, `.bind` autonomo).
