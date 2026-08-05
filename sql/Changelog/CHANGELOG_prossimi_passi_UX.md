# Allonwheel - Prossimi passi UX: implementazione completa - 2026-07-03

Un solo file. CRLF. Nessun `?v=`. PHP 8.3 lint: 778/778 OK. CSS bilanciato (479/479).
BASE: applicare DOPO (o al posto di) Allonwheel_audit_debug_css_pulsanti.zip —
il CSS qui incluso CONTIENE gia' tutte le pulizie dell'audit + i nuovi blocchi.

## Verifica preliminare (cosa era GIA' fatto — non rifatto)
- **Homepage `index.php`**: gia' riscritta (hero brand + 5 macro card + CTA,
  immagini di `images/00_first/` usate in sola lettura, dir. 15 rispettata). ✓
- **Filtri faccette lato server** in `browse.php` (`q`, `macro`, `rs[]`,
  `cond[]`, `pmin/pmax` con whitelisting): gia' funzionanti. ✓
- **Directory per famiglia** (`?macro=` + `getCompaniesByMacroKeys`): gia' attiva. ✓
- **Dashboard venditore lead-centric** (`seller_dashboard.php`: annunci + RFQ
  ricevute per azienda): gia' attiva. ✓
- **Accesso account su Home/About/Portfolio**: gia' risolto — l'header attuale
  ha la voce "Account" login-aware su tutte le pagine. ✓

## Implementato in questa consegna

### 1. Menu mobile off-canvas — CABLATO (utente)
Il CSS completo esisteva (hamburger animato → X, pannello scorrevole, overlay,
scroll-lock) ma non era mai stato collegato all'HTML (per questo l'audit lo
aveva rimosso come inutilizzato). Ora:
- **CSS reintrodotto** in coda a `allonwheel_style.css` (checkbox-hack, no JS,
  jQuery 1.3.2 non toccato; ≤860px: hamburger; ≥861px: menu ddsmoothmenu normale);
- **`header.php`**: aggiunti `input.nav_chk` (checkbox nascosto) + `label.nav_toggle`
  (hamburger) prima del menu e `label.nav_scrim` (overlay cliccabile per chiudere)
  dopo — tutti fratelli dentro `#templatemo_header`, come richiede il selettore `~`.
Sul telefono il menu ora scorre da destra con sotto-menu sempre espansi e CTA
"Request a quote" a tutta larghezza. Accessibile (focus visibile, aria-label).

### 2. Ricerca a faccette — CABLATA (utente)
Pannello "Filter listings" in cima a `sidebar_browse.php` (guardia
`isset($cond_set)` → appare SOLO su browse.php): famiglia (radio, 5 macro dal
DB), Road/Special (checkbox), condizione (checkbox sull'enum reale), prezzo
min/max, Apply/Reset. Riusa i parametri gia' whitelistati lato server e
mantiene `q`/`cat` correnti come hidden. CSS `.facets` reintrodotto.
Le chip rimovibili sopra i risultati (gia' presenti) restano il feedback attivo.

### 3. Badge "Certified supplier" sulle card annunci (aziende)
`browse.php`: aggiunto `id_user` ai due rami della UNION; una query leggera
raccoglie gli `user_id` con azienda certificata (ISO 9001/14001/45001 non
vuote, tabella `06_company`); sulla card, accanto ai badge esistenti, appare
`✓ Certified supplier` (classe esistente `.badge_approved`, tooltip ISO).
Dati reali dal DB (dir. 14), nessun nuovo CSS (dir. 8).

### 4. Conferma email al compratore sulle RFQ (utente + aziende)
`04_send_offer.php`: dopo l'esito positivo, il compratore riceve una ricevuta
("richiesta ricevuta e inoltrata ai fornitori specializzati, ti contatteranno
direttamente") con il riepilogo del suo messaggio. Best-effort in try/catch:
non blocca mai il flusso; nessun dato interno (destinatari/esiti) esposto.

### 5. Eta' del lead in `_admin/leads.php` (SLA visivo, aziende/admin)
Sotto la data di creazione: "today / N days ago"; se il lead e' ancora `new`
dopo 3+ giorni, warning `⚠ N days ago` con la classe badge esistente
`.badge_rejected` (niente stili inline, dir. 8).

## File (6)
```
allonwheel_style.css            (audit + faccette + menu mobile)
header.php                      (wiring hamburger/scrim)
sidebar_browse.php              (pannello faccette — file mantenuto A MANO:
                                 se rigeneri le sidebar con gen_sidebars.py,
                                 tieni 'browse' nello skip-set)
browse.php                      (id_user + badge Certified supplier)
04_request_offer/04_send_offer.php  (conferma compratore)
_admin/leads.php                (eta' lead + warning SLA)
```
Sovrascrivere mantenendo i percorsi.

## Test consigliati sul server
1. Telefono (o finestra <860px): hamburger in alto a destra → menu scorre,
   overlay chiude, hamburger diventa X, sotto-menu visibili.
2. `browse.php`: pannello "Filter listings" in sidebar; applica famiglia +
   condizione + prezzo → risultati e chip coerenti; Reset pulisce.
3. Card di un annuncio il cui autore ha un'azienda con almeno una ISO →
   badge verde "Certified supplier".
4. Invia una RFQ di prova → il compratore riceve la ricevuta.
5. `_admin/leads.php`: colonna data con eta'; un lead `new` vecchio di 3+
   giorni mostra il warning rosso.
