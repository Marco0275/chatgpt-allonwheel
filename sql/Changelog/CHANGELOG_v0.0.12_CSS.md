# Allonwheel v0.0.12 — Delta: Consolidamento CSS (Blocco 1)

**Eccezione unica a dir. 8** autorizzata da Marco: tutto il CSS prima sparso
nei file confluisce in `allonwheel_style.css`. Nessun nuovo design, stesse regole.

## Cosa è stato fatto
- **3 blocchi `<style>` rimossi** e spostati nel foglio principale:
  - `cookie_banner/cookie_banner.php` → regole `#aow-cookie-banner` / `#aow-cc-manage`
  - `02_free_ads/02_preview_ad.php` e `03_ads/03_preview_ad.php` → `.Stile1` / `.Stile2`
- **Stili inline → classi semantiche** (nuovo blocco in coda al CSS):
  - `color:#1D275A` (8 file) → `.aow-title-navy`
  - flash/errori 06_14_company_gallery → `.flash` `.flash_ok` `.flash_err` `.muted_small` `.gal_thumb` `.btn_del` `.inline_form`
  - chip-bar macro in `browse.php` → `.macro_filter` `.chip` `.chip.active` (stato attivo via classe, non più via style PHP) + `.clear_link` `.m0`
  - thumbnail 02/03 insert/modify → `.thumb_wrap` `.ib_mr12`
  - esito form 01_login → `.aow-error-text` `.aow-ok-text`
  - `header.php` `<br>` → `.clear_left`; `blog_comments.php` form → `.inline_form`

## Lasciato inline INTENZIONALMENTE
- **Honeypot anti-spam** `display:none` (`contact.php`, `04_request_offer.php`) — comportamento, non stile.
- **Toggle JS** `display:none` (`reset_password.php` #pwd_error, `register_ok_noemail.php`) — il colore è passato a classe, il `display` resta inline perché lo muove il JS.
- **Handler di redirect** `04_send_offer.php`, `contact_submit.php` — NON caricano il foglio di stile (sola `header('Location')`), quindi i loro style restano inline.
- **`index.php`** e **`00_first/*`** — esclusi: `index.php` è il P0 da riscrivere; `00_first/` è legacy motorsport in attesa di rimozione con redirect 301.

## Verifica (doppio passaggio, dir. 2/10)
- `php -l` su tutti i 22 PHP modificati → **No syntax errors**.
- Line endings preservati: **CRLF** ovunque, **LF** su `cookie_banner.php` (com'era).
- Cookie banner GDPR: markup intatto, banner ancora stilato (il foglio è caricato su tutte le pagine che lo mostrano).
- Nessuna classe orfana: tutte le classi referenziate esistono nel CSS.

## Applicazione
Sovrascrivere i 23 file mantenendo i percorsi. Nessuna modifica a DB, `upload_image/`, `images/`.
