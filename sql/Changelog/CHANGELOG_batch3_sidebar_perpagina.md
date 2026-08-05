# Allonwheel — Batch 3 (rev.3): UNA sidebar dedicata per ogni pagina

Data: 2026-06-20
Richiesta: ogni pagina ha la sua `sidebar_<nomepagina>.php`; in quel file NON deve
comparire alcun link gia' presente nella pagina stessa, NE' nell'header, NE' nel
footer (le versioni precedenti ripetevano link di header/footer/pagina — corretto).

## Cosa e' cambiato rispetto al generatore unico
La versione a generatore unico leggeva l'output buffer: vedeva header e contenuto,
ma NON il footer (renderizzato dopo) -> i link del footer finivano in sidebar.
Ora ogni sidebar e' un **file fisico dedicato**, con le esclusioni calcolate su
header + footer + pagina, quindi niente piu' duplicati di nessuno dei tre.

## Come funziona
- `include_sidebar.php` e' ora un **dispatcher**: dalla pagina corrente ricava
  `sidebar_<nomepagina>.php` e lo include (es. `browse.php` -> `sidebar_browse.php`).
  Se manca, usa `sidebar_default.php`. Le pagine restano invariate: includono
  sempre lo stesso `include_sidebar.php` dentro `#templatemo_sidebar`.
- I 64 file `sidebar_<pagina>.php` + `sidebar_default.php` sono **generati** dallo
  script `gen_sidebars.py` (incluso come riferimento). Per ogni pagina lo script:
  1. legge l'HTML di header.php + footer.php + la pagina;
  2. costruisce il catalogo di link utili;
  3. scrive in sidebar solo i link NON presenti in quei tre.
- Esclusione robusta anche dei link costruiti in loop: se la pagina genera link
  `?macro=` (es. `browse.php`) o `?vtype=`, l'intero gruppo dinamico viene soppresso.

## Contenuto del catalogo (cosa puo' apparire in sidebar)
- Marketplace: le macro NON gia' linkate (header/footer ne linka 2 -> ne restano 3),
  Browse/Request/Wanted sono gia' nell'header -> esclusi.
- Suppliers: directory/Road/Special sono gia' nell'header -> di norma esclusi.
- **Road vehicle types (24)** e **Special vehicle types (8)**: filtri verso
  `06_30_company_directory.php?vtype=<slug>` (la directory accetta `?vtype=`).
  Non presenti in header/footer -> compaiono (etichette tradotte via `t('vt.'+slug)`).
- Account (login-aware): My posts, Seller dashboard, Post free/premium, Wanted
  post/manage, Register company, Account roles, Logout; ospite: Login/Register.
- Company/Help: tutti gia' nell'header/footer -> esclusi.

## File nel pacchetto
- `include_sidebar.php` (dispatcher).
- `header.php` (ripulito: rimosso l'`ob_start` introdotto nella versione precedente
  del Batch 3 — non piu' necessario).
- 64 × `sidebar_<pagina>.php` + `sidebar_default.php`.
- `gen_sidebars.py` (tool di rigenerazione — **NON caricare in webroot**, e' un
  utility da eseguire in locale; va aggiornato il percorso progetto/dump).

## Verifiche
- Full-project `php -l`: 261 file, 0 errori (inclusi i 65 nuovi).
- Esclusioni testate: sidebar_default non contiene about/blog/FAQ/Conditions/contact/
  what_we_do/browse(plain)/directory(plain)/road/special/04_request_offer/wanted_list/
  macro hospitality+race-trailer; `sidebar_browse` non ripete i macro (soppressi).
- CRLF preservati su tutti i file generati.

## Vecchi file sidebar ora OBSOLETI (puoi eliminarli dal server)
`sidebar_blog.php`, `sidebar_logged.php`, `sidebar_static.php`, `sidebar_marketplace.php`,
`sidebar_suppliers.php`, `sidebar_special.php`, `sidebar_account.php`,
`sidebar_user_box.php`, `sidebar_company_logo.php`, `sidebar.php` (se presenti).
Il nuovo `sidebar_default.php` sovrascrive il vecchio. Nessuno di questi e' piu'
referenziato dal dispatcher.

## Due note (decisione tua)
1. **Lunghezza**: con i 32 vehicle types ogni sidebar arriva a ~46 voci. Se le vuoi
   solo sulle pagine Marketplace/Suppliers (non su login/error/preview), lo limito.
2. **Propagazione**: i tipi sono "congelati" alla generazione. Quando aggiungi un
   tipo da admin, rilancia `gen_sidebars.py` per aggiornarli ovunque (la
   propagazione automatica live su browse/Hero resta argomento del Batch 2).
