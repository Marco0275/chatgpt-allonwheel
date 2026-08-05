# Allonwheel — Restyle 2026: pagine annunci e scheda dettaglio

Data: 2026-06-24

Restyle mirato delle pagine marketplace (oltre al layer generico).

## Liste annunci (browse.php / road_vehicles.php / special_vehicles.php)
- Ogni annuncio diventa una **card con immagine a sinistra** e contenuto a destra
  (CSS su `.post_box .gallery.m0`), con **hover lift** (via :has, progressive).
- **Prezzo evidenziato** in Oswald (aggiunta `class="price"` al paragrafo prezzo).
- Su mobile l'immagine torna a tutta larghezza sopra il testo.

## Scheda annuncio (shared/view_ad.php)
- Hook `class="ad_detail"` sul contenitore.
- Galleria principale piu' grande e arrotondata.
- Le specifiche (Author/Type/Condition/List price/...) diventano **righe pulite**
  label+valore con separatori (CSS su `.ad_detail .float_l`), prezzo in Oswald.
- I pulsanti (Gallery/Tech details/PDF/Manage) restano bottoni.

## File
- `allonwheel_style.css` — blocco "pagine annunci/dettaglio" appeso (sostituisce il CSS
  del pacchetto precedente: lo ingloba).
- `browse.php`, `road_vehicles.php`, `special_vehicles.php` — `class="price"` sul prezzo.
- `shared/view_ad.php` — `class="ad_detail"` sul contenitore.

## Verifiche
- `php -l` full-project: 0 errori. CSS graffe bilanciate (376/376). CRLF preservati.
