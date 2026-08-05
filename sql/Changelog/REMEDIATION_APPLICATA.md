# Remediation applicata — 1 giugno 2026

## Interventi P0 / quick-win implementati nel codice
- **GDPR Art. 17** — `01_login/delete_account.php`: cancellazione account con
  re-autenticazione (password) + CSRF; erasure completa (annunci free/premium,
  gallery, tech details, azienda + figli) e dei file immagine; account admin
  bloccato per integrità dell'audit trail (Art. 17(3)(b)).
- **GDPR Art. 20** — `01_login/export_data.php`: export JSON dei dati personali
  (password esclusa).
- **Profilo** — `01_login/all_about_me.php`: sezione "Your data & privacy" con i
  link a export e cancellazione.
- **Sicurezza credenziali** — `.htaccess`: blocco esteso anche a `env` (senza
  punto); `config/env` ripulito dalle credenziali reali (ora è un template).
- **CSRF form contatto** — `contact.php` + `contact_submit.php`.
- **Retention** — `scripts/purge_personal_data.php`: purga `login_attempts` > 90 gg (cron).
- **Cookie Policy** linkata nel footer.
- **Cookie banner** (Garante 2025) pronto in `cookie_banner/` + `js/cookie_consent.js`
  + `sql/consent_log.sql` — INATTIVO finché non ci sono cookie non tecnici.

## Azioni MANUALI ancora necessarie (lato server / processo)
1. **RUOTARE** le password DB e mail precedentemente esposte in `config/env`.
2. Posizionare il file `.env` reale **fuori dalla webroot** (vedi `config/bootstrap.php`).
3. Eseguire `sql/consent_log.sql` solo quando si attiveranno tracker.
4. Impostare il cron giornaliero per `scripts/purge_personal_data.php`.
5. Dopo aver convertito gli handler inline, mettere la **CSP in enforce**
   (`ALLONWHEEL_CSP_ENFORCE = true`) e rimuovere `'unsafe-inline'` da `script-src`.
6. Pianificare aggiornamento jQuery / sostituzione piroBox.
