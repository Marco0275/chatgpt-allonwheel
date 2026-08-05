# Allonwheel — Correzione SMTP (.env) + ID Histats

## SMTP nel `.env` — c'erano errori, ora corretto
Problemi trovati nel file caricato:
1. Le **credenziali SMTP reali** erano nel blocco "stile Laravel"
   (`MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_ENCRYPTION`), che però
   `libs/mailer.class.php` **non legge**. Mailer usa `SMTP_HOST`, `SMTP_USER`,
   `SMTP_PASS`, `SMTP_ENCRYPTION`, `MAIL_TRANSPORT`, `MAIL_FROM`, `MAIL_FROM_NAME`.
2. I campi che Mailer legge erano **vuoti** (`SMTP_HOST=`, `SMTP_USER=`).
3. Il blocco SMTP era **duplicato**; il parser del `.env` usa "**prima occorrenza
   vince**", quindi vinceva `MAIL_TRANSPORT=mail` (la prima) e host/user vuoti →
   **SMTP di fatto spento**.

Nel file **`env_corretto`** (rinominalo `.env` sul server, fuori dalla webroot):
- `MAIL_TRANSPORT=smtp`  (prima era `mail`)
- `SMTP_HOST=mail.allonwheel.com`  (prima vuoto)
- `SMTP_PORT=587`
- `SMTP_USER=noreply@allonwheel.com`  (prima vuoto)
- `SMTP_PASS=…`  (impostata dal valore reale che avevi in `MAIL_PASSWORD`)
- `SMTP_ENCRYPTION=tls`  (587 + STARTTLS, coerente)
- `MAIL_FROM=info@allonwheel.com`, `MAIL_FROM_NAME=All on Wheel Ltd`
- **`HISTATS_ID=4703110`** aggiunto
- **duplicati rimossi**: ogni chiave compare una sola volta.
- mantenuto LF (i `.env` non vanno in CRLF).

### Da controllare se l'invio fallisce (troubleshooting)
- Alcuni server rifiutano un mittente diverso dall'utente autenticato: se ottieni
  "sender address rejected", metti `MAIL_FROM=noreply@allonwheel.com` (= utente SMTP).
- Se il server usa **SSL su 465** invece di STARTTLS su 587, imposta
  `SMTP_PORT=465` e `SMTP_ENCRYPTION=ssl`.

## Histats — ID impostato, ora il contatore parte
Il partial `includes/histats.php` conteneva già il tuo codice esatto (ID **4703110**),
ma il "fallback ID" era vuoto: il controllo a inizio file faceva `return` e **non
stampava nulla**. Corretto:
- impostato l'ID **4703110** (fallback) + reso lo snippet **parametrico**
  (l'ID è ora in un solo punto e guida sia `Histats.start` sia il pixel `noscript`);
- in più ora c'è anche `HISTATS_ID` nel `.env`, quindi l'ID arriva pure da lì.
- Verificato: lo snippet viene generato con `1,4703110,4,0,0,0,00010000`.
- L'inclusione resta unica via `footer.php`.

> Sostituisci `includes/histats.php` e metti `env_corretto` come `.env` sul server.
