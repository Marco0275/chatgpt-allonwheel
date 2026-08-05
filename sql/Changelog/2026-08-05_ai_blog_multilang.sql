-- ============================================================
-- 2026-08-05 — Autopublisher AI multilingua (IT + traduzioni EN/FR/DE)
-- Idempotente, MySQL 5.7-safe (nessun ADD COLUMN IF NOT EXISTS: guardie via
-- INFORMATION_SCHEMA in stored procedure). Non distruttivo (dir. 9/19).
-- ============================================================
DELIMITER //

DROP PROCEDURE IF EXISTS aow_add_col //
CREATE PROCEDURE aow_add_col(IN tb VARCHAR(64), IN co VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns
      WHERE table_schema=DATABASE() AND table_name=tb AND column_name=co) THEN
    SET @s=CONCAT('ALTER TABLE `',tb,'` ADD COLUMN ',ddl); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //

DROP PROCEDURE IF EXISTS aow_add_index //
CREATE PROCEDURE aow_add_index(IN tb VARCHAR(64), IN ix VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics
      WHERE table_schema=DATABASE() AND table_name=tb AND index_name=ix) THEN
    SET @s=CONCAT('ALTER TABLE `',tb,'` ADD ',ddl); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //

DELIMITER ;

-- blog: colonna che lega le versioni tradotte + indici utili
CALL aow_add_col('blog','translation_group', "`translation_group` CHAR(36) NULL DEFAULT NULL COMMENT 'UUID condiviso dalle versioni tradotte' AFTER `language`");
CALL aow_add_index('blog','ix_blog_lang_status', "INDEX `ix_blog_lang_status` (`language`,`status`)");
CALL aow_add_index('blog','ix_blog_txgroup', "INDEX `ix_blog_txgroup` (`translation_group`)");

-- editorial_queue: dedup del piano (niente articoli doppi al re-import)
CALL aow_add_index('editorial_queue','uq_eq_source', "UNIQUE KEY `uq_eq_source` (`source_file`(150),`source_row`)");

-- guardia idempotente "1 articolo/giorno"
CREATE TABLE IF NOT EXISTS `ai_daily_log` (
  `run_date` DATE NOT NULL,
  `blog_id`  INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`run_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- coda traduzioni per i post UMANI (async, la drena il cron)
CREATE TABLE IF NOT EXISTS `blog_translation_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` INT UNSIGNED NOT NULL,
  `from_lang` CHAR(2) NOT NULL,
  `translation_group` CHAR(36) NOT NULL,
  `status` ENUM('pending','done','error') NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_btj_status` (`status`),
  UNIQUE KEY `uq_btj_blog` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS aow_add_col;
DROP PROCEDURE IF EXISTS aow_add_index;
