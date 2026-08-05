# Allonwheel — Overlay consolidato SEO/perf (cluster 1+2+3)
98 file (92 PHP + 4 lang + 5 .htaccess + CSS). Tutti: `php -l` OK, CRLF preservati.
Questo overlay SOSTITUISCE i pacchetti cluster1/cluster2 (contiene tutto).
Applica sovrapponendo alla webroot mantenendo la struttura cartelle.

## Cluster 1 — igiene indicizzazione + Histats GDPR
- includes/histats.php RISCRITTO: consent-gated reale (definisce window.aowLoadHistats,
  chiamato da cookie_consent.js solo col consenso 'analytics'). Fix del fallback ID.
- Meta description uniche: about, what_we_do, FAQ, Conditions, contact, blog.
- blog_post.php: description dinamica (excerpt/body).
- canonical + hreflang aggiunti: what_we_do, professionals, blog_post.
- index.php: schema home arricchito (Organization+sameAs + WebSite/SearchAction).

## Cluster 2 — H1 unico + noindex + rich results
- B: header.php brand = h1 solo se la pagina non ha h1 proprio (flag $page_has_own_h1),
  stile su .site_brand (CSS). index.php e contact.php dichiarano il flag. Doppio-h1 risolto.
- G: noindex funnel/account via X-Robots-Tag (.htaccess: 01_login, 02_free_ads,
  03_ads[append], 04_request_offer, root[append]). Pagine di dettaglio annuncio e
  landing preventivo restano indicizzabili.
- D2: BreadcrumbList + ItemList su browse.php e shared/family_page.php (4 famiglie).

## Cluster 3 — Value proposition (C) + cache asset
- F: hero variante C in EN/IT/FR/DE (lang/*.php + default index.php).
  CTA2 ora "Request a quotation" -> pagina preventivo (prima "Find a supplier").
- H: versioning ?v=20260726 su TUTTI i riferimenti CSS/JS (566 rif., 86 file).
  .htaccess: CSS/JS ora "1 year" + Cache-Control "public, max-age=31536000, immutable".
  scripts/bump_asset_version.php: bump della versione ad ogni deploy CSS/JS.

## IMPORTANTE (H)
Con la cache "immutable", ad OGNI modifica di CSS/JS lancia:
  php scripts/bump_asset_version.php 20260901
(altrimenti i browser useranno la versione cache per 1 anno).

## NON toccato (tue scelte)
- Link social nel footer (li compili a mano).
- meta title/OG: già coerenti con il posizionamento C, lasciati invariati.

## Coda residua (facoltativa)
- h1 descrittivo per-pagina sulle editoriali (richiede copy/layout).
- server: server_tokens off in nginx; misurare CWV con Lighthouse/PSI.
