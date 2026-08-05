# Allonwheel — Wanted in my_posts (dir.3, vista aggregata)

Base: tua ZIP. `php -l` OK su tutto il progetto. CRLF preservati. PDO+prepared. Dir.8 OK.

## Cosa fa
Chiude il vincolo **dir.3**: `my_posts.php` ora aggrega anche le **richieste Wanted**
dell'utente, insieme a Free ad / Premium ad / Company.

- **`01_login/my_posts.php`** (modificato):
  - nuova query su `wanted_ads WHERE id_user` (PDO);
  - ogni Wanted entra in `$all_posts` come tipo **`wanted`** (label "Wanted"),
    con View → `wanted_view.php`, Edit → `wanted_manage.php`, Delete → handler dedicato;
  - aggiunta la **tab filtro "Wanted"** (conteggio automatico) e la whitelist del filtro;
  - le richieste non hanno immagine → mostra il placeholder `no_image.jpg` (nessuna
    immagine rotta), nessun accesso a chiavi `extra` (gestite solo per company).
- **`05_wanted/wanted_delete.php`** (nuovo): elimina una propria Wanted dal pulsante
  Delete di my_posts (CSRF + ownership), poi torna a my_posts.

## Note
- Nessuna modifica a immagini/upload (dir.15). Nessun nuovo stile (dir.8).
- Prossimo: **Sprint 3 — dashboard venditore** (RFQ aperte, Wanted compatibili,
  download documenti) su `seller_statistics` + `quote_requests` + `wanted_ads`.
