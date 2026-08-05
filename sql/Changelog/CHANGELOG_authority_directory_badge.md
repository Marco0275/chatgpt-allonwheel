# Allonwheel - Authority layer (lato pubblico): badge certificazioni in directory - 2026-06-24

L'authority layer era completo lato gestione (06_15_authority.php: upload ISO
9001/14001/45001 + associazioni/referenze/area) e lato scheda azienda (box
Credentials), ma la DIRECTORY non mostrava alcun segnale di fiducia sulle card.

## Modifica
- `06_30_company_directory.php`: ogni card mostra un badge **"Certified"** (verde)
  quando l'azienda ha almeno una certificazione ISO. Il `title` del badge elenca
  le specifiche (ISO 9001 / 14001 / 45001). Tutte le query della directory tornano
  gia' `c.*`, quindi i campi cert_iso* sono disponibili senza modifiche al modello.
- Riuso delle classi badge esistenti (`.badges`, `.badge`, `.badge_approved`):
  **nessuna modifica al CSS**.
- Lang: chiave `dir.certified` (en/it/fr/de).

## Impostazione CSS (richiesta utente)
- Rimosso il `<link ...allonwheel_style.css?v=...>` dal file (e mantenuto rimosso
  d'ora in poi su tutti i file). **Niente piu' cache-buster `?v=` ne' bump.**
  Il caricamento del CSS e' ora gestito centralmente lato utente.

Lint PHP 8.3 OK, CRLF.
