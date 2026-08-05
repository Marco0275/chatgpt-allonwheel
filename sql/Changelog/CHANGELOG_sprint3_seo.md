# Allonwheel — P5: SEO (sitemap dinamica + robots)

Base: tua ZIP. `php -l` OK su tutto il progetto. CRLF preservati (robots.txt invariato di EOL).

## File
- **`sitemap.php`** (nuovo) — sitemap XML **dinamica** con **hreflang** per le 4 lingue
  (en/it/fr/de). Include:
  - pagine statiche principali (home, browse, directory fornitori, wanted list,
    request a quotation, road/special/shelter, about, what we do, blog, FAQ,
    conditions, contact, portfolio);
  - una voce **per macro** (`browse.php?macro=<slug>`);
  - tutti gli **annunci approvati** (premium + free) con `lastmod`;
  - tutte le **Wanted attive**;
  - i **post del blog** pubblicati.
  URL canonico = senza prefisso lingua (= en); alternate `it`/`fr`/`de` con prefisso,
  piu' `x-default`. Si rigenera ad ogni richiesta (sempre aggiornata).
- **`robots.txt`** (modificato) — la riga `Sitemap:` ora punta a
  `https://www.allonwheel.com/sitemap.php` (prima indicava `sitemap.xml`, inesistente → 404).

## Opzionale (a tua scelta, sul server)
- Se preferisci l'URL `/sitemap.xml`, aggiungi in `.htaccess`:
  `RewriteRule ^sitemap\.xml$ sitemap.php [L]`
  (e riporta `robots.txt` a `sitemap.xml`). Non l'ho fatto io per non toccare il
  routing live senza test.

## Nota sul "router SEO" con URL puliti
Gli URL pristini tipo `/marketplace/race-trailer` richiedono regole di rewrite in
`.htaccess` che si intrecciano col rewrite lingua `^(en|it|fr|de)/` gia' presente:
e' un intervento che va **testato sul server** (rischio di rompere il routing).
Te lo preparo come proposta separata di sole regole `.htaccess` quando vuoi, così
lo applichi e verifichi in un ambiente controllato. La sitemap+hreflang qui sopra
dà già il grosso del beneficio SEO senza rischi.

## Punti ancora in sospeso
- **Home `index.php`**: ancora demo motorsport — bloccata sulla tua decisione su
  immagini/copy (asset in `images/00_first/`, dir.15).
- **Traduzioni** `lang/fr.php` / `lang/de.php` (placeholder EN) — a tua cura.
- **Clean URL rewrite** (router SEO) — proposta `.htaccess` da testare sul server.
- **Conteggio views** annunci (`seller_statistics.views`) — opzionale.
