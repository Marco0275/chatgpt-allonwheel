# Allonwheel — Admin: classificazione Road / Special dei vehicle types

Data: 2026-06-20

## Cosa fa
Nuova pagina admin `_admin/admin_classify_vehicles.php` (voce nav "Road/Special"):
- Riepilogo a due colonne: Special a sinistra, Road a destra (con conteggi).
- Form con una checkbox per ogni vehicle type: le voci spuntate diventano Special,
  tutte le altre diventano Road.
- Al salvataggio aggiorna in blocco vehicle_types.macro_category (transazione,
  prepared statement, CSRF one-shot via csrf_generate(), AdminAuth).
Copre veicoli e shelter (entrambi righe di vehicle_types).

## Riflesso sulle pagine
- Automatico/live: i filtri Road/Special su browse, road_vehicles, special_vehicles
  leggono macro_category dal DB a ogni richiesta.
- Sidebar statiche: dopo il salvataggio, il messaggio ricorda di rilanciare
  gen_sidebars.py per aggiornare i box "Road/Special vehicle types".

## Verifiche
- php -l OK (pagina + header admin). Full-project: 0 errori.
- Solo classi CSS esistenti. CRLF preservati.
