# Allonwheel — Ottimizzazione sito

Data: 2026-06-24

## 1) Performance .htaccess (compressione + cache)
Gia' presenti deflate + cache per css/js/immagini. Aggiunto:
- **Cache dei font** (l'Oswald non era cachato): `font/woff2`, `font/woff`, `font/ttf`
  a 1 anno; estesa la FilesMatch del Cache-Control a `woff2|woff|ttf`.
- **immutable** sul Cache-Control degli asset statici (versionati via ?v= e nomi
  stabili) -> il browser non rivalida, meno richieste.
- **AddType font/woff2** (MIME corretto se il server non lo conosce).
- Cache-Control gia' correttamente limitato agli asset statici (HTML escluso).

## 2) Lazy-loading immagini
Le 64 <img> non avevano `loading`. Aggiunto **`loading="lazy" decoding="async"`**
alle immagini delle pagine a lista/griglia (browse, road, special, view_ads,
gallery, directory). Meno banda e render piu' veloce; i browser caricano comunque
subito quelle in viewport. (Le immagini sono in loop: 1 tag = tutte le istanze.)
NB: la scheda annuncio singola (view_ad) e' stata lasciata "eager" per non
ritardare l'immagine principale above-the-fold.

## 3) SEO / social — home
Aggiunti a `index.php`: **canonical**, **Open Graph** (type/site_name/title/
description/url/image) e **Twitter Card** (summary_large_image). Migliora le
anteprime di condivisione e la chiarezza per i motori. (description e hreflang
gia' presenti.)

## File
- `.htaccess` (LF), `index.php`, `browse.php`, `road_vehicles.php`,
  `special_vehicles.php`, `shared/view_ads.php`, `shared/gallery.php`,
  `06_company/06_30_company_directory.php`.

## Verifiche
- `php -l` su tutti i file toccati: 0 errori. CRLF preservati (LF su .htaccess).

## Possibili passi successivi
- Minificazione CSS/JS (con gzip attivo il guadagno sul filo e' modesto: ~marginale).
- `defer` sugli script: rischioso con jQuery 1.3.2 + init inline; da valutare con test.
- OG/canonical anche su browse e scheda annuncio (head per-pagina).
- Preload del font Oswald (1 richiesta critica) e `width/height` sulle <img> per CLS.
