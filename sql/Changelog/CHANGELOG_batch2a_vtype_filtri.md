# Allonwheel — Batch 2 / parte A: vehicle types come filtri LIVE (punto 6)

Data: 2026-06-20

## Cosa fa
Aggiunge il filtro per **vehicle type** (live da `vehicle_types`) su browse, road e
special, con una chip-bar che riusa lo stile `.chip` gia' esistente (nessun CSS
nuovo, dir.8). I tipi sono letti dal DB a ogni richiesta: un tipo aggiunto da admin
**compare da solo** come filtro (propagazione live, niente rigenerazione).

## File modificati
- **browse.php**
  - Nuovo parametro `?vtype=<slug>`, validato contro `vehicle_types.slug`.
  - Clausola ` AND vehicle_type = ?` aggiunta a ENTRAMBI i rami della UNION
    (02_free_ads + 03_ads); bind accodato dopo `macro` -> ordine coerente.
  - Chip-bar "All types" + tutti i vehicle types sotto la barra macro. Gli href
    preservano `macro` e `q` (helper `aow_bqs`). Le label usano `t('vt.'+slug)`.
- **road_vehicles.php** / **special_vehicles.php**
  - Stessa logica, ma la lista tipi e' filtrata sul macro della pagina
    (`macro_category = 'road'` / `'special'`): la chip-bar mostra solo i tipi
    pertinenti. Filtro `?vtype=` + clausola in entrambi i rami; href preservano `q`.
- **lang/en|it|fr|de.php**: aggiunta chiave `filter.all_types`
  (All types / Tutti i tipi / Tous les types / Alle Typen).

## Note
- Le label dei singoli tipi usano le chiavi `vt.<slug>` gia' presenti nei dizionari.
- Su browse, cambiando macro il sotto-filtro vtype si azzera (gli href delle chip
  macro preesistenti non sono stati toccati per ridurre il rischio); il vtype invece
  preserva il macro. Se vuoi che anche le chip macro mantengano il vtype, lo aggiungo.
- I link vtype della **sidebar** puntano alla *directory fornitori*
  (`06_30_company_directory.php?vtype=`), mentre questi filtri agiscono sugli
  *annunci* (02/03): sono due assi diversi e coerenti col modello dati.

## Verifiche
- Full-project `php -l`: 261 file, 0 errori.
- browse: clausola `vtype` presente in entrambe le WHERE, bind nell'ordine
  search→macro→vtype, `bind_union` calcolato dopo. CRLF preservati.

## Restante del Batch 2
- **Punto 7 (bottoni uniformi)**: richiede una micro-eccezione CSS — vedi nota nella
  chat (la classe `.more` ha larghezza fissa 110px e troncherebbe i label lunghi).
- **Hero `index.php`**: la home e' ancora il template motorsport (bloccata sulla tua
  decisione immagini/copy), quindi i chip vtype nella hero della home restano in
  attesa di quel via libera.
