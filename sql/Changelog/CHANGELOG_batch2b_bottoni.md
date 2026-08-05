# Allonwheel — Batch 2 / parte B: bottoni uniformi (punto 7)

Data: 2026-06-20

## Decisione applicata
Stile unico **brand + larghezza adattiva + freccia**, ovunque. La vecchia `.more`
era un pill a immagine fissa (`more.png`, 110px) che troncava i label lunghi:
ridefinita in CSS come bottone brand (#1B2A41) a larghezza automatica, con freccia
"›" via `::after`.

## CSS (unica regola toccata — eccezione dir.8 concordata)
`a.more, button.more` riscritta:
- `display:inline-block`, larghezza automatica (rimosso `width:110px`),
  `padding:0 26px 0 12px`, `background:#1B2A41`, `color:#fff`, `white-space:nowrap`.
- hover `#3a4c5e`.
- freccia `content:"\203A"` via `::after` (a destra). Nessun'altra regola modificata.

## HTML — conversione `<input type="submit">` -> `<button class="more">`
La freccia `::after` non funziona sugli `<input>`: per questo i submit non-admin
sono stati convertiti in `<button type="submit" class="more">`, **preservando**
`name`, `value`, `id`, `title`, eventuali `float_r/float_l` e attributi extra
(es. `disabled` condizionale). La label visibile = il vecchio `value` (testo o PHP
`te(...)`), e `value` resta come attributo: il comportamento dei form e i `$_POST`
sono identici a prima.

- **45 bottoni** convertiti in **39 file** (aree 01–06, shared, blog, contact,
  request offer, wanted, browse, road/special, template).
- I **55 bottoni di ricerca** (`id="searchbutton"`, icona via `#searchbutton`)
  sono stati **lasciati invariati** di proposito.
- I file in `_admin/` non sono stati toccati (scope: pubblico/utente). Gli admin
  `a.more`/`button.more` ereditano comunque il nuovo stile; gli `input` submit
  admin restano come prima.

## Verifiche
- Full-project `php -l`: 261 file, 0 errori.
- `name`/`value` preservati (es. `newlogin` -> `name="login"`, `register` ->
  `name="submit" value="Register"`); tag `<button>` bilanciati; CRLF preservati.
- 0 `<input type="submit">` residui non-admin oltre ai search button.

## Applicazione
Sovrascrivi i 39 file PHP + `allonwheel_style.css`. Verifica a vista qualche form
(login, contact, request offer, wanted, register company) e il pulsante "View
details" su browse (ora brand con freccia, larghezza adattiva).
