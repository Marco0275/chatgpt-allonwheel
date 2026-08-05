# Allonwheel v0.0.13 — Fix PiroBox (anteprima thumbnail → click = immagine reale)

## Pattern corretto (recuperato dal codice originale)
Riferimento: `template/full_blog_post.php` (templatemo) e le pagine annuncio gia'
corrette (`shared/view_ads.php`, `browse.php`, `shared/gallery.php`):
- **`<a class="pirobox" href="IMMAGINE_ORIGINALE">`** → PiroBox apre la foto a dimensione reale.
- **`<img src="THUMBNAIL" width="220" height="150">`** → anteprima piccola in pagina.
Il difetto comune nelle pagine rotte era **href === src** (stessa immagine: PiroBox non
mostrava nulla di piu' grande) oppure **img senza width/height** (l'originale veniva
mostrato a tutta pagina, senza anteprima).

## Pagine corrette
| File | Problema | Correzione |
|---|---|---|
| `blog_post.php` | href=src=originale, **img senza width/height** (foto enorme inline) | aggiunto `width="220" height="150"`; href resta l'originale. Il blog non ha thumbnail: anteprima = originale ridotto via width/height, PiroBox a dimensione reale (pattern templatemo). |
| `blog.php` | idem (lista articoli) | idem |
| `06_company/06_02_view_company.php` | logo e galleria: **href=src=originale** (anteprima pesante) | `src` → **thumbnail** (`/thumbnail/`), `href` resta originale. Aggiunte `$logo_thumb` e `$img_thumb`. |
| `06_company/06_30_company_directory.php` | logo: **href=src=thumbnail** (PiroBox apriva la miniatura) | `href` → **originale** (`/original/`), `src` resta thumbnail. Aggiunti `$logo_orig_base`/`$logo_full`. |
| `06_company/06_20_modify_company.php` | logo: **href=src=originale** | `src` → **thumbnail**; `href` resta originale. |
| `02_free_ads/02_insert_ad_image.php` | **href=src=thumbnail** (PiroBox apriva la miniatura) | aggiunto `$imageFull` (da `image_original`); `href` → **originale**, `src` resta thumbnail. |

## Pagine verificate GIA' corrette (nessuna modifica)
`shared/view_ads.php`, `shared/view_ad.php`, `shared/gallery.php` (pirobox_gall),
`browse.php`, `road_vehicles.php`, `special_vehicles.php`, `shelter_container.php`,
`01_login/my_posts.php`, `06_company/06_14_company_gallery.php` (pirobox_gall),
`02_free_ads/02_insert_ad_gallery.php`, `02_free_ads/02_modify_insert_ad_gallery.php`,
`02_free_ads/02_preview_ad.php` — gia' href=originale + src=thumbnail.

## Single-file con sizing (corretti per pattern templatemo, lasciati invariati)
`index.php` (box statici), `sidebar_company_logo.php`, `01_login/all_about_me.php`,
`_admin/manage_companies.php` (logo con classe CSS `admin_thumb`): href=src ma con
width/height o classe CSS che crea l'anteprima → PiroBox apre la dimensione naturale.

## Verifica
- `php -l` OK sui 6 file. Dopo il fix ogni ancora PiroBox ha **href = originale** e
  **src = thumbnail** (o single-file con width/height). CRLF preservati.
- **Nessun percorso immagine inventato** (dir.15): usati solo `/upload_image/06_company/
  {original,thumbnail}/` e `/upload_image/02_free_ads/{original,thumbnail}/`, gia' in uso
  altrove; gli `onerror` esistenti coprono eventuali file mancanti. `images/`/`upload_image/`
  non toccate.

## Nota
Il **blog non genera thumbnail** (solo `/upload_image/blog/original/`): l'anteprima usa
l'originale ridotto via width/height. Se in futuro si vorranno vere miniature piu' leggere,
andra' aggiunta la generazione thumbnail in fase di upload articolo.
