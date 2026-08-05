# Allonwheel — Invio elenco PM/Consulenti alle aziende

Base: tua ZIP. 2 file (1 nuovo). `php -l` OK su tutto il progetto. CRLF preservati.

## Cosa fa
- **`_admin/admin_pm_list.php`** (nuova voce admin "PM/Consultant list"):
  - mostra l'elenco corrente dei **Project manager** e dei **Consulenti**
    (utenti con quel ruolo in `user_roles`: username, email, telefono);
  - mostra le **aziende che hanno spuntato** "ricevi elenco PM/consulenti"
    (`06_company.wants_pm_list = 1`, attive, con email);
  - bottone **"Send list to companies"**: invia l'elenco a **ciascuna azienda
    opt-in** con un'email **one-to-one** (un solo destinatario per messaggio),
    e riporta quante sono partite.
- **`_admin/admin_header.php`**: aggiunta la voce di navigazione.

Richiede **SMTP attivo** per l'invio reale (altrimenti fallback `mail()`).
Solo admin (CSRF, `AdminAuth`).

## Stato sistema ruoli/forum (completo)
- Auto-iscrizione ruoli (Esperto / PM / Consulente) in Account settings — fatto.
- Badge **Expert** nei post/commenti del forum — fatto.
- **Notifica email** ai partecipanti del thread a ogni nuova risposta — fatto.
- **Invio elenco PM/consulenti** alle aziende opt-in — fatto (questo batch).

## Promemoria
- SMTP nel `.env` per far partire notifiche forum e invio elenco.
- L'elenco si invia **a comando** dall'area admin (non automatico), così controlli tu
  quando spedirlo.
