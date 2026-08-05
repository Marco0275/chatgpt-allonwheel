# Audit P0 — CSRF / IDOR sui form legacy — ESITO: CHIUSO

Scansione di tutti i 51 file che gestiscono POST e di tutti gli UPDATE/DELETE.

## CSRF — tutti i form che modificano stato sono protetti
- I tre `delete` (`02_delete_ad`, `03_delete_ad`, `06_40_delete_company`) usano un
  controllo CSRF **inline** (`hash_equals($_SESSION['csrf_token'], $_POST[...])`),
  non la funzione `csrf_verify()` — per questo un primo grep li segnalava: **falsi
  positivi**. Hanno CSRF + ownership (`WHERE … AND id_user/user_id`). OK.
- `send_reset_link.php` / `save_new_password.php`: il reset password è protetto dal
  **token monouso** (64 hex, `reset_expires > NOW()`), che è il pattern corretto e
  funge da anti-CSRF (un attaccante non può forgiare la richiesta senza il token
  inviato via email). OK.
- `02_preview_ad` / `03_preview_ad`: **non scrivono** sul DB e l'output è
  `htmlspecialchars`-escaped → CSRF non necessario. OK.
- `cookie_banner/consent_log.php`: beacon di consenso anonimo, rischio trascurabile.

## IDOR — ownership presente ovunque serve
- Tutti gli UPDATE/DELETE utente hanno `WHERE … AND id_user/user_id` o passano da
  helper con ownership (es. `deleteCompany`, `ownsAd`).
- Pagine `_admin/*` senza ownership per-utente: corretto, sono protette da
  `AdminAuth::requireAdminSession()` e operano su tutti i record per design.
- `scripts/purge_personal_data.php`: **CLI-only**; via HTTP richiede `CRON_TOKEN`
  (header o `?token=`), altrimenti 403. OK.
- Nuovo sistema documenti (Sprint 1): CSRF + `ownsAd` + magic bytes + proxy. OK.

## Note (non vulnerabilità, miglioramenti facoltativi futuri)
- Uniformare i tre `delete` su `csrf_verify()` (oggi inline) per coerenza.
- Valutare rate-limiting su `send_reset_link.php` (oggi assente).

**Conclusione:** nessun buco CSRF/IDOR. **P0 chiuso.** Si procede con Sprint 2.
