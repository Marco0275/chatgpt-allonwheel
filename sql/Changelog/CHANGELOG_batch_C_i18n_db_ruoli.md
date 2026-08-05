# Allonwheel — Batch C: RFQ one-to-one + i18n contenuti DB + fondamenta ruoli

Base: tua ZIP `Allonwheel_V_0_0_13_i18n.zip`. 10 file (2 nuovi).
`php -l` OK su 186 file. CRLF preservati (eccetto `06_11_save_company.php`, già LF
nella tua zip: l'ho lasciato com'era senza convertirlo).

---

## C6 — RFQ one-to-one: CONFERMATO, nessuna modifica
`Mailer::send($to, …, $replyTo)` usa `addAddress($to)` **singolo** + `addReplyTo`
(il 4° parametro è **Reply-To**, non un destinatario); niente CC/BCC. Il broadcast
invia **un'email per azienda** (loop, un solo `to`) e la copia a `rfq@allonwheel.com`
è un **invio separato singolo**. È già strettamente one-to-one: nessun indirizzo
multiplo, nessun CC/CCN. Lasciato invariato.

## C7 — i18n contenuti DB (IT) — COMPLETO
Tradotti: **intro macro** (`product_macros.intro_text`) e **descrizione azienda**
(`06_company.descrizione`). Confermato schema URL `/en/` `/it/`.

- **Patch SQL** `sql/Changelog/2026-06-17_i18n_db_and_roles.sql`:
  `product_macros.intro_text_it`, `06_company.descrizione_it` (+ `wants_pm_list`,
  + tabella `user_roles`, vedi sotto). **Esegui questa patch.**
- **Helper** `aow_i18n_field($row, 'campo')` in `config/i18n.php`: restituisce
  `campo_it` se locale IT ed è valorizzato, altrimenti `campo` (fallback EN). Verificato.
- **Display wirato**:
  - `browse.php` (intro macro): la query ora seleziona `intro_text_it` e usa l'helper.
  - `06_company/06_02_view_company.php` (descrizione del profilo + meta description).
  - `06_company/06_30_company_directory.php` (snippet descrizione in elenco).
- **Input wirato** (così le aziende possono inserire l'italiano):
  - `06_10_register_company.php` e `06_20_modify_company.php`: aggiunta textarea
    **Description (Italian)** sotto quella inglese.
  - Persistenza via nuovo metodo sicuro `CompanyManager::saveCompanyPrefs()`
    (UPDATE dedicato: **non** tocca i bind posizionali di insert/update → zero rischio
    di regressione sul salvataggio azienda). Chiamato da `06_11_save_company.php` e
    da `06_20_modify_company.php`.
- **Intro macro IT**: non c'è una UI admin per `product_macros`; imposta i testi IT
  via SQL, es. `UPDATE product_macros SET intro_text_it='…' WHERE slug='race-trailer';`
  (finché `intro_text_it` è vuoto, browse mostra l'inglese).

## Ruoli + checkbox — FONDAMENTA (schema + helper + checkbox azienda)
- **Tabella `user_roles`** (multi-ruolo) creata nella patch SQL: `user_id` +
  `role enum('expert','project_manager','consultant')`, UNIQUE su `(user_id, role)`.
- **Classe `libs/user_roles.class.php`** (`UserRoles`): `getRoles`, `hasRole`,
  `addRole` (idempotente), `removeRole`, `getUsersByRole` (per l'elenco PM/consulenti).
- **Checkbox azienda** "Receive the list of project managers & consultants" aggiunta
  ai form di registrazione/modifica azienda; salvata in `06_company.wants_pm_list`
  via `saveCompanyPrefs`.

---

## 🔜 Prossimo blocco (le parti grandi, sulla base appena creata)

Ora che ci sono tabella ruoli, helper e checkbox, il prossimo step è l'UI + la logica:

1. **Auto-iscrizione ruoli (Esperto / PM / Consulente)** — pagina nell'area utente
   (post-iscrizione, scelta libera) che usa `UserRoles::addRole/removeRole`. Multi-ruolo.
2. **Forum** (riuso `blog` + `blog_comments`): thread = articolo, risposte = commenti.
   L'Esperto risponde; evidenziazione risposte dell'esperto (ruolo).
3. **Notifiche email** ai partecipanti del thread (autore + chiunque abbia risposto)
   a ogni nuova risposta/aggiornamento, con link alla conversazione. **Dipende da SMTP
   attivo** (B2): finché l'SMTP non è configurato, le notifiche non partono.
4. **Invio elenco PM/consulenti** alle aziende con `wants_pm_list = 1`
   (usa `UserRoles::getUsersByRole`).
5. **Configuratore PDF (D8)** — dopo `composer require mpdf/mpdf` (dipendenze mancanti).
6. **Revisione sidebar** (regola "no link già nel contenuto") — meglio dopo il forum,
   così le sidebar riflettono già le nuove sezioni.

Confermami l'ordine (suggerito: 1→2→3→4) e procedo. Per il forum, dimmi se vuoi
marcare visivamente le risposte dell'Esperto (badge "Expert") nel thread.

## Promemoria azioni tue (dai batch precedenti)
- Esegui le due patch SQL: `2026-06-17_macro_hero_images.sql` e
  `2026-06-17_i18n_db_and_roles.sql`.
- Imposta `HISTATS_ID` (ancora vuoto).
- Configura SMTP nel `.env` (sblocca le notifiche del forum).
- `composer require mpdf/mpdf` (sblocca il PDF).
