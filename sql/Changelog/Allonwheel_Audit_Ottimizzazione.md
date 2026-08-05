# Allonwheel — Audit & proposte di ottimizzazione
*v0.0.7 · 177 file PHP · 6 giu 2026*

## Stato generale: BUONO
0 errori di sintassi. Nessuna SQLi/XSS da input grezzo. Prepared statement ovunque, `password_verify`, sessione hardened (secure/httponly/samesite), security headers completi, indici DB su status/macro/FK. **Nessuna criticità bloccante**: sotto, miglioramenti per priorità.

---

## P1 — Sicurezza / dati (alta)

1. **Credenziali esposte in chat (DB + mail).** Le password reali sono state condivise: vanno **ruotate** (DB `allonwhe80316`, mail `noreply@`). Il `.env` resta corretto (fuori webroot).
2. **`CRON_TOKEN` = password del DB.** Pessimo: il token cron viaggia via HTTP. Sostituiscilo con un valore casuale dedicato (`php -r "echo bin2hex(random_bytes(24));"`), diverso da qualsiasi password. Preferire header `X-Cron-Token` a `?token=` (niente token nei log).
3. **CSP con `'unsafe-inline'`** in `script-src`/`style-src`. È il prezzo dei 90 file con JS inline (`ddsmoothmenu.init`) e dei 12 con `onclick/onsubmit`. Spostando JS/handler in `/js/site_init.js` si può togliere `unsafe-inline` → CSP davvero efficace contro XSS.

## P2 — Performance (media)

4. **Nessun `.htaccess` di root**: manca gzip/deflate + cache headers. Aggiungerne uno con `mod_deflate` (HTML/CSS/JS) ed `Expires`/`Cache-Control` per asset statici (immagini, css, js): forte riduzione TTFB e banda. *(File pronto sotto.)*
5. **43 `<img>` senza `width`/`height`** → layout shift (CLS) e penalità Core Web Vitals. Aggiungere dimensioni esplicite.
6. **0 immagini con `loading="lazy"`**. Aggiungerlo alle gallery e alle liste annunci: meno richieste al primo paint.
7. **33 `SELECT *`** (soprattutto `03_ads`/`02_free_ads`): selezionare solo le colonne usate riduce I/O e memoria, specie sulle liste.
8. **Query in loop in `06_company.class.php`** (`getProducts/getServices` chiamate per-azienda nelle directory): potenziale N+1 sulle pagine elenco fornitori. Valutare una JOIN/`IN(...)` unica.
9. **`ORDER BY RAND()` in `sidebar_company_logo.php`**: costoso su tabelle grandi. Sostituire con selezione random via `id` (es. offset casuale).

## P3 — SEO / qualità (media-bassa)

10. **`robots.txt` e `sitemap.xml` assenti.** Aggiungerli (sitemap delle pagine pubbliche + `Disallow: /_admin/`, `/scripts/`, `/config/`).
11. **79 pagine pubbliche senza `<meta viewport>`** → resa mobile compromessa. Aggiungere il meta (solo tag, nessuno stile).
12. **Footer: social LinkedIn/YouTube/Vimeo con `href="#"`** (placeholder): mettere URL reali o rimuovere i link.
13. **File legacy `ads.php` / `ad_post.php`**: redirect 301 a `browse.php` + `noindex` (evita contenuti duplicati/orfani).

## P4 — Debito tecnico (bassa, già noto)

14. **Histats inline (1 file)**, **`ddsmoothmenu.init` inline (90)**, **`clearText` (65)** → consolidare in `js/site_init.js` (abilita anche il punto 3).
15. **49 occorrenze di chiavi sessione legacy** (`session_id_user`, ecc.): completare la migrazione a `session_helper` per un'unica API.
16. **`session_start()` solo in `csrf.php`/`session_helper.php`**: spostarlo in `bootstrap.php` evita il bug già visto (pagine che dimenticano la sessione → redirect/login). **Fix preventivo consigliato.**
17. 4 usi di `@` (soppressione errori) e 1 TODO: ripulire.

---

## Quick wins (basso sforzo, alto impatto)
- `.htaccess` root con gzip + cache (P2.4) → *incluso nel pacchetto*.
- `session_start()` in `bootstrap.php` (P4.16) → elimina un'intera classe di bug.
- `robots.txt` (P3.10) → *incluso*.
- `CRON_TOKEN` casuale dedicato (P1.2).

## Note di metodo
Tutte le proposte rispettano i vincoli del progetto: nessuna modifica a `upload`/`images`, solo foglio di stile esistente, jQuery 1.3.2 (lo shim resta), CRLF. Posso implementare per fasi (consiglio: P1.2 + P4.16 + P2.4 come primo blocco).
