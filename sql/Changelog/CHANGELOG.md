# Allonwheel — Cluster 2 (B: H1 unico · G: noindex funnel · D2: ItemList/Breadcrumb)
Tutti i PHP: `php -l` OK, CRLF preservati. JSON-LD testato a runtime (valido).

## B — H1 unico per pagina (zero regressioni visive)
- `header.php`: il brand ora è `<h1>` SOLO se la pagina non ha un h1 proprio, altrimenti `<div>`
  (flag `$page_has_own_h1`). Aggiunta classe `.site_brand`.
- `allonwheel_style.css`: stile brand spostato da `#site_title h1` a `#site_title .site_brand`
  (8 selettori) — resa identica per entrambi i tag.
- `index.php`, `contact.php`: dichiarano `$page_has_own_h1 = true` prima dell'header
  → il loro h1 di contenuto diventa l'UNICO h1 (risolto il doppio-h1 reale).
- Le altre pagine restano invariate (brand = loro unico h1). Per dare a ognuna un h1
  DESCRITTIVO servirebbe una scelta di copy/layout: è il punto lasciato a te (vedi coda).

## G — noindex su pagine funnel/account (via X-Robots-Tag, nessun PHP toccato)
- `01_login/.htaccess` (nuovo): noindex intera area account.
- `02_free_ads/.htaccess` (nuovo): noindex creazione/anteprima; restano indicizzabili
  `02_view_ad.php` / `02_view_ads.php`.
- `03_ads/.htaccess` (append ai redirect esistenti): idem; restano `03_view_ad(s).php`,
  `03_view_tech_details.php`.
- `04_request_offer/.htaccess` (nuovo): noindex esiti/handler; resta la landing `04_request_offer.php`.
- `.htaccess` root (append): noindex handler in root (contact-success/retry, saved_search_*,
  blog_comment_save, blog_save, ad_post, contact_submit, download_doc).
- Richiede `mod_headers` (già usato dal sito).

## D2 — Rich results
- `browse.php`: JSON-LD `BreadcrumbList` (Home > Marketplace) + `ItemList` dei risultati (max 50).
- `shared/family_page.php`: `BreadcrumbList` (Home > Marketplace > Famiglia) + `ItemList`
  (copre race_trailers / hospitality / mobile_clinics / custom_projects con un solo file).

## NOTA
`index.php` in questo zip è CUMULATIVO (contiene anche lo schema del cluster 1): usa questo.

## In coda (ordine di criticità)
F) value proposition hero — MI FERMO QUI: ti propongo 2-3 varianti EN/IT/FR/DE, scegli tu.
B2) h1 descrittivo per-pagina (richiede tua scelta copy/layout, come sopra).
H) cache CSS/JS con versioning (più invasivo).
