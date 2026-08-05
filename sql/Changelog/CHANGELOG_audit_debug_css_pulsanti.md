# Allonwheel — Audit completo: debug, CSS, pulsanti, prossimi passi UX
2026-07-03. Un solo file. CRLF preservato. PHP 8.3 lint: 778/778 file OK.
Nessun `?v=` sul CSS.

---

## 1. DEBUG — 5 bug reali trovati e corretti

Metodo: lint PHP su tutti i 778 file, scansione di tutti i `require/include`
(660, verificati uno a uno), scansione SQLi/XSS diretti da input (0 trovati:
le query passano tutte da prepared statement, gli output sono tutti escapati),
scansione CSRF sui form POST, scansione link `href` interni, scansione ID
duplicati nello stesso file.

1. **`contact-retry.php` (root) — include rotto.**
   `include __DIR__ . 'include_sidebar.php'` mancava lo slash iniziale: il path
   risultante non esiste, quindi la sidebar spariva silenziosamente. Pagina
   raggiunta da `contact.php` e da `register_ok_noemail.php` quando l'invio del
   modulo di contatto fallisce la validazione. **Fix:** aggiunto lo slash.

2. **`01_login/already_registered.php` — form di login senza token CSRF (bug
   bloccante).** Il form POST verso `login.php` non chiamava `csrf_generate()`;
   `login.php` chiama pero' `csrf_verify()` in modo incondizionato, che **rigetta
   con HTTP 403** se il token manca. Risultato: un utente che si registra con
   un'email gia' esistente arriva su questa pagina e, se prova a fare login da
   li', riceve un errore bloccante. **Fix:** aggiunto `csrf_generate()` nel form.

3. **`01_login/register_ok_noemail.php` — modulo di contatto legacy morto.**
   Un `<form>` residuo del vecchio template puntava a `../submit.php`
   (**inesistente, 404 garantito**), usava un JS `controlloForm()` non piu'
   definito, e non aveva CSRF. **Fix:** sostituito con un messaggio chiaro +
   link funzionante alla pagina di contatto reale (`contact.php`, che ha gia'
   CSRF e invio funzionante).

4. **`forgot_password.php`/`reset_password.php` — reset password senza CSRF.**
   I relativi handler (`send_reset_link.php`, `save_new_password.php`) non
   verificavano affatto il token CSRF: gap di conformita' sulla dir. 11 in un
   flusso sensibile (cambio password). **Fix:** aggiunto `csrf_generate()` nei
   due form e `csrf_verify()` in entrambi gli handler.

5. **`03_ads/03_modify_tech_details.php` — pagina di fallback senza CSS.**
   Il ramo "No technical details yet" (mostrato al venditore quando un annuncio
   premium non ha ancora la scheda tecnica) costruisce il proprio HTML minimo e
   collegava **solo** il CSS di piroBox, non `allonwheel_style.css` ne'
   `ddsmoothmenu.css`: la pagina appariva completamente senza stile (niente
   font/colori/spaziature del sito). **Fix:** aggiunti i due link CSS mancanti,
   nello stesso ordine usato dal resto del sito.

### Verificato ma NON un bug (falsi positivi esclusi con verifica manuale)
- I 3 `require` "mancanti" in `libs/mpdf/` sono percorsi dati opzionali della
  libreria mPDF (caricati solo se servono, comportamento normale del vendor).
- Gli ID duplicati apparenti in `03_modify_tech_details.php` sono in due rami
  PHP mutuamente esclusivi (uno termina con `exit;`): mai presenti insieme nel
  DOM reso.
- I redirect-stub `06_new_company.php`, `01_login/not_registered.php`,
  `ad_post.php` (nessun CSS) sono corretti cosi': redirect 301 istantanei,
  l'HTML minimo si vede solo se il redirect fallisce.
