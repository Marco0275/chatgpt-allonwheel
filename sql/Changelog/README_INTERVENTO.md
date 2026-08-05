# Allonwheel — Sidebar condizionale, browse, shelter & container, vehicles

Versione: 28 maggio 2026 (intervento successivo a quello su tassonomia Road/Special).

## Riepilogo dell'intervento

| # | Richiesta | Stato |
|---|---|---|
| 1 | Nelle pagine con sidebar: utente loggato → `sidebar_logged.php`, altrimenti `sidebar_static.php` | ✅ |
| 2 | `browse.php` deve mostrare **tutti** gli annunci free e premium (shelter & vehicles) | ✅ già conforme |
| 3 | Pagina che mostra **solo** Shelter & Container | ✅ `shelter_container.php` già presente |
| 4 | Pagina che mostra **solo** Vehicles | ✅ NUOVO `vehicles.php` |
| 5 | Collegare le due pagine ad entrambe le sidebar | ✅ |

## File modificati

### `include_sidebar.php` (dispatcher)
Per utenti loggati ora include `sidebar_logged.php` (era `sidebar.php`). Visitatori → `sidebar_static.php`. La direttiva 17 è rispettata letteralmente. Internamente `sidebar_logged.php` delega a `sidebar.php` (che resta il file con il contenuto reale del menu), quindi nessuna duplicazione di codice.

### `sidebar.php` (contenuto loggato)
Sezione **Marketplace** corretta:
- "All listings" → `browse.php`
- "Shelter & Container" → `shelter_container.php` (era erroneamente `browse.php`)
- "Vehicles" → `vehicles.php` (era erroneamente `02_free_ads/02_view_ads.php`)
- "Supplier directory" e "Portfolio" invariati.

### `sidebar_static.php` (contenuto visitatore)
Stessa correzione applicata: tre link Marketplace corretti come sopra.

## File NUOVO

### `vehicles.php` (root)
Mirror di `shelter_container.php` ma con filtro opposto: mostra **solo** gli annunci di tipo veicolo, escludendo Shelter & Container.

**Query (entrambi i rami della UNION):**
```sql
WHERE (item_kind = 'vehicle' OR item_kind IS NULL)
      AND status = 'approved'
```
La condizione `OR item_kind IS NULL` garantisce retro-compatibilità con eventuali annunci pre-migrazione che non hanno ancora la colonna popolata (anche se nello schema migrato il `DEFAULT 'vehicle'` la popola).

**Filtro macro opzionale:** la pagina accetta `?macro=road` e `?macro=special` per restringere ulteriormente la vista. Bottoni "All / Road / Special" nella testata della pagina.

**Stato vuoto graceful:** se la migrazione non è ancora stata eseguita (colonne `item_kind`/`macro_category` assenti), la pagina cattura l'eccezione e mostra "This section is being set up. Please check back soon." anziché un errore SQL.

Nessuno stile nuovo (dir. 8): riutilizza `.post_box`, `.gallery`, `.cat`, `.more`, `.float_r` già definite in `allonwheel_style.css`.

## `browse.php`
Lasciato invariato: era **già** conforme alla richiesta. La UNION di `02_free_ads` e `03_ads` non ha alcun filtro su `item_kind`, quindi mostra tutti gli annunci (vehicles + shelter, free + premium), ordinati per data. Il link "All listings" nelle sidebar punta qui.

> Nota: differenza voluta — `browse.php` non filtra per `status`, mostra ogni annuncio inserito. Le due pagine dedicate (`shelter_container.php` e `vehicles.php`) filtrano `status = 'approved'`, come è già nel comportamento di `shelter_container.php`. Se vuoi uniformare anche `browse.php` a "solo approvati" è un'aggiunta minima.

## Architettura della sidebar

```
qualsiasi pagina del sito
   └── include __DIR__ . '/include_sidebar.php'   (o '/../include_sidebar.php' da subfolder)
         │
         ├── is_user_logged_in() == TRUE  → include sidebar_logged.php
         │                                    └── require_once sidebar.php   ← contenuto reale
         │
         └── is_user_logged_in() == FALSE → include sidebar_static.php       ← contenuto reale
```

Tutte le pagine del sito (verificate ~50) usano già `include __DIR__ . '/include_sidebar.php'` — non c'è nessun `include sidebar.php` diretto in giro, quindi il cambio del dispatcher è trasparente.

## Verifiche eseguite (doppia passata)

- **Lint completo:** 148 file PHP, **0 errori**.
- **Test funzionali:**
  1. Il dispatcher include `sidebar_logged.php` (non più `sidebar.php` diretto).
  2. `sidebar.php` ha 3 link corretti in Marketplace.
  3. `sidebar_static.php` ha 3 link corretti in Marketplace.
  4. `vehicles.php` filtra `(item_kind = 'vehicle' OR item_kind IS NULL) AND status = 'approved'`.
  5. `shelter_container.php` filtra `item_kind = 'shelter_container' AND status = 'approved'` (invariato).
  6. `browse.php` UNION 02+03 senza filtro su `item_kind` — mostra tutto.
  7. Nessun include diretto legacy di `sidebar.php` altrove (la catena `include_sidebar → sidebar_logged → require_once sidebar` è pulita, niente ricorsioni).
- **EOL preservati:** `include_sidebar.php` LF (originale LF), `sidebar.php` e `sidebar_static.php` CRLF (originali CRLF), `vehicles.php` CRLF (coerente con `shelter_container.php` e `browse.php`).

## Indice file inclusi nel pacchetto

1. `include_sidebar.php` (MODIFICATO)
2. `sidebar.php` (MODIFICATO)
3. `sidebar_static.php` (MODIFICATO)
4. `vehicles.php` (NUOVO)

`sidebar_logged.php`, `browse.php`, `shelter_container.php` sono **invariati** rispetto allo ZIP che hai caricato — li trovi già nel tuo progetto, non serve riconsegnarli.
