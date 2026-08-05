# Allonwheel — Audit UX/UI (v0.0.10)
*9 giu 2026 — analisi da prospettiva UX/UI, ancorata al codice reale. Vincoli rispettati: solo CSS esistente (dir. 8), jQuery 1.3.2 bloccato, UI in inglese (dir. 0), `upload`/`images` intoccabili (dir. 15), GDPR/consenso (dir. 11).*

> **Legenda priorità:** **P0** = blocca credibilità/conversione, da fare subito · **P1** = alto impatto · **P2** = medio · **P3** = rifinitura.

---

## Sintesi
Le fondamenta sono migliorate (navigazione per intento, sidebar login-aware, viewport diffuso, consenso GDPR solido). Il problema dominante non è strutturale ma di **coerenza di contenuto**: la home racconta ancora un business diverso (motorsport) da quello reale (veicoli commerciali Road/Special). Risolto quello, i guadagni maggiori vengono da ricerca/filtri, riduzione dei click e fiducia (social proof). Sotto, gli interventi in ordine di impatto.

---

## P0 — Coerenza della Home (credibilità e conversione)

**Problema.** `index.php` è ancora il template "motorsport": `<title>`/`<meta description>`/`<meta keywords>` parlano di *race transporters, hospitality trailers, motorhomes, paddock trailer*; i `post_box` sono *Racing trailers*, *Roadshow vehicles*, *Sell or rent your transporter*, con CTA `Learn more` verso `00_first/racing_trailer.php`. Il marketplace reale però è **Road** (24 categorie: ambulanze, ribaltabili, frigo, antincendio…) / **Special**.

**Perché conta.** È la prima pagina che vede l'utente (e Google): un visitatore B2B che cerca un allestimento frigorifero trova un sito di trasporti da gara e rimbalza. È anche la causa dell'errore di dominio dei prompt esterni (Gemini).

**Raccomandazione.**
1. Riscrivere `title`/`meta`/`keywords` sul dominio reale (es. *"All on Wheel — Commercial & special vehicle bodies marketplace"*).
2. Sostituire gli 8 `post_box` con i percorsi reali: **Marketplace**, **Supplier directory**, **Road vehicles**, **Special vehicles**, **Request a quotation**, **Sell on Allonwheel**.
3. CTA con verbi d'azione (vedi P2-CTA).
> ⚠️ **Blocco asset (dir. 15):** le immagini dei `post_box` stanno in `images/00_first/` (motorsport) e non sono modificabili da codice. Servono **nuove immagini commerciali** da te, oppure si usano temporaneamente immagini già presenti coerenti (es. da gallery reali). Decisione tua: testi nuovi + foto nuove insieme, evitando "copy commerciale su foto da gara".

---

## P1 — Ricerca e filtri del marketplace

**Stato.** `browse.php` ha già: ricerca testo `q` (title/description/author), filtro categoria, messaggio "no results", UNION free+premium. Buona base.

**Problemi UX.**
- Nessun **conteggio risultati** ("24 listings") né **ordinamento** (più recenti / prezzo).
- Filtri poco scopribili; manca un **"Clear filters"** evidente quando un filtro è attivo.
- I badge **Premium/Free** usano **stili inline** (`style="background:#8B6914…"`) → viola dir. 8 e crea incoerenza visiva.

**Raccomandazione (entro CSS esistente).**
- Aggiungere riga di stato: *"Showing N listings"* + select di ordinamento (`?sort=recent|price`).
- Esporre i filtri **Road/Special** e **Free/Premium** come facet accanto alla search box (riusando `.sb_list`/classi esistenti).
- Spostare i badge Premium/Free su **classi CSS esistenti** (o, se non esiste una classe adatta, su un semplice `<span class="more">`-like già presente) eliminando lo stile inline.

---

## P1 — Copertura del banner di consenso

**Problema.** `cookie_banner.php` risulta incluso **solo da `index.php`**. Un utente che atterra su una pagina profonda (link diretto, risultato Google) **non vede il banner** → niente UI di consenso fuori dalla home.