- Gli 11 file in `00_first/` con lo stesso pattern sono le pagine legacy
  motorsport gia' segnalate nel piano come "P1: da rimuovere con redirect 301"
  — non toccate qui, task separato gia' noto.

---

## 2. CSS — pulizia regole inutilizzate

Metodo: estratti tutti i selettori `.classe`/`#id` dal CSS, confrontati con
**match esatto** (parola intera in `class="..."`, non sottostringa — la prima
passata aveva un falso negativo: `.chip` risultava "usato" solo perche'
sottostringa di `filter_chip`) contro tutto il codice PHP/JS del sito.

**469 → 444 regole (-25), 58.220 → 55.736 byte (-2.484, -4,3%).** Graffe
bilanciate, nessuna regola vuota, nessun altro selettore inutilizzato residuo
(verificato con un secondo giro dopo la pulizia).

Rimosso (mai referenziato in nessun file HTML/PHP del sito):
- **Ricerca a faccette** (`.facets`, `.fac_grp`, `.fac_h`, `.fac_row`,
  `.fac_price`, `.fac_actions`, `.fac_reset`, 8 regole): implementazione CSS
  completa (checkbox, prezzo min/max, azioni) ma **mai collegata a nessun HTML**
  — `sidebar_browse.php` non la usa.
- **Menu mobile off-canvas** (`.nav_toggle`, `.nav_chk`, `.nav_scrim` e le loro
  varianti nei media query, 12 regole): implementazione **completa e curata**
  — hamburger animato che diventa "X", overlay scuro, blocco scroll — basata
  sul pattern checkbox-hack, ma **mai collegata a `header.php`**, che oggi usa
  solo ddsmoothmenu (pensato per hover, non ottimale al tocco).
- **`.macro_filter`/`.chip`** (5 regole): chip-bar superata da `.filter_chip`,
  gia' in uso su `browse.php` e `06_02_view_company.php`.

Verificati e **mantenuti** (falsi positivi, usati dinamicamente o da libreria):
`admin_row_rejected`/`badge_pending`/`badge_rejected` (costruite in PHP da
variabile), `commentbox1`/`commentbox2` (idem), `downarrowclass`/
`rightarrowclass` (iniettate da `ddsmoothmenu.js`), `.selected` (iniettata
dalla stessa libreria sulla voce di menu attiva), `back` (usata in `about.php`).

> Nota: le faccette e il menu off-canvas erano lavoro gia' pronto e di buona
> qualita', solo mai cablato. Vedi §4 — sono la mia prima raccomandazione per
> i prossimi passi, non vanno "rifatte da zero" se deciderai di riattivarle.

---

## 3. PULSANTI — uniformita' (esclusi login/register e chi ha gia' il loro stile)

Inventario di **tutti** i `<button>`/`<input type=submit|button|reset>` del
sito (778 file). Il sistema era gia' quasi interamente uniforme grazie alle
sessioni precedenti (Login/Reset/Submit → `.more`; area `_admin/` al 97% su
`.more`/`.submit_btn`). Trovate e corrette le uniche 5 eccezioni reali:

- **4× bottone "Delete" nudo** (icona cestino, nessuna classe) nei wizard di
  upload galleria (`02_free_ads/02_insert_ad_gallery.php`,
  `02_free_ads/02_modify_insert_ad_gallery.php`,
  `03_ads/03_insert_ad_gallery.php`, `03_ads/03_modify_insert_ad_gallery.php`):
  applicata la classe **`.btn_del`**, gia' esistente nel CSS e gia' in uso in
  `06_company/06_14_company_gallery.php` per la stessa identica azione — nessun
  nuovo CSS (dir. 8 rispettata), solo riuso dello stile "distruttivo" corretto.
- **1× bottone "Update" nudo** in `_admin/leads.php`: applicata la classe
  `.more`, coerente con tutti gli altri bottoni azione dell'area admin.

