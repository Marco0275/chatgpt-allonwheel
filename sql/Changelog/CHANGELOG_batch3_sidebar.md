# Allonwheel — Batch 3: sidebar per-pagina (rifacimento completo)

Data: 2026-06-19 (rev.2 — vehicle types raggruppati per macro)
Richiesta (punto 5): «dimentica tutte le sidebar esistenti; creane di nuove,
una per ogni pagina, che NON contengano link gia' presenti nella stessa pagina».

## Approccio
Un **unico generatore** `include_sidebar.php` invece di ~50 file separati:

1. La sidebar e' inclusa DOPO il contenuto (`#templatemo_content` chiude prima di
   `#templatemo_sidebar`): tramite l'output buffer legge l'HTML gia' prodotto.
2. Estrae i link presenti (`href="..."`), li normalizza (basename + query,
   minuscolo) per confronto robusto a percorsi relativi/assoluti/full-URL.
3. Mostra un catalogo di link escludendo quelli gia' in pagina (header nav
   inclusa: nessun doppione tra header e sidebar).

Ogni pagina ottiene di fatto la propria sidebar, senza duplicare i propri link,
con un solo file da mantenere.

## File modificati
- **include_sidebar.php** — RISCRITTO, self-contained. Box `.sb_box` / liste
  `.sb_list` con `<h3>` (solo classi esistenti, dir.8). Gruppi:
  - **Marketplace**: Browse all + le 5 macro (da `product_macros`) + Request a
    quotation + Wanted requests.
  - **Suppliers**: directory fornitori + Road + Special.
  - **Road vehicle types** / **Special vehicle types**: due box separati, costruiti
    da `vehicle_types` raggruppando per `macro_category` (enum road/special, 24+8).
    Link filtro verso `06_company/06_30_company_directory.php?vtype=<slug>`
    (la directory accetta gia' `?vtype=`). Un tipo nuovo aggiunto dall'admin
    compare nel gruppo giusto **in automatico** (propagazione, parte del punto 6).
    Etichette tradotte via `t('vt.'.$slug, nome_DB)`.
  - **Account** (login-aware): My posts, Seller dashboard, Post free/premium,
    Wanted (post/manage), Register company, Account roles, Logout; ospite:
    Login / Register.
  - **Company**: About, What we do, Blog, FAQ, Conditions, Contact.
  Etichette via `t()/te()` con fallback inglese. PDO opzionale: senza DB i gruppi
  dinamici restano vuoti e il resto funziona.

- **header.php** — aggiunto `if (ob_get_level() === 0) { ob_start(); }` dopo il
  require dell'i18n, cosi' la sidebar puo' leggere l'HTML gia' prodotto. PHP
  svuota il buffer a fine script (nessun flush manuale). I proxy che NON includono
  header.php (download_doc.php, sitemap.php, PDF tecnico) non sono toccati.

## Perche' road/special e non le 5 macro
`vehicle_types.macro_category` e' un enum **('road','special')**: e' l'unico
raggruppamento che i dati della directory supportano. Le 5 macro brand
(`product_macros`) classificano gli ANNUNCI (02/03), non i tipi della directory
fornitori. Quindi i vehicle types si raggruppano in Road (24) / Special (8).

## Compatibilita'
- Le vecchie `sidebar_*.php` non sono piu' incluse: restano inerti su disco,
  rimuovibili in seguito (nessun riferimento residuo).
- Nessuna nuova CSS. Pagine `#no_sidebar` (Home/About/Portfolio) invariate.

## Verifiche
- `php -l` OK su entrambi; full-project `php -l`: 206 file, 0 errori.
- Esclusione testata: header `browse.php` -> "Browse all" escluso; macro mostrate;
  `?vtype=ambulanze` in pagina -> escluso dal gruppo Road, gli altri Road mostrati;
  `about.php` full-URL nell'header -> escluso.
- Regex href testata byte-esatta dal file (apici doppi/singoli + query string).

## Nota
Il box "Road vehicle types" elenca 24 voci (Special 8). E' raggruppato e
scansionabile; se preferisci accorciarlo (es. solo i tipi con fornitori attivi)
posso aggiungere un filtro per conteggio: dimmelo.
