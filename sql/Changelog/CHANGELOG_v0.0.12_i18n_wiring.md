# Allonwheel v0.0.12 — Delta: i18n live nella UI (header/footer + switcher + hreflang)

Costruisce sulle fondamenta i18n gia' consegnate: ora la traduzione e' VISIBILE.
Da applicare sopra il delta precedente.

## config/i18n.php
- `te($key,$default)`: echo + htmlspecialchars (stampa sicura della traduzione).
- `aow_lang_switcher()`: HTML dello switcher EN|IT per la pagina corrente
  (mantiene path e query string, evidenzia la lingua attiva).

## lang/en.php + lang/it.php
- +12 chiavi (nav.about_us/what_we_do/blog/faq/conditions/contact,
  footer.marketplace/useful/follow/privacy/cookie/lang). IT tradotto.

## header.php
- Include i18n guardato. **Tutta la nav** ora usa `te('chiave','Testo EN')`:
  Home, Marketplace, All listings, Request a quotation, Suppliers, Supplier
  directory, Road/Special vehicles, Shelter & Container, Portfolio, About,
  What we do, Blog, F.A.Q., Conditions & rules, Contact us.

## footer.php
- Include i18n guardato. Titoli e link wirati con `te()`; legali (Privacy/Cookie).
- **Switcher lingua EN|IT** aggiunto nel rigo copyright.

## index.php + browse.php
- `aow_hreflang_tags()` nel <head> (alternate en / it / x-default).

## Comportamento
- Default = inglese: le pagine attuali restano identiche (fallback sui default EN).
- `/it/...` -> nav e footer in italiano, switcher attivo, hreflang corretto.
- Le altre stringhe di pagina si traducono in modo incrementale con `t()/te()`.

## Verifica
- `php -l` OK su tutti i 7 file. Render i18n testato EN/IT (nav, switcher con query
  string preservata, hreflang). CRLF preservati.
