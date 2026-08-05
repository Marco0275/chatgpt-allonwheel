# Allonwheel - Fix: layout Tech details + coerenza pagine Road/Special
2026-07-09. Base V2_2. Un solo ZIP. CRLF. PHP lint 786/786 OK. CSS bilanciato.
Verificato con getComputedStyle reale (jsdom).

## Problema 1 (Immagine 1) - Tech details con layout scomposto
La pagina 03_view_tech_details.php e' una VISTA in sola lettura, ma riusa
`#contact_form`. La regola CSS form-mobile `#contact_form:not(.ad_detail)`
(pensata per i FORM: label a blocco, campi width:100%) si applicava anche a
questa vista, scomponendo la tabella (label e valori disallineati, colonne
sballate come nello screenshot).

### Fix
- 03_view_tech_details.php: al wrapper ho aggiunto la classe `tech_view`
  (`<div id="contact_form" class="tech_view">`).
- allonwheel_style.css:
  - escluso `.tech_view` da tutte le regole form-mobile
    (`:not(.ad_detail)` -> `:not(.ad_detail):not(.tech_view)`, 15 selettori);
  - aggiunte regole dedicate `.tech_view`: tabella a larghezza piena,
    colonne al 33% (colspan 2/3 gestiti), testo allineato a sinistra,
    label/checkbox inline, titoli in grassetto spaziati.
Risultato: la scheda tecnica e' leggibile e allineata (verificato: label
inline, td 33.33%, contenitore a piena larghezza).
Nessuna modifica al FORM di modifica (03_modify_tech_details.php intatto).

## Problema 2 (Immagine 2) - "Vehicles - Special" con URL ?macro=road
special_vehicles.php ha il macro forzato a 'special' e ignora `?macro=road`:
arrivando a `special_vehicles.php?macro=road` il titolo restava "Special"
(contraddittorio con l'URL). I link interni del sito sono corretti
(road_vehicles.php / special_vehicles.php senza ?macro): l'URL dello
screenshot era anomalo (bookmark/incolla manuale).

### Fix (robustezza)
- special_vehicles.php: se arriva `?macro=road` -> redirect 301 a
  road_vehicles.php (mantiene gli altri parametri).
- road_vehicles.php: simmetrico, `?macro=special` -> special_vehicles.php.
Cosi' URL, titolo e contenuti restano sempre coerenti.
NOTA: il testo "Premium ad form" ripetuto nella card e' un DATO DI TEST
(placeholder del record), non un bug di layout: sparira' con contenuti reali.

## File (4)
allonwheel_style.css | 03_ads/03_view_tech_details.php |
special_vehicles.php | road_vehicles.php

## Test
1. Apri 03_ads/03_view_tech_details.php?id_ads=<N>: la scheda tecnica e'
   ordinata, checkbox e valori allineati nelle colonne.
2. special_vehicles.php?macro=road -> ti porta a road_vehicles.php ("Road").
3. La modifica della scheda tecnica (03_modify_tech_details.php) e' invariata.
