# Allonwheel — P1.3: redirect 301 delle pagine legacy 00_first/

Data: 2026-06-20

## Contesto
Le 11 pagine in `00_first/` sono i residui "motorsport demo" del template.
Non sono piu' referenziate da header/footer/sidebar/index (verificato: nessun link
diretto). Restano raggiungibili solo via URL diretto / indicizzazione esterna.

## Intervento
Ogni pagina e' stata sostituita con uno **stub di redirect 301 permanente** verso
la pagina reale equivalente, con `X-Robots-Tag: noindex` e fallback `<meta refresh>`
+ link (nel caso gli header siano gia' stati inviati). Usa `BASE_URL` da bootstrap.

| Pagina legacy | Redirect 301 -> |
|---|---|
| racing_trailer.php, paddock_trailer.php, box_trailer.php | browse.php?macro=race-trailer |
| hospitality.php, roadshow.php, mobilhome.php, motorhome.php, motorhome_mobilhome.php | browse.php?macro=hospitality |
| sell_or_rent.php | browse.php |
| service.php | contact.php |
| why-rent.php | about.php |

## Note
- **Immagini intatte:** `images/00_first/*` NON sono toccate (dir.15) e restano usate
  dalla home; qui si dismettono solo le PAGINE PHP in `00_first/`.
- Nessuna tabella DB e' mappata a `00_first/` (non e' una sezione dir.1): nessuna
  migrazione necessaria.
- Quando vuoi, le pagine si possono anche eliminare del tutto: i redirect bastano a
  preservare SEO/link esterni nel frattempo.

## Verifiche
- `php -l` su tutti gli 11 stub: OK. Full-project: 0 errori.
- CRLF preservati.
