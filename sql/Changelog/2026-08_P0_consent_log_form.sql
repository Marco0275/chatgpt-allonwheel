-- P0.3 - Registro consensi dei FORM (Art. 7(1) GDPR).
-- Riusa la tabella `consent_log` (gia' usata per i cookie) aggiungendo la
-- colonna `form` per tracciare da quale form arriva il consenso.
-- Idempotente e compatibile MySQL 5.7 (niente ADD COLUMN IF NOT EXISTS).

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'consent_log'
             AND COLUMN_NAME = 'form');
SET @sql := IF(@c = 0,
  'ALTER TABLE `consent_log` ADD COLUMN `form` VARCHAR(40) NULL DEFAULT NULL AFTER `action`',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
