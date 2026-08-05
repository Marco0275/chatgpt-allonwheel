# Allonwheel — Sblocco home (index.php)

Data: 2026-06-20

## Stato di partenza
La home era gia' stata rielaborata (SEO commerciale+motorsport, hero con 3 CTA,
5 famiglie macro, sezione B2B Road/Special, gallery dinamica degli annunci reali).
Restavano: un link rotto, alcune immagini demo del template e nessun meccanismo per
gestire le hero da admin.

## Modifiche
1. **Hero famiglie gestite da admin (live):** nuova mappa `product_macros.hero_image`
   + helper `$hero(slug, fallback)`. Le 5 famiglie (Race Trailer, Hospitality,
   Mobile Clinic, Shelter & Container, Custom Projects) ora usano l'immagine hero
   caricata dall'admin (pagina "Hero images"); se non c'e', **fallback** all'immagine
   attuale. Nessun asset creato/eliminato (dir.15): il codice referenzia soltanto.
2. **Link rotto risolto:** la colonna "Experts & Project manager" puntava a
   `expert_project.php` (inesistente) -> ora punta alla **directory fornitori**
   (`06_company/06_30_company_directory.php`).
3. **Immagini demo -> reali** dove disponibili in `images/00_first/`:
   - B2B Road: `templatemo_image_07.jpg` -> `road_vehicles.JPG`
   - B2B Experts: `templatemo_image_07.jpg` -> `Notizie_tecniche.jpg`
   - B2B Shelter: `templatemo_image_08.jpg` -> `shelter_container.jpg`
   - Shelter (famiglia): href di anteprima `templatemo_image_06.jpg` -> reale.

## Cosa resta "demo" (manca l'immagine reale)
- **Mobile Clinic**: nessuna immagine clinica in `images/00_first/`. Resta come
  fallback `templatemo_image_05.jpg`. Caricane una come **hero della macro
  mobile-clinic** dalla pagina admin "Hero images" e comparira' da sola.
- **B2B Special**: nessuna immagine "special" dedicata; resta `templatemo_image_07.jpg`.
  Se vuoi, mettine una in `images/` e te la collego.

## Verifiche
- `php -l index.php`: OK. CRLF preservati.
- Copy gia' presente nei 4 dizionari (44 chiavi `home.*`), invariata.
- La gallery "Latest from the marketplace" continua a usare le thumbnail reali
  degli annunci approvati.
