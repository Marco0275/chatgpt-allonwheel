# All on Wheel — Audit GDPR & Cybersecurity
### allonwheel.com · All on Wheel Ltd
**Data:** 1 giugno 2026 · **Versione:** 1.0
**Base dell'analisi:** codice sorgente PHP effettivo (≈142 file), dump del database, configurazione server (.htaccess, header HTTP), verifica della home page live.

> **Avvertenza professionale.** Questo documento è un audit tecnico-operativo redatto sulla base del codice fornito. Non costituisce parere legale vincolante: i testi giuridici (Privacy Policy, Cookie Policy, registro trattamenti, DPIA) vanno validati da un DPO o legale privacy abilitato prima della pubblicazione. Gli "esempi di attacco" sono descrizioni concettuali a scopo di remediation, non exploit operativi.

---

## Sintesi esecutiva

Il codice di All on Wheel è, sul piano della **sicurezza applicativa, già a un buon livello**: query PDO parametrizzate, CSRF, bcrypt cost 12, hardening della sessione, anti-brute-force, upload immagini con validazione MIME reale + strip EXIF + blocco esecuzione PHP nella cartella di upload. Le criticità non sono diffuse ma **puntuali e ad alto impatto**.

Sul piano **GDPR/ePrivacy** la situazione è intermedia: esistono informativa privacy e cookie policy, e — fatto importante — **il sito non usa attualmente alcun tracker di terze parti** (niente GA4, GTM, Meta Pixel, reCAPTCHA, Google Fonts, YouTube embed; Histats è stato rimosso). L'unico cookie è quello tecnico di sessione `PHPSESSID`. Le lacune principali riguardano l'**esercizio dei diritti dell'interessato in-app** (cancellazione/esportazione), la **data retention** e l'**infrastruttura di consenso** da predisporre prima di introdurre qualsiasi tracker.

| Dominio | Score | Giudizio |
|---|---|---|
| **GDPR / ePrivacy** | **61 / 100** | Parziale — buona sicurezza del trattamento e informative, lacune su diritti e retention |
| **Cybersecurity (OWASP)** | **72 / 100** | Buono, ma trascinato in basso da 1 criticità P0 (credenziali esposte). Sale a ~86 una volta chiusa. |

**Le 3 azioni più urgenti (P0):**
1. **Credenziali DB e mail esposte/scaricabili** (`config/env`) → rotazione immediata + spostamento fuori webroot.
2. **CSP in sola modalità report-only** → non protegge da XSS finché non viene messa in enforce.
3. **Nessuna procedura in-app per cancellazione/esportazione dati** (Art. 15/17/20 GDPR).

---

## 1. Audit GDPR completo

### 1.1 Trattamenti rilevati (dal codice)

| # | Trattamento | Dati personali | Base giuridica (Art. 6) | Dove |
|---|---|---|---|---|
| T1 | Registrazione utente | username, email, telefono, password (hash) | 6(1)(b) contratto | `01_login/register.php`, tab `users` |
| T2 | Autenticazione | email, password, IP | 6(1)(b) + 6(1)(f) sicurezza | `01_login/login.php` |
| T3 | Anti-brute-force | email, **IP**, timestamp | 6(1)(f) legittimo interesse | tab `login_attempts` |
| T4 | Pubblicazione annunci | autore, email, telefono, immagini | 6(1)(b) | `02_free_ads`, `03_ads` |
| T5 | Directory aziende | dati azienda, immagini | 6(1)(b) | `06_company` |
| T6 | Form contatto | nome, email, messaggio | 6(1)(f) / 6(1)(a) | `contact_submit.php` (invio via `mail()`) |
| T7 | Recupero password | email, reset_token, scadenza | 6(1)(b) | `01_login/forgot_password.php` |
| T8 | Verifica email | email, verification_token | 6(1)(b) | `01_login/verify.php` |
| T9 | Audit amministrativo | azioni admin | 6(1)(c)/(f) | tab `admin_audit_log` |

**Categorie particolari (Art. 9):** nessuna rilevata. **Minori:** nessun trattamento dedicato.

### 1.2 Esito per ciascun principio (Art. 5)

