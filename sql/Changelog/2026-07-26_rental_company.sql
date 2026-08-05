-- ============================================================
-- 2026-07-26_rental_company.sql
-- Feature NOLEGGIO (07_rent) - parte 1: azienda.
--  - offers_rental: l'azienda dichiara di offrire il noleggio di veicoli speciali
--  - general_note : nota unica (sostituisce le note per-riga di service/product/special,
--                   rimosse dal form 06_10_register_company.php)
--
-- GARANZIE: non distruttiva (dir. 9, solo ADD COLUMN), idempotente
-- (procedura su information_schema; MySQL 5.7 non ha ADD COLUMN IF NOT EXISTS).
-- ============================================================

DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
DELIMITER $$
CREATE PROCEDURE `aow_add_col_if_missing`(
  IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_ddl TEXT
)
BEGIN
  IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL aow_add_col_if_missing('06_company', 'offers_rental',
  "`offers_rental` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=azienda offre noleggio veicoli speciali'");
CALL aow_add_col_if_missing('06_company', 'general_note',
  "`general_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Nota unica del form registrazione azienda'");

DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
