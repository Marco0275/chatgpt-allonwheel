-- ============================================================
-- 2026-07-20_ad_dimensions.sql
-- Misure dimensionali NUMERICHE sugli annunci (base per le faccette)
--
-- CONTESTO E DECISIONE
-- Le misure oggi vivono solo in 03_ads_tech_details (premium) come varchar
-- (ext_length/width/height, axles): stringhe libere, non filtrabili, e assenti
-- sui free. Per avere un filtro dimensionale REALE servono numeri veri, sugli
-- annunci, disponibili sia per free che per premium.
--
-- Aggiungo quindi a 02_free_ads E 03_ads quattro colonne numeriche:
--   length_cm, width_cm, height_cm  (INT, in centimetri: niente virgole ne'
--                                    unita' ambigue - 12.5 m diventa 1250)
--   axles_n                         (TINYINT: numero di assi)
-- In centimetri come intero: un compratore filtra "lunghezza >= 1000 cm" in
-- modo affidabile, cosa impossibile su "12,5 m" varchar.
--
-- MIGRAZIONE DAI VARCHAR ESISTENTI
-- La tabella 03_ads_tech_details risulta VUOTA nel dump: non c'e' nulla da
-- migrare, le nuove colonne nascono NULL (nessun dato inventato, dir. 14).
-- Se in futuro ci fossero varchar popolati, la conversione va fatta a parte
-- con parsing tollerante (i non interpretabili -> NULL). Qui NON tocco i
-- varchar: restano dove sono, le colonne numeriche sono la fonte per i filtri.
--
-- GARANZIE
--  - NON distruttiva (dir. 9): aggiunge colonne, non tocca dati esistenti.
--  - NULL ammesso: un annuncio senza misure resta valido; il filtro
--    dimensionale semplicemente non lo include (come dev'essere).
--  - Idempotente: procedura su information_schema (5.7 non ha IF NOT EXISTS
--    su ADD COLUMN). Rieseguibile.
--  - MySQL 5.7 compatibile.
-- ============================================================

DROP PROCEDURE IF EXISTS `aow_add_dim_col`;
DELIMITER $$
CREATE PROCEDURE `aow_add_dim_col`(
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

-- 02_free_ads
CALL `aow_add_dim_col`('02_free_ads', 'length_cm', '`length_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Lunghezza in cm (filtri)''');
CALL `aow_add_dim_col`('02_free_ads', 'width_cm',  '`width_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Larghezza in cm''');
CALL `aow_add_dim_col`('02_free_ads', 'height_cm', '`height_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Altezza in cm''');
CALL `aow_add_dim_col`('02_free_ads', 'axles_n',   '`axles_n` TINYINT UNSIGNED DEFAULT NULL COMMENT ''Numero di assi''');

-- 03_ads
CALL `aow_add_dim_col`('03_ads', 'length_cm', '`length_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Lunghezza in cm (filtri)''');
CALL `aow_add_dim_col`('03_ads', 'width_cm',  '`width_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Larghezza in cm''');
CALL `aow_add_dim_col`('03_ads', 'height_cm', '`height_cm` INT UNSIGNED DEFAULT NULL COMMENT ''Altezza in cm''');
CALL `aow_add_dim_col`('03_ads', 'axles_n',   '`axles_n` TINYINT UNSIGNED DEFAULT NULL COMMENT ''Numero di assi''');

DROP PROCEDURE IF EXISTS `aow_add_dim_col`;

-- Indici per il filtro (idempotenti).
DROP PROCEDURE IF EXISTS `aow_add_dim_idx`;
DELIMITER $$
CREATE PROCEDURE `aow_add_dim_idx`(IN p_table VARCHAR(64), IN p_idx VARCHAR(64), IN p_col VARCHAR(64))
BEGIN
  IF NOT EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_idx
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_idx, '` (`', p_col, '`)');
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;
CALL `aow_add_dim_idx`('02_free_ads', 'idx_02_length', 'length_cm');
CALL `aow_add_dim_idx`('03_ads',      'idx_03_length', 'length_cm');
DROP PROCEDURE IF EXISTS `aow_add_dim_idx`;

-- ---- VERIFICA (facoltativa) ---------------------------------
--   SHOW COLUMNS FROM `02_free_ads` LIKE '%_cm';
--   SHOW COLUMNS FROM `03_ads` LIKE 'axles_n';

-- Fine patch.
