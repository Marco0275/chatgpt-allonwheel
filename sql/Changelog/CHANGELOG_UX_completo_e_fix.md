# Allonwheel - Prossimi passi UX (completo) + fix pulsanti/banda + admin full-width
2026-07-04. Un solo ZIP: sostituisce integralmente Allonwheel_prossimi_passi_UX.zip
(stessi 6 file, CSS aggiornato con i fix di questo giro). CRLF. Nessun `?v=`.
PHP 8.3 lint OK. CSS bilanciato (481/481).

## A) Fix di questo giro (dallo screenshot)

### 1. Pulsante .more centrato nella banda a righe oblique
Causa residua trovata nel blocco BASE del vecchio template:
`a.more, button.more { height:26px; line-height:26px; padding:0 26px 0 12px }`
- altezza fissa 26px + padding asimmetrico (26px a destra = spazio per la
  freccia ormai rimossa) -> pill piu' alto della banda e testo decentrato.
Fix:
- blocco base portato a `height:auto; line-height:1.2; padding:9px 22px`
  (simmetrico); idem il blocco gemello `a.back/button.back`;
- nella banda (`.post_box:has(.gallery.m0) .post_meta`) azzerati i margini
  verticali dei figli + `align-self:center` + `float:none` sul pulsante ->
  autore/data a sinistra e pulsante a destra SEMPRE centrati verticalmente
  dentro le righe oblique, che crescono con il contenuto.
- La freccia "\203A" resta rimossa (content:none, gia' presente): se sul sito
  live la vedi ancora, e' il segno che il CSS online e' una versione vecchia -
  questo file la elimina.

### 2. Tabella dashboard admin a tutta pagina
Causa: il RESTYLE impone `#templatemo_content{ width:calc(100% - 332px) !important }`
(spazio sidebar) che VINCEVA su `#templatemo_content.admin_full{ width:100% }`
(senza !important) -> in admin restavano 332px vuoti a destra e la tabella
(width:100% del contenitore) non riempiva la pagina.
Fix: `#templatemo_content.admin_full{ width:100% !important; float:none !important; }`
-> tutte le pagine admin (dashboard, moderazione, leads, ...) usano l'intera
larghezza; le `.admin_table` (gia' width:100%) ora riempiono davvero la pagina.
Su mobile resta lo scroll orizzontale gia' presente (<=860px).

## B) Contenuto della consegna precedente (incluso qui, invariato)
1. **Menu mobile off-canvas cablato**: CSS reintrodotto + `header.php` con
   checkbox nascosto, hamburger (diventa X), pannello che scorre da destra,
   overlay cliccabile, scroll-lock. <=860px; desktop invariato (ddsmoothmenu).
2. **Ricerca a faccette cablata**: pannello "Filter listings" in
   `sidebar_browse.php` (solo su browse): famiglia, Road/Special, condizione,
   prezzo min/max, Apply/Reset; riusa i parametri gia' whitelistati.
   NB: sidebar_browse.php e' mantenuto A MANO (skip-set in gen_sidebars.py).
3. **Badge "Certified supplier"** sulle card annunci di `browse.php` per gli
   autori con azienda certificata ISO (badge esistente `.badge_approved`).
4. **Conferma email al compratore** dopo l'invio RFQ (`04_send_offer.php`),
   best-effort, nessun dato interno esposto.
5. **Eta' del lead** in `_admin/leads.php` ("N days ago"; warning rosso con
   classe esistente se `new` da 3+ giorni).

Gia' presenti in questa base e verificati (non rifatti): homepage brand,
faccette server-side, directory per famiglia, dashboard venditore lead-centric,
voce Account login-aware nell'header su tutte le pagine.

## File (6)
allonwheel_style.css | header.php | sidebar_browse.php | browse.php |
04_request_offer/04_send_offer.php | _admin/leads.php
Sovrascrivere mantenendo i percorsi. Ordine: prima questo ZIP, nient'altro
(supera sia l'audit sia il precedente prossimi_passi_UX).

## Test rapidi
1. Card annuncio: pulsante dentro la banda a righe, centrato, senza freccia.
2. Admin dashboard: tabella a tutta pagina (niente colonna vuota a destra).
3. Telefono <860px: hamburger -> menu off-canvas.
4. browse.php: pannello "Filter listings" in sidebar.
