# Allonwheel - M1: SEO tecnico e indicizzabilita' - 2026-07-06

Primo blocco del piano v1.1 (5 miglioramenti pre-lancio). Un solo ZIP. CRLF.
Nessun `?v=`. PHP 8.3 lint OK su tutti i file toccati.

## Gia' presente in V_1_5 (verificato, NON rifatto)
- 11/11 pagine `00_first/*` = stub redirect **301** verso le pagine reali ✓
- `sitemap.php` dinamica (statiche + macro + annunci approved + fornitori) con
  hreflang, e rewrite `.htaccess` `sitemap.xml -> sitemap.php` ✓
- `robots.txt` corretto (Disallow admin/config/upload + riga Sitemap) ✓
- Nessun link di navigazione residuo a `00_first` (solo commenti/array) ✓
- Title unici per template ✓ · hreflang gia' emesso su index e browse ✓

## Fatto in questo blocco

### 1. Nuovo partial `includes/seo_head.php`
Un punto unico che emette: **canonical assoluto** (da `$seo_canonical`),
**hreflang** (riusa `aow_hreflang_tags()` di i18n.php) e **JSON-LD** opzionale
(da `$seo_jsonld`). Robusto: self-require di bootstrap/i18n, nessun output se
mancano i prerequisiti - non puo' rompere una pagina.

### 2. Canonical + hreflang sulle pagine indicizzabili (8 innesti)
- `road_vehicles.php` / `special_vehicles.php`: canonical che CONSERVA il
  `?vtype=` validato (le pagine-tipo sono landing indicizzabili) e SCARTA
  qualsiasi altro parametro.
- `06_company/06_30_company_directory.php`: canonical con `?macro=` whitelistato.
- `portfolio.php`, `about.php`, `blog.php`: canonical self.
- `browse.php`: canonical che conserva solo `?macro=` (i filtri q/rs/cond/
  pmin/pmax NON generano varianti indicizzabili -> niente duplicazione).
- `shared/view_ad.php`: canonical sull'URL reale del wrapper
  (`02_.../02_view_ad.php?id_ads=N` o `03_...`), solo parametro id.

### 3. JSON-LD schema.org (3 innesti)
- `index.php`: **Organization** (nome, url, logo).
- `shared/view_ad.php`: **Product** (nome, descrizione 300c, immagine
  originale) + **Offer** SOLO se il prezzo e' > 0 (price/priceCurrency EUR,
  availability, itemCondition da `conditions`); niente prezzo inventato per i
  "Price on request" (dir. 14).
- `06_company/06_02_view_company.php`: **LocalBusiness** (ragione sociale).

### 4. Fix i18n per hreflang veritieri (`config/i18n.php`)
Su Apache, dopo una rewrite interna la variabile env arriva spesso come
`REDIRECT_AOW_LOCALE`: senza fallback, gli URL `/it/...` avrebbero servito
l'INGLESE (e gli hreflang appena emessi sarebbero stati falsi). Aggiunto il
fallback `REDIRECT_AOW_LOCALE` / `REDIRECT_REDIRECT_AOW_LOCALE` in
`aow_locale()`.

## Compiti lato server (per completare M1 - non richiedono codice)
1. Carica questi file, poi verifica: `https://www.allonwheel.com/sitemap.xml`
   deve mostrare l'XML dinamico.
2. **Google Search Console**: aggiungi la proprieta' (se manca) e invia
   `sitemap.xml`.
3. Test rapidi: `https://validator.schema.org` su una scheda annuncio e sulla
   home; una pagina `/it/browse.php` deve mostrare la UI in italiano.
4. Facolt.: Bing Webmaster Tools con la stessa sitemap.

## File (12)
includes/seo_head.php (NUOVO) | config/i18n.php | index.php | browse.php |
road_vehicles.php | special_vehicles.php | portfolio.php | about.php |
blog.php | shared/view_ad.php | 06_company/06_30_company_directory.php |
06_company/06_02_view_company.php
Sovrascrivere mantenendo i percorsi.

Prossimo blocco al tuo "procedi": **M2 - Attivazione fornitori** (percorso
"primo annuncio in 10 minuti" + email benvenuto + badge Founding partner).
Ricorda in parallelo i 3 task infra: DNS/SMTP, HISTATS_ID, URL social.