**Perché conta.** È un buco sia UX (l'utente non può gestire le preferenze) sia di conformità (il consenso va raccolto ovunque). Impatta anche **Histats** appena introdotto: senza banner sulle pagine profonde, l'analytics parte solo per chi ha già il cookie `aow_consent`.

**Raccomandazione.** Includere `cookie_banner.php` **una sola volta a livello globale** (in `footer.php` o nei due template), come già fatto per il partial Histats. Un solo punto, sito-wide.

---

## P1 — Usabilità del menu su mobile (ddsmoothmenu / jQuery 1.3.2)

**Problema.** I menu a tendina multilivello (Marketplace ▸, Suppliers ▸, About ▸) sono **hover-based** (ddsmoothmenu su jQuery 1.3.2, bloccato). Su touch l'hover non esiste: aprire i sottomenu è scomodo o impossibile.

**Raccomandazione (senza toccare jQuery).**
- Garantire che ogni **voce-padre sia anche un link cliccabile** verso una landing di sezione (Marketplace→`browse.php`, Suppliers→directory, About→`about.php`): già così nell'header attuale ✅ — quindi su mobile l'utente raggiunge comunque la sezione anche se il sottomenu non si apre.
- In aggiunta, valutare un piccolo handler `js/site_init.js` (vanilla, no jQuery) che su viewport stretto trasformi il tap sulla voce-padre in "apri sottomenu", senza nuove dipendenze.

---

## P1 — Accesso account sulle pagine senza sidebar

**Problema (punto aperto noto).** Home/About/Portfolio sono `#no_sidebar`. Con header pubblico (rev.4) e link utente solo in sidebar, **un loggato lì non ha Logout/Dashboard**.

**Raccomandazione.** Opzioni, in ordine di pulizia:
1. Dare a quelle 3 pagine una sidebar (anche minimale) quando l'utente è loggato → mostra `sidebar_user_box.php`.
2. Oppure un'eccezione mirata: una sola voce "My account" nell'header **solo se loggato** su quelle pagine.
Da decidere con te (registrato in piano §5.5).

---

## P2 — CTA orientate all'azione

**Problema.** Restano `Learn more` generici (es. in home). Le CTA generiche convertono meno di quelle specifiche.

**Raccomandazione (UI in EN, classi `.more` esistenti).**
`Browse available bodies` · `Post a free ad` · `Request a quotation` · `Find suppliers` · `Sell on Allonwheel`. Una CTA primaria per blocco, verbo + oggetto.

---

## P2 — Cross-sell Marketplace ↔ Suppliers

**Opportunità.** Chi guarda un annuncio (un certo `vtype`/macro) è un lead caldo per i fornitori di quella categoria.

**Raccomandazione.** Box **"Related suppliers"** nella pagina annuncio: query sulla Company directory filtrata per lo stesso `vtype`/`macro_category` (JOIN `06_company_products`). Solo valori reali del DB, classi `.sb_box`/`.sb_list` esistenti.

---

## P2 — Fiducia / social proof B2B

**Raccomandazione.**
- **Numeri reali** dove ci sono (es. "N suppliers", "N listings") in home/footer.
- **Loghi fornitori** in evidenza (già esiste `sidebar_company_logo.php`): portarne una fascia in home.
- Badge **"Verified supplier"** se il dato esiste a DB; altrimenti non inventare (dir. 14).

---

## P3 — Accessibilità (quick wins)

- **Logo header:** `<h1><a href="…/index.php"></a></h1>` è un link **senza testo accessibile**. Aggiungere `aria-label="All on Wheel — home"` (o testo nascosto). Nessun CSS nuovo.
- **`alt` immagini:** verificare che gallery/annunci abbiano `alt` descrittivi (molti già presenti).
- **Focus/contrasto:** controllare contrasto dei badge e stato `:focus` visibile sui link di navigazione.
- **Label form:** ogni input con `<label for>` esplicito (migliora touch e screen reader).

---

## P3 — Performance immagini

- **Lazy-loading** nativo (`loading="lazy"`) sulle immagini sotto la piega di gallery/listing: attributo HTML standard, niente JS, niente CSS, compatibile col vincolo.
- `width`/`height` sulle immagini: in gran parte già presenti (buono per evitare layout shift).

---

## Quick wins (basso sforzo, alto rapporto)
1. Riscrivere `title`/`meta` della home (P0) — minuti, grande effetto SEO/credibilità.
2. Rimuovere gli **stili inline** dei badge in `browse.php` (P1, dir. 8).
3. Includere `cookie_banner.php` **sito-wide** (P1) — un solo edit.
4. `aria-label` sul logo (P3).
5. CTA con verbi d'azione (P2).

## Vincoli ribaditi
Nessun nuovo CSS/foglio di stile (dir. 8) · jQuery 1.3.2 e ddsmoothmenu invariati · UI in inglese, commenti in italiano (dir. 0) · `upload`/`images` non modificabili da codice (dir. 15) · spostamenti fisici file solo con mappa IA approvata + redirect (dir. 19) · consenso/GDPR preservati (dir. 11) · **Histats permanente e consent-gated (dir. 20)**.

---
*Fine audit — i punti P0/P1 sono pronti per l'implementazione su tua conferma; P0 (home) richiede la tua decisione su immagini/copy.*
