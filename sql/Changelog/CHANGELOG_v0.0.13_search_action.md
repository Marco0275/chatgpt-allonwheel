# Allonwheel v0.0.13 — Delta: search box decorative ora funzionanti

Le search box ripulite nel delta precedente avevano ancora `action="#"` (non
cercavano nulla). Ora **tutte** le ricerche generiche puntano al marketplace
`browse.php` (che filtra su `title/description/author` via `?q=`), come la home.

## Cosa cambia
- **46** form di ricerca con `action="#"` → `action="<?php echo $base_url; ?>browse.php"`.
- **portfolio.php**: la ricerca puntava per errore a `special_vehicles.php` → ora `browse.php`.
- **index.php** e le altre già su `browse.php`: unificate al prefisso `$base_url`.
- Totale **48** form allineati.

## Perche' `$base_url` e non `browse.php` secco
`browse.php` sta nella root. Da una pagina in sottocartella (`01_login/`, `02_free_ads/`,
`03_ads/`, `04_request_offer/`, `06_company/`, `shared/`) un `action="browse.php"` si
risolverebbe in `01_login/browse.php` → 404. `header.php` espone `$base_url`
(`''` in root, `'../'` in sottocartella) **prima** del rendering della box, quindi:
- root → `action="browse.php"`
- sottocartella → `action="../browse.php"`
Stesso schema già usato dalla navigazione di `header.php`. Verificato per tutte le
sottocartelle in elenco. (I 3 scaffold in `template/` non sono route servite: usano lo
stesso pattern e si adattano correttamente quando il template viene copiato.)

## Non toccato (volutamente)
- **Self-search funzionali** (`action=""`: road/special/shelter/view_ads): cercano su
  se stesse, comportamento corretto invariato.
- **Directory fornitori** (`action="06_30_company_directory.php"`): ricerca fornitori,
  invariata.

## Verifica
- `php -l` OK su tutti i 48 file. Risoluzione `$base_url` testata (root→`browse.php`,
  sottocartella→`../browse.php`). CRLF preservati. Nessuna modifica a markup
  della box, dizionari, DB/query o `images/`.

## Nota di applicazione
Questi 48 file sono **cumulativi**: contengono tutte le modifiche precedenti
(i18n testo dove applicabile + search box pulita + action funzionante). Applicare
queste versioni (superano quelle dei delta i18n-text e searchbox per gli stessi file).
