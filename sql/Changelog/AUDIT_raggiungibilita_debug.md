# Allonwheel V1.1 — Audit raggiungibilita' + debug

Data: 2026-06-24 · Versione analizzata: ultima (logo+Account, fix header/login, `?v=20260630`).

## Sintesi
- **260** file PHP di sito (escluse vendor/libs).
- **Lint PHP 8.3: 0 errori** su tutti i 260 file.
- Sito **sostanzialmente tutto linkato**: solo 15 file non sono mai citati altrove,
  e quasi tutti per design (vedi sotto).

## Link rotti
| # | Sorgente | Target | Esito |
|---|----------|--------|-------|
| 1 | `03_ads/03_preview_ad.php` | `/02_free_ads/3-2_modify.php` | **ROTTO (corretto)** -> ora `/03_ads/03_modify_insert_ad.php` |
| 2 | `01_login/register_ok_noemail.php` | `../submit.php` | **ROTTO (legacy)** form verso file inesistente; pagina legacy del vecchio flusso `register.php`. Da decidere: ricollegare a un endpoint valido o dismettere la pagina. |
| 3 | `_admin/moderate_blog.php` | `/blog_post.php` | OK (root-relative, esiste a root) |
| 4 | `cookie_banner/cookie_banner.php` | `/cookie-policy.php` | OK (root-relative, esiste a root) |
| 5 | `Changelog/patch.php` | `Changelog/scripts/...` | Ignorare: `Changelog/` e' cruft di sviluppo (rimosso dal .bat di pulizia) |

## Pagine mai referenziate (15)
**Per design / SEO (tenere):**
- `catalog.php` -> redirect 301 a `browse.php` (serve i vecchi URL).
- `sitemap.php` -> sitemap XML, citata in `robots.txt` (non in nav, corretto).
- `00_first/*` (10 file) -> stub di redirect 301 delle pagine motorsport legacy.

**Legacy da valutare (rimuovere con redirect o dismettere):**
- `ad_post.php` -> vecchio flusso annunci (sostituito da `browse.php`/`03_ads`).
- `06_company/06_new_company.php` -> sostituito da `06_10_register_company.php`.
- `01_login/not_registered.php` -> pagina d'errore del vecchio login.

## Note
- I link di navigazione globali (Home/Marketplace/Suppliers/Portfolio/About/Account)
  vivono in `header.php` incluso da tutte le pagine: ogni pagina e' quindi raggiungibile
  dalla navigazione. L'area Account e' login-aware (ospite vs loggato).
- Area `_admin/` raggiungibile solo dopo login admin (corretto, non in nav pubblica).

## Azione applicata
- Corretto il redirect rotto in `03_ads/03_preview_ad.php` (file allegato).
