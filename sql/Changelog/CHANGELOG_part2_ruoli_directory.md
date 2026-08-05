# Allonwheel — Ruoli professionali, consenso contatti e directory pubblica

Data: 2026-06-20

## Sintesi
Gli utenti possono dichiararsi Expert / Project manager / Consultant e, dando il
consenso, comparire in una directory pubblica dove gli altri utenti li vedono e li
contattano via form; email e telefono sono mostrati solo con il consenso.

## DB (eseguire PRIMA di pubblicare le pagine)
- `sql/Changelog/2026-06-20_user_public_contact.sql` — aggiunge `users.public_contact`
  TINYINT(1) DEFAULT 0 (idempotente, MySQL 5.7, guardia information_schema).
- La tabella `user_roles` (enum expert/project_manager/consultant, multi-ruolo) e la
  classe `UserRoles` esistevano gia' e sono riusate.

## Nuove pagine pubbliche
- **professionals.php** — directory pubblica: elenca gli utenti con almeno un ruolo
  E `public_contact = 1`, con badge ruolo e link "Contact". Linkata nell'header sotto
  **Suppliers -> Professionals** (chiave i18n `nav.professionals` in en/it/fr/de).
- **contact_professional.php?id=** — pagina di contatto: mostra ruoli, **email e
  telefono** (solo se l'utente e' consenziente) e un **form di invio mail** (via
  `Mailer::send`, Reply-To = email del mittente), con CSRF one-shot e honeypot.
  Carica il profilo solo se `public_contact = 1` e con almeno un ruolo.

## Registrazione e profilo (scelta ruolo + consenso)
- **01_login/newregister.php** — aggiunti checkbox ruoli (Expert/PM/Consultant) e
  checkbox consenso directory pubblica. Il telefono era gia' nel form.
- **01_login/register.php** — dopo l'INSERT salva i ruoli scelti (`UserRoles::addRole`)
  e imposta `public_contact` se consenso dato (non bloccante).
- **01_login/account_roles.php** — in profilo aggiunti: campo **telefono** e checkbox
  **consenso directory pubblica**; al salvataggio aggiorna `users.public_contact` e
  `users.phone` oltre ai ruoli.

## Sicurezza / vincoli
- CSRF su tutti i form; honeypot anti-spam; isolamento (ognuno edita solo il proprio
  profilo). Email/telefono pubblici solo con consenso esplicito (GDPR-friendly).
- Solo classi del foglio di stile esistente (badge, post_box, more, flash_ok/err,
  templatemo_list). L'unico inline style e' l'honeypot `display:none` (eccezione
  honeypot gia' in uso nel progetto). CRLF preservati.

## Verifiche
- Full-project `php -l`: 264 file, 0 errori.

## Ordine di applicazione
1. Esegui il patch SQL `2026-06-20_user_public_contact.sql`.
2. Carica i file PHP + i 4 `lang/*.php` + `header.php`.
3. Prova: registra un utente scegliendo un ruolo + consenso; verifica che compaia in
   `professionals.php` e che il form di contatto invii la mail.
