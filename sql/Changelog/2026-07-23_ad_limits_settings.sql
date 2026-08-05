-- ============================================================
-- 2026-07-23_ad_limits_settings.sql
-- Limiti annunci configurabili da admin
--
-- CONTESTO
-- Il numero massimo di annunci pubblicabili era una costante nel codice
-- (UserTier::BASIC_TOTAL_LIMIT = 2, SILVER_TOTAL_LIMIT = 15): per cambiarlo
-- bisognava modificare un file PHP. Ora vive in site_settings e si cambia da
-- _admin/admin_ad_limits.php.
--
-- Il limite e' sul TOTALE degli annunci dell'utente (free + premium): e' la
-- semantica che UserTier gia' applicava, qui non cambia nulla.
-- 0 = illimitato.
--
-- GARANZIE
--  - NON distruttiva: inserisce due righe in una tabella esistente.
--  - Idempotente: ON DUPLICATE KEY non sovrascrive un valore gia' impostato
--    dall'admin, quindi si puo' rilanciare senza rimettere i default.
--  - MySQL 5.7 / MariaDB compatibile.
--
-- PREREQUISITO: 2026-07-20_site_settings.sql (crea la tabella site_settings).
-- Se non e' ancora applicata, applicarla prima: senza tabella il codice
-- ricade sui default di fabbrica e la pagina admin lo segnala.
-- ============================================================

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES
  ('ad_limit_free',    '2'),
  ('ad_limit_premium', '15')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---- VERIFICA (facoltativa) ---------------------------------
--   SELECT * FROM `site_settings` WHERE setting_key LIKE 'ad_limit_%';
--   Atteso: ad_limit_free = 2, ad_limit_premium = 15 (o i valori scelti
--   successivamente da admin).

-- Fine patch.