| Principio | Esito | Note |
|---|---|---|
| Liceità/correttezza/trasparenza | 🟡 Parziale | Informativa presente; manca link a Cookie Policy nel footer; basi giuridiche non esplicitate per trattamento. |
| Limitazione finalità | 🟢 OK | Dati usati per le finalità dichiarate. |
| Minimizzazione | 🟡 Parziale | Telefono raccolto in registrazione: valutare se necessario al momento del signup o solo alla pubblicazione. |
| Esattezza | 🟢 OK | Profilo modificabile. |
| **Limitazione conservazione** | 🔴 Carente | Nessuna retention per `login_attempts` (IP), email di contatto, account inattivi. Annunci: OK (`expires_at` + cron). |
| **Integrità e riservatezza (Art. 32)** | 🟡 Parziale | Buona ma con la criticità P0 credenziali (§2.1). |
| Responsabilizzazione | 🔴 Carente | Manca registro trattamenti (Art. 30), DPIA, log consensi. |

### 1.3 Diritti dell'interessato (Art. 15–22)

| Diritto | Stato | Gap |
|---|---|---|
| Accesso (15) | 🟡 | Profilo visibile, ma nessun "scarica i miei dati". |
| Rettifica (16) | 🟢 | `mydetails.php`. |
| **Cancellazione/oblio (17)** | 🔴 | **Nessuna funzione di cancellazione account.** |
| **Portabilità (20)** | 🔴 | **Nessun export JSON/CSV.** |
| Opposizione (21) | 🟡 | Solo via contatto. |
| Decisioni automatizzate (22) | 🟢 | N/A (nessuna profilazione). |

**Riferimenti:** GDPR Art. 5, 6, 12–22, 30, 32; Linee guida EDPB 01/2022 (diritti accesso), 4/2019 (privacy by design); Garante — provvedimenti su cookie 10/06/2021.

---

## 2. Audit sicurezza completo

Legenda gravità: 🔴 Critica · 🟠 Alta · 🟡 Media · 🔵 Bassa.

### 2.1 🔴 [P0] Credenziali DB e mail esposte e potenzialmente scaricabili
- **File:** `config/env` (contiene `DB_PASSWORD`, `MAIL_PASSWORD` in chiaro).
- **Causa tecnica:** il file si chiama `env` (senza punto). La regola in `.htaccess` blocca `^\.env` (con punto), quindi **`/config/env` non è coperto** e, a seconda del server, è servito come testo. Inoltre le credenziali sono comunque presenti nel pacchetto/ZIP e vanno considerate compromesse.
- **Rischio sicurezza:** accesso completo al database e all'account mail → esfiltrazione dati, defacement, invio spam dal dominio. **Rischio GDPR:** data breach notificabile (Art. 33/34), sanzioni Art. 83(5).
- **Esempio di attacco:** richiesta diretta `GET https://www.allonwheel.com/config/env` → il browser scarica le password.
- **Soluzione tecnica:**
  1. **Ruotare subito** password DB e mail.
  2. Spostare il file **fuori dalla webroot** (es. `/home/utente/.env`, già previsto da `bootstrap.php`).
  3. Rimuovere `config/env` dal repository e dai pacchetti.
  4. Estendere la regola `.htaccess`.
```apache
# .htaccess — blocca sia .env sia env (e varianti)
<FilesMatch "(^\.env|^env$|\.env\..*|\.git|composer\.(lock|json)|README|DIRECTORY_STRUCTURE)">
    Require all denied
</FilesMatch>
```

### 2.2 🟠 [P1] Content-Security-Policy solo in "report-only"
- **File:** `config/security_headers.php` — `ALLONWHEEL_CSP_ENFORCE = false`.
- **Rischio:** la CSP non blocca nulla; in caso di XSS l'iniezione di script non viene impedita. Inoltre `script-src 'unsafe-inline'` indebolisce comunque la difesa.
- **Esempio di attacco:** un payload XSS riflesso/stored eseguirebbe JS arbitrario; con CSP enforce + senza `unsafe-inline` verrebbe bloccato.
- **Soluzione:** convertire i pochi handler inline (`onfocus`/`onsubmit`) in delega eventi (già avviato in `site_init.js`), poi:
```php
define('ALLONWHEEL_CSP_ENFORCE', true);
$csp = implode('; ', [
  "default-src 'self'",
  "script-src 'self'",          // niente 'unsafe-inline'
  "style-src 'self' 'unsafe-inline'", // mantenere finché restano stili inline
  "img-src 'self' data:",
  "object-src 'none'", "base-uri 'self'", "form-action 'self'",
  "frame-ancestors 'self'",
]);
header('Content-Security-Policy: ' . $csp);
```

