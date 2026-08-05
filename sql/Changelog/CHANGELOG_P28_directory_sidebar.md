# Allonwheel — P2.8: navigazione fornitori per categoria (sidebar, niente filtri in cima)

Data: 2026-06-20

## Modello (come richiesto)
- Le aziende dichiarano prodotti tra TUTTE le sotto-categorie (invariato).
- Chi naviga: usa il box di ricerca OPPURE sceglie Special / Road. Dopo la prima
  scelta, la **sidebar** mostra TUTTE le sotto-categorie di quella categoria.
- I **filtri in cima alle pagine sono stati rimossi**.

## Filtri in cima rimossi (resta la logica di filtro, ora guidata dalla sidebar)
- `browse.php`, `road_vehicles.php`, `special_vehicles.php`: rimossa la chip-bar
  vehicle-type in testa (il filtro `?vtype=` resta e ora e' pilotato dalla sidebar).
- `06_company/06_30_company_directory.php`: rimossa la chip-bar "famiglie" in testa.
- NB: su `browse.php` resta la chip-bar delle 5 macro-brand (filtro annunci per
  famiglia), che e' cosa diversa dal nav Road/Special dei fornitori. Se vuoi togliere
  anche quella, e' un attimo.

## Sidebar dinamiche (scritte a mano; gen_sidebars.py le SALTA)
- **sidebar_road_vehicles.php** / **sidebar_special_vehicles.php**: elencano i
  vehicle types del rispettivo macro come filtri della pagina stessa
  (`road_vehicles.php?vtype=` / `special_vehicles.php?vtype=`), con "All types" e
  voce attiva evidenziata. Live da `vehicle_types` (un tipo nuovo compare subito).
- **sidebar_06_30_company_directory.php**: navigazione a due livelli.
  - Nessuna scelta -> due voci: **Special** / **Road**.
  - `?cat=special` -> elenco delle 6 categorie speciali (`?cat=special&special=...`).
  - `?cat=road` -> elenco dei vehicle types road (`?cat=road&vtype=...`).
  - In fondo, link per tornare alla scelta Road/Special.
  La directory filtra gia' per `?special=` / `?vtype=`; il parametro `?cat` guida solo
  la sidebar e si auto-inferisce se e' presente special/vtype.

## gen_sidebars.py
Aggiornato: salta `road_vehicles.php`, `special_vehicles.php`,
`06_company/06_30_company_directory.php` (le loro sidebar sono dinamiche e non vanno
rigenerate). Resta un tool locale, non caricare in webroot.

## Verifiche
- Full-project `php -l`: 264 file, 0 errori. Solo classi CSS esistenti
  (sb_box/sb_list/cleaner). CRLF preservati.

## Nota
La directory, scelto `?cat=road|special` senza una sotto-categoria, mostra l'elenco
completo dei fornitori; la sidebar lo restringe alla sotto-categoria scelta. Se
preferisci che `?cat` mostri SOLO i fornitori di quel macro gia' da subito, aggiungo
i due metodi di query dedicati.
