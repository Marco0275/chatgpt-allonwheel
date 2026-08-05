-- ============================================================
-- 2026-07-27 — Alert di ricerca aperti ai visitatori non registrati.
--
-- Perche': con l'inventario ancora vuoto, la ricerca senza risultati e' la
-- pagina piu' vista del sito. Finora chiedeva un account per attivare un
-- alert: la quasi totalita' della domanda in arrivo se ne andava senza
-- lasciare traccia. Ora l'alert si attiva con la sola email, con conferma
-- via link (doppio opt-in: il consenso deve essere dimostrabile, GDPR
-- art. 7 c.1), e il salvataggio resta invariato per gli utenti loggati.
--
-- Sicurezza dei dati: nessuna colonna viene rimossa o rinominata, nessuna
-- riga esistente viene modificata. Le tre colonne sono nullable, quindi le
-- righe gia' presenti restano valide (id_user > 0 = consenso gia' provato
-- dall'azione autenticata).
--
-- Applicazione:
--   mysql -u UTENTE -p NOME_DB < sql/migrations/2026_07_27_guest_alerts.sql
-- Idempotente su MariaDB 10.6 / MySQL 8 grazie a IF NOT EXISTS.
-- ============================================================

ALTER TABLE `saved_searches`
  ADD COLUMN IF NOT EXISTS `vtype`         VARCHAR(80) DEFAULT NULL AFTER `q`,
  ADD COLUMN IF NOT EXISTS `confirm_token` CHAR(32)    DEFAULT NULL AFTER `token`,
  ADD COLUMN IF NOT EXISTS `confirmed_at`  DATETIME    DEFAULT NULL AFTER `confirm_token`;

-- Gli alert dei guest partono solo dopo la conferma: l'indice serve al cron,
-- che ora filtra anche su confirmed_at.
ALTER TABLE `saved_searches`
  ADD INDEX IF NOT EXISTS `idx_confirm` (`confirm_token`),
  ADD INDEX IF NOT EXISTS `idx_email`   (`email`);

-- Le righe create prima di oggi appartengono tutte a utenti autenticati:
-- il consenso e' gia' documentato, quindi si marcano come confermate per
-- non interrompere gli invii in corso.
UPDATE `saved_searches`
   SET `confirmed_at` = `created_at`
 WHERE `confirmed_at` IS NULL
   AND `id_user` > 0;