**Non toccati (per progetto, non per svista):**
- `.more`/`.submit_btn` (Login, Register, Reset, Submit ovunque) — stile di
  riferimento, invariato come richiesto.
- `.more.btn_accent` (CTA primaria hero, es. "Browse the marketplace") e
  `.btn_ghost` (CTA secondaria hero, es. "Find a supplier") — coppia
  intenzionale gia' coerente al suo interno: gerarchia primaria/secondaria
  voluta sull'hero scuro, non un'inconsistenza da correggere.
- `#searchbutton` (icona lente di ricerca) — stilizzato via ID dedicato, non
  e' un CTA testuale, corretto cosi'.
- Bottoni del cookie banner (`#aow-cc-*`) — gia' stilizzati via ID dedicato in
  `#aow-cookie-banner .aow-cc-actions button`.

---

## 4. PROSSIMI PASSI — proposte di usabilita'

### Lato utente (compratori/visitatori)
1. **Attivare il menu mobile off-canvas** (CSS pronto, rimosso in §2 perche'
   inutilizzato): bastano poche righe in `header.php` (checkbox toggle +
   hamburger + overlay) per avere un menu a scomparsa vero al posto del solo
   ddsmoothmenu, pensato per il mouse. Impatto diretto sull'usabilita' mobile,
   di cui ti sei occupato di recente.
2. **Attivare la ricerca a faccette** (CSS pronto, rimosso in §2): filtri
   Road/Special, condizione, prezzo min/max come pannello reale in
   `sidebar_browse.php`, sopra i filtri `?rs[]=`/`?cond[]=`/`?pmin=`/`?pmax=`
   gia' funzionanti lato server in `browse.php`.
3. **Homepage**: ancora il contenuto motorsport del template originale (P0 gia'
   segnalato, in attesa della tua decisione su immagini/copy).
4. **Accesso account sulle pagine senza sidebar** (Home/About/Portfolio): da
   loggato non c'e' modo diretto di raggiungere logout/dashboard da li' (P1
   gia' aperto).
5. Persistenza dei filtri di ricerca / breadcrumb sulle pagine di listing piu'
   profonde.

### Lato aziende (fornitori)
6. **Analytics per il venditore**: oggi il fornitore non vede quante visite ha
   il suo profilo ne' quante RFQ ha ricevuto (lo vede solo l'admin in
   `_admin/leads.php`) — una vista riepilogativa in `seller_dashboard.php`
   aumenterebbe il valore percepito dell'account premium.
7. **Filtro directory per le 5 famiglie di brand** (race-trailer, hospitality,
   mobile-clinic, shelter-container, custom-projects): oggi la directory
   fornitori filtra solo su Road/Special/vtype; estendere il filtro alle macro
   di brand (gia' pianificato come P2 nel piano di lavoro).
8. **Badge di certificazione piu' visibili**: oggi il badge "Certified" compare
   solo nella directory; mostrarlo anche sulle card degli annunci premium
   rinforzerebbe la fiducia nel punto in cui il compratore decide.
9. **Notifica/SLA sulle RFQ**: conferma email al compratore quando un fornitore
   risponde, e tempo di risposta atteso visibile in `_admin/leads.php`.

Nessuna di queste e' stata implementata in questa consegna: sono proposte.
Dimmi quali priorizzare e procedo un blocco alla volta.

---

## File in questa consegna (14 PHP + 1 CSS)
```
allonwheel_style.css
contact-retry.php
01_login/already_registered.php
01_login/register_ok_noemail.php
01_login/forgot_password.php
01_login/send_reset_link.php
01_login/reset_password.php
01_login/save_new_password.php
03_ads/03_modify_tech_details.php
03_ads/03_insert_ad_gallery.php
03_ads/03_modify_insert_ad_gallery.php
02_free_ads/02_insert_ad_gallery.php
02_free_ads/02_modify_insert_ad_gallery.php
_admin/leads.php
```
Sovrascrivere mantenendo i percorsi indicati.