### 2.3 🟡 Form contatto privo di token CSRF
- **File:** `contact_submit.php`. Presenti honeypot (`test`) e controllo temporale (`momento_del_caricamento`), email validata, anti-open-redirect. **Manca il token CSRF.**
- **Rischio:** invio forzato del form da terzi (abuso di `mail()`).
- **Soluzione:** aggiungere `csrf_generate()` nel form `contact.php` e `csrf_verify()` all'inizio di `contact_submit.php` (vedi §14).

### 2.4 🟡 Rate-limit brute-force "fail-open"
- **File:** `01_login/login.php`. Se la query su `login_attempts` fallisce, il login prosegue senza limiti.
- **Rischio:** in caso di errore DB isolato la protezione si disattiva.
- **Soluzione:** in `catch`, applicare un limite prudenziale (es. throttling temporizzato) anziché proseguire libero.

### 2.5 🟡 Componenti front-end datati (OWASP A06)
- jQuery e **piroBox 1.2.1 (2009)** sono librerie molto vecchie. piroBox contiene codice legacy e dipende da jQuery 1.x.
- **Rischio:** vulnerabilità note nelle versioni datate di jQuery.
- **Soluzione:** verificare la versione di `js/jquery.min.js`; pianificare aggiornamento a jQuery 3.7+ valutando un lightbox moderno (es. GLightbox) — attenzione: aggiornare jQuery può rompere piroBox.

### 2.6 🔵 Hardening minori
- Cookie di sessione `secure` valutato solo su `$_SERVER['HTTPS']`: dietro proxy aggiungere il check `X-Forwarded-Proto` (come già fa `security_headers.php`).
- Nessun timeout di sessione assoluto/inattività; nessuna MFA (consigliata per l'admin).
- `.htaccess` imposta `X-Frame-Options` sia `DENY` sia `SAMEORIGIN` (duplicato/conflitto) e HSTS con `preload` mentre il PHP lo imposta senza: uniformare la sorgente degli header.
- Reset/verification token salvati in chiaro: valutare hashing a riposo (`hash('sha256', $token)`).

### 2.7 Controlli verificati come ADEGUATI ✅
- **SQL Injection:** query PDO con bind ovunque verificato (login, delete, vehicles, portfolio). *Da confermare il modulo `06_company` (MySQLi).*
- **XSS:** output con `htmlspecialchars(..., ENT_QUOTES)` esteso; ricerca `q` escapata.
- **CSRF:** helper robusto (`random_bytes(32)`, `hash_equals`, one-shot + persistent, 403).
- **Session Fixation:** `session_regenerate_id(true)` al login.
- **Session Hijacking:** cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` su HTTPS).
- **IDOR:** delete con `WHERE id_ads=? AND id_user=?` (ownership nella query). *Confermare lo stesso pattern in tutti i `*_modify_*`.*
- **File Upload:** `is_uploaded_file`, MIME via `finfo`, `getimagesize`, limite pixel, whitelist directory, filename random, **re-encoding GD (strip EXIF/GPS)**, `.htaccess` che **disabilita l'esecuzione PHP** nella cartella upload.
- **Auth:** bcrypt cost 12, anti-brute-force email+IP, messaggi generici (no user enumeration), verifica email.

---

## 3. Checklist GDPR operativa

- [ ] **P0** Spostare `.env` fuori webroot + ruotare credenziali esposte.
- [ ] Pubblicare il link **Cookie Policy** nel footer accanto a Privacy.
- [ ] Implementare **cancellazione account** (Art. 17) con conferma + cancellazione/anonimizzazione annunci.
- [ ] Implementare **esportazione dati** JSON (Art. 20).
- [ ] Definire e applicare **retention**: `login_attempts` (es. purge > 90 gg), email contatto (es. 24 mesi), account inattivi (policy).
- [ ] Esplicitare **basi giuridiche** per ogni trattamento nell'informativa.
- [ ] Predisporre **Registro dei trattamenti** (Art. 30) — §9.
- [ ] Predisporre **DPIA semplificata** — §11.
- [ ] Predisporre **log consensi** prima di introdurre tracker — §10.
- [ ] Procedura **data breach** (Art. 33/34) documentata.
- [ ] Nominare un referente privacy / valutare necessità DPO (Art. 37).
- [ ] DPA (Art. 28) con hosting e provider mail (responsabili del trattamento).
- [ ] Verificare collocazione server/backup (UE) e trasferimenti extra-UE.

---

## 4. Checklist OWASP Top 10 (2021)

| Cat. | Tema | Stato | Azione |
|---|---|---|---|
| A01 | Broken Access Control | 🟢 con riserva | Confermare ownership in tutti i `*_modify_*`. |
| A02 | Cryptographic Failures | 🟠 | **config/env esposto (P0)**; hashare reset token. bcrypt OK. |
| A03 | Injection | 🟢 con riserva | Confermare prepared statements nel modulo MySQLi 06_company. |
| A04 | Insecure Design | 🟡 | Rate-limit fail-open; valutare MFA admin. |
| A05 | Security Misconfiguration | 🟠 | CSP report-only + `unsafe-inline`; header duplicati; `display_errors` off in prod. |
| A06 | Vulnerable Components | 🟡 | Aggiornare jQuery; valutare sostituzione piroBox. |
| A07 | Identification & Auth | 🟢 con riserva | Solido; aggiungere MFA admin + timeout sessione. |
| A08 | Software/Data Integrity | 🔵 | Niente CI/SRI; rischio basso (librerie locali). |
| A09 | Logging & Monitoring | 🟡 | `admin_audit_log` + `error_log` presenti; centralizzare e definire retention dei log. |
| A10 | SSRF | 🟢 | Nessuna fetch lato server su input utente. |

---

## 5. Piano di remediation (per priorità)

| Prio | Intervento | Sforzo | File |
|---|---|---|---|
| **P0** | Ruotare credenziali, spostare `.env` fuori webroot, fix `.htaccess` | 1h | `.htaccess`, `config/env`, server |
| **P0** | Cancellazione account (Art. 17) | 4h | nuovo `01_login/delete_account.php` |
| **P0** | Esportazione dati (Art. 20) | 3h | nuovo `01_login/export_data.php` |
| **P1** | CSP in enforce + rimozione `unsafe-inline` script | 3h | `security_headers.php`, handler inline |
| **P1** | Retention `login_attempts` + email contatto (cron) | 2h | nuovo `scripts/purge_personal_data.php` |
| **P1** | CSRF sul form contatto | 0.5h | `contact.php`, `contact_submit.php` |
| **P2** | Link Cookie Policy nel footer | 0.2h | `footer.php` |
| **P2** | Infrastruttura consenso + banner (per futuri tracker) | 4h | bundle `cookie_banner/` |
| **P2** | Hardening minori (secure cookie dietro proxy, header duplicati, hashing token) | 2h | vari |
| **P3** | Aggiornare jQuery / sostituire piroBox | 6h | `js/` |
| **P3** | MFA admin + timeout sessione | 6h | `01_login/`, `_admin/` |

---

## 6. Privacy Policy conforme UE
*(vedi file allegato `Privacy_Policy_IT.md` — sostituisce/aggiorna `privacy.php`)*

## 7. Cookie Policy conforme UE
*(vedi file allegato `Cookie_Policy_IT.md` — sostituisce/aggiorna `cookie-policy.php`)*

## 8. Cookie Banner conforme Garante 2025
*(vedi bundle `cookie_banner/` — banner + JS + endpoint log + SQL)*

> **Nota di applicabilità:** allo stato attuale il sito usa **solo il cookie tecnico `PHPSESSID`**, esente da consenso preventivo (ePrivacy Art. 5(3); Garante, Linee guida cookie 2021 §3.1). **Un banner di consenso non è quindi obbligatorio oggi.** Diventa obbligatorio **prima** di attivare qualsiasi cookie non tecnico (GA4, Meta Pixel, ecc.). Il bundle è fornito pronto per quel momento, con blocco preventivo degli script e Google Consent Mode v2.

---

## 9. Registro dei trattamenti (Art. 30) — estratto

| Trattamento | Finalità | Base giuridica | Categorie interessati | Dati | Conservazione | Destinatari | Trasf. extra-UE |
|---|---|---|---|---|---|---|---|
| Account utente | Erogazione servizio | Contratto 6(1)(b) | Utenti registrati | username, email, tel, hash pwd | Durata account + 30 gg | Hosting | Da verificare |
| Sicurezza accessi | Anti-frode/brute-force | Leg. interesse 6(1)(f) | Visitatori/utenti | email, IP, timestamp | **90 gg (da impostare)** | — | No |
| Annunci | Pubblicazione | Contratto 6(1)(b) | Inserzionisti | dati annuncio, immagini | TTL 45/60 gg (già attivo) | Pubblico | No |
| Contatti | Risposta richieste | Leg. interesse 6(1)(f) | Mittenti | nome, email, messaggio | **24 mesi (da impostare)** | Provider mail | Da verificare |
| Audit admin | Sicurezza/accountability | Obbligo/leg. int. | Admin | azioni, timestamp | 12–24 mesi | — | No |
| Recupero password | Sicurezza account | Contratto 6(1)(b) | Utenti | email, token, scadenza | Token: 1h | Provider mail | Da verificare |

*Titolare:* All on Wheel Ltd. *Contatto privacy:* da pubblicare (es. privacy@allonwheel.com).

---

## 10. Registro consensi

Attualmente **non necessario** (nessun trattamento basato sul consenso: account e annunci = contratto; sicurezza/contatti = legittimo interesse). Da attivare quando si introdurranno cookie di marketing/profilazione o una newsletter. Schema proposto (incluso nel bundle, `sql/consent_log.sql`):

| Campo | Tipo | Scopo |
|---|---|---|
| id | BIGINT PK | — |
| consent_id | CHAR(36) | UUID lato client (cookie 1st-party) |
| ip_hash | CHAR(64) | SHA-256 dell'IP (prova senza conservare l'IP in chiaro) |
| user_agent | VARCHAR(255) | contesto |
| categories | JSON | es. `{"technical":true,"analytics":false,"marketing":false}` |
| consent_version | VARCHAR(20) | versione informativa accettata |
| action | ENUM('grant','deny','update','withdraw') | granularità + revoca |
| created_at | DATETIME | data/ora (prova del consenso, Art. 7(1)) |

---

## 11. DPIA semplificata

- **Necessità DPIA completa (Art. 35):** **NO** in base ai criteri EDPB (WP248): no profilazione su larga scala, no categorie particolari, no monitoraggio sistematico, no dati di minori.
- **Rischi residui principali e mitigazioni:**

| Rischio | Probab. | Impatto | Mitigazione |
|---|---|---|---|
| Esfiltrazione DB via credenziali esposte | Media | Alto | P0 §2.1 (rotazione + .env fuori webroot) |
| Data breach via XSS | Bassa | Medio | CSP enforce (P1) |
| Conservazione illimitata IP/contatti | Alta | Medio | Retention + cron purge (P1) |
| Mancato esercizio diritti | Media | Medio | Funzioni erasure/export (P0) |

**Esito:** rischio complessivo **medio**, riducibile a **basso** completando P0–P1.

---

## 12. Score finale GDPR: **61 / 100**
Punti di forza: informativa + cookie policy presenti, nessun tracker, sicurezza del trattamento solida, bcrypt. Penalità: diritti (erasure/export) assenti, retention non definita, registro/DPIA/consensi da formalizzare, cookie policy non linkata.

## 13. Score finale Cybersecurity: **72 / 100**
Solida base (prepared statements, CSRF, bcrypt12, upload hardening, sessione). Penalità principali: credenziali esposte (P0, −12), CSP non enforce + `unsafe-inline` (−6), CSRF contatto (−3), fail-open rate-limit (−2), componenti datati (−3), MFA assente (−2). **Chiudendo la P0 → ~84/100.**

---

## 14. Elenco modifiche codice, file per file

**`.htaccess`** — estendere il blocco a `env` (vedi §2.1).

**`config/security_headers.php`** — `ALLONWHEEL_CSP_ENFORCE=true`, rimuovere `'unsafe-inline'` da `script-src` dopo conversione handler inline.

**`config/session_helper.php`** — `secure` cookie anche via `X-Forwarded-Proto`; opzionale timeout sessione.

**`contact.php`** — inserire `<?php echo csrf_generate(); ?>` nel form.
**`contact_submit.php`** — `require_once config/csrf.php; csrf_verify();` in cima.

**`01_login/login.php`** — nel `catch` del rate-limit, non proseguire fail-open: applicare throttling.

**`footer.php`** — aggiungere link `cookie-policy.php`.

**NUOVO `01_login/delete_account.php`** (Art. 17) — esempio:
```php
<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$uid = require_user_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pdo->beginTransaction();
    try {
        // anonimizza/cancella annunci dell'utente prima di rimuovere l'account
        $pdo->prepare('DELETE FROM `02_free_ads` WHERE id_user = ?')->execute([$uid]);
        $pdo->prepare('DELETE FROM `03_ads`      WHERE id_user = ?')->execute([$uid]);
        $pdo->prepare('DELETE FROM `users`       WHERE id_user = ?')->execute([$uid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[Allonwheel] delete_account: ' . $e->getMessage());
        exit('Could not delete the account. Please contact support.');
    }
    logout_user();
    header('Location: ' . BASE_URL . '/index.php?account=deleted');
    exit;
}
// (GET) mostra pagina di conferma con form + csrf_generate()
```

**NUOVO `01_login/export_data.php`** (Art. 20) — esempio:
```php
<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

$uid = require_user_logged_in();
$out = [];
$u = $pdo->prepare('SELECT id_user, username, email, phone, created_at, user_tier FROM users WHERE id_user = ?');
$u->execute([$uid]); $out['account'] = $u->fetch();
foreach (['02_free_ads','03_ads'] as $t) {
    $s = $pdo->prepare("SELECT * FROM `$t` WHERE id_user = ?");
    $s->execute([$uid]); $out['ads'][$t] = $s->fetchAll();
}
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="allonwheel_my_data.json"');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

**NUOVO `scripts/purge_personal_data.php`** (retention, da cron giornaliero):
```php
<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
// IP/email tentativi login > 90 giorni
$pdo->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 90 DAY");
// (eventuale) tabella messaggi contatto > 24 mesi, se persistiti
echo "purge ok\n";
```

**`libs/06_company.class.php` (MySQLi)** — confermare che ogni query usi `prepare()` + `bind_param()` (non concatenazione). Da verificare in dettaglio.

---

## 15. Tabella sanzioni potenzialmente applicabili

| Scenario | Norma | Tetto sanzionatorio |
|---|---|---|
| Data breach da credenziali esposte | Art. 32 + 83(4) | fino a **10 M€ o 2%** del fatturato mondiale |
| Mancata notifica breach | Art. 33/34 + 83(4) | fino a **10 M€ o 2%** |
| Cookie non tecnici senza consenso valido (se attivati) | ePrivacy + Art. 83(5); Garante | fino a **20 M€ o 4%** |
| Mancato riscontro ai diritti (15/17/20) | Art. 12–22 + 83(5) | fino a **20 M€ o 4%** |
| Assenza registro trattamenti (Art. 30) | Art. 83(4) | fino a **10 M€ o 2%** |
| Informativa carente/assente (Art. 13) | Art. 83(5) | fino a **20 M€ o 4%** |

*Le sanzioni reali sono proporzionate (Art. 83(2)): gravità, natura, misure adottate. Per una PMI con misure correttive tempestive l'esposizione effettiva è molto inferiore ai tetti.*

---

*Fine audit — All on Wheel v1.0 · 1 giugno 2026. Documento di supporto tecnico; far validare i contenuti legali a un professionista abilitato.*
