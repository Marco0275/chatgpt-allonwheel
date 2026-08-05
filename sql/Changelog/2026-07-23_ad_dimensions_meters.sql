-- ============================================================
-- 2026-07-23_ad_dimensions_meters.sql
-- Misure annunci: da CENTIMETRI (length_cm...) a METRI (length_mt...)
--
-- CONTESTO
-- Nei file PHP le misure sono state portate a METRI e i campi rinominati in
-- length_mt / width_mt / height_mt. Questa patch allinea il DATABASE.
--
-- NON e' una semplice rinomina: le colonne attuali contengono CENTIMETRI
-- (nel dump c'e' gia' almeno una riga con 1000, 1000, 1000 = 10 m). Se ci
-- limitassimo a rinominare, quel 1000 verrebbe letto come "1000 metri".
-- Quindi: nuove colonne DECIMAL, dati CONVERTITI (/100), poi rimozione delle
-- vecchie solo quando la conversione e' certificata completa.
--
-- SCOPE (verificato sul dump allonwhe80316.sql):
--   02_free_ads : length_cm, width_cm, height_cm  smallint(5) UNSIGNED
--   03_ads      : idem
--   indici      : idx_02_length e idx_03_length, entrambi su length_cm
--   axles_n     : NON si tocca (e' un CONTEGGIO di assi, non una misura)
--   wanted_ads  : nessuna colonna misura (verificato)
--
-- TIPO SCELTO: decimal(6,2)
--   Esatto (niente errori di arrotondamento del float), tiene 12.5 -> 12.50,
--   massimo 9999.99 m. Stesso stile di wanted_ads.budget decimal(12,2).
--
-- GARANZIE
--   - Idempotente: rieseguibile senza errori (procedure su information_schema;
--     MySQL 5.7 non ha ADD/DROP COLUMN IF EXISTS).
--   - Non distruttiva finche' i dati non sono al sicuro: le vecchie colonne
--     vengono rimosse SOLO se ogni valore e' stato copiato (dir. 9).
--   - MySQL 5.7 / MariaDB compatibile.
-- ============================================================

-- ------------------------------------------------------------
-- 1) Procedure di supporto (rimosse in fondo al file)
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
DROP PROCEDURE IF EXISTS `aow_drop_col_if_exists`;
DROP PROCEDURE IF EXISTS `aow_drop_idx_if_exists`;
DROP PROCEDURE IF EXISTS `aow_add_idx_if_missing`;
DROP PROCEDURE IF EXISTS `aow_copy_cm_to_mt`;
DROP PROCEDURE IF EXISTS `aow_drop_cm_if_migrated`;

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

CREATE PROCEDURE `aow_drop_col_if_exists`(
  IN p_table VARCHAR(64), IN p_column VARCHAR(64)
)
BEGIN
  IF EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_column, '`');
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `aow_drop_idx_if_exists`(
  IN p_table VARCHAR(64), IN p_idx VARCHAR(64)
)
BEGIN
  IF EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_idx
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` DROP INDEX `', p_idx, '`');
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE `aow_add_idx_if_missing`(
  IN p_table VARCHAR(64), IN p_idx VARCHAR(64), IN p_col VARCHAR(64)
)
BEGIN
  IF EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) AND NOT EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_idx
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_idx, '` (`', p_col, '`)');
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- Copia CENTIMETRI -> METRI (divisione per 100). Agisce solo dove la nuova
-- colonna e' ancora vuota e la vecchia ha un valore: rieseguibile senza
-- sovrascrivere quanto gia' inserito in metri dall'applicazione.
CREATE PROCEDURE `aow_copy_cm_to_mt`(
  IN p_table VARCHAR(64), IN p_old VARCHAR(64), IN p_new VARCHAR(64)
)
BEGIN
  IF EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_old
  ) AND EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_new
  ) THEN
      SET @ddl = CONCAT('UPDATE `', p_table, '` SET `', p_new, '` = `', p_old,
                        '` / 100 WHERE `', p_new, '` IS NULL AND `', p_old, '` IS NOT NULL');
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- Rimuove la vecchia colonna in cm SOLO se ogni suo valore e' finito nella
-- nuova: se restasse anche una sola riga non migrata, la colonna resta al suo
-- posto e non si perde nulla (dir. 9).
CREATE PROCEDURE `aow_drop_cm_if_migrated`(
  IN p_table VARCHAR(64), IN p_old VARCHAR(64), IN p_new VARCHAR(64)
)
BEGIN
  DECLARE v_pending INT DEFAULT 0;
  IF EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_old
  ) AND EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_new
  ) THEN
      SET @q = CONCAT('SELECT COUNT(*) INTO @aow_pending FROM `', p_table,
                      '` WHERE `', p_old, '` IS NOT NULL AND `', p_new, '` IS NULL');
      PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
      SET v_pending = IFNULL(@aow_pending, 0);
      IF v_pending = 0 THEN
          SET @ddl = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_old, '`');
          PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
      END IF;
  END IF;
END$$

DELIMITER ;

-- ------------------------------------------------------------
-- 2) Nuove colonne in METRI
--    decimal(6,2): esatto, fino a 9999.99 m, NULL = misura non indicata.
-- ------------------------------------------------------------
CALL `aow_add_col_if_missing`('02_free_ads', 'length_mt',
  '`length_mt` decimal(6,2) DEFAULT NULL COMMENT ''Lunghezza in metri (NULL = non specificata)''');
CALL `aow_add_col_if_missing`('02_free_ads', 'width_mt',
  '`width_mt` decimal(6,2) DEFAULT NULL COMMENT ''Larghezza in metri''');
CALL `aow_add_col_if_missing`('02_free_ads', 'height_mt',
  '`height_mt` decimal(6,2) DEFAULT NULL COMMENT ''Altezza in metri''');

CALL `aow_add_col_if_missing`('03_ads', 'length_mt',
  '`length_mt` decimal(6,2) DEFAULT NULL COMMENT ''Lunghezza in metri (NULL = non specificata)''');
CALL `aow_add_col_if_missing`('03_ads', 'width_mt',
  '`width_mt` decimal(6,2) DEFAULT NULL COMMENT ''Larghezza in metri''');
CALL `aow_add_col_if_missing`('03_ads', 'height_mt',
  '`height_mt` decimal(6,2) DEFAULT NULL COMMENT ''Altezza in metri''');

-- ------------------------------------------------------------
-- 3) Conversione dei dati esistenti: centimetri / 100 = metri
--    (1000 cm -> 10.00 m). Non sovrascrive valori gia' in metri.
-- ------------------------------------------------------------
CALL `aow_copy_cm_to_mt`('02_free_ads', 'length_cm', 'length_mt');
CALL `aow_copy_cm_to_mt`('02_free_ads', 'width_cm',  'width_mt');
CALL `aow_copy_cm_to_mt`('02_free_ads', 'height_cm', 'height_mt');

CALL `aow_copy_cm_to_mt`('03_ads', 'length_cm', 'length_mt');
CALL `aow_copy_cm_to_mt`('03_ads', 'width_cm',  'width_mt');
CALL `aow_copy_cm_to_mt`('03_ads', 'height_cm', 'height_mt');

-- ------------------------------------------------------------
-- 4) Indici: spostati da length_cm a length_mt, stesso nome
--    (li usa il filtro dimensionale della sidebar).
-- ------------------------------------------------------------
CALL `aow_drop_idx_if_exists`('02_free_ads', 'idx_02_length');
CALL `aow_add_idx_if_missing`('02_free_ads', 'idx_02_length', 'length_mt');

CALL `aow_drop_idx_if_exists`('03_ads', 'idx_03_length');
CALL `aow_add_idx_if_missing`('03_ads', 'idx_03_length', 'length_mt');

-- ------------------------------------------------------------
-- 5) Rimozione delle vecchie colonne in cm
--    Avviene SOLO se la conversione e' completa: se anche una sola riga
--    avesse un valore in cm non copiato, la colonna resta e non si perde
--    nulla. In quel caso: rilanciare la patch dopo aver sistemato i dati.
-- ------------------------------------------------------------
CALL `aow_drop_cm_if_migrated`('02_free_ads', 'length_cm', 'length_mt');
CALL `aow_drop_cm_if_migrated`('02_free_ads', 'width_cm',  'width_mt');
CALL `aow_drop_cm_if_migrated`('02_free_ads', 'height_cm', 'height_mt');

CALL `aow_drop_cm_if_migrated`('03_ads', 'length_cm', 'length_mt');
CALL `aow_drop_cm_if_migrated`('03_ads', 'width_cm',  'width_mt');
CALL `aow_drop_cm_if_migrated`('03_ads', 'height_cm', 'height_mt');

-- ------------------------------------------------------------
-- 6) Pulizia delle procedure di supporto
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
DROP PROCEDURE IF EXISTS `aow_drop_col_if_exists`;
DROP PROCEDURE IF EXISTS `aow_drop_idx_if_exists`;
DROP PROCEDURE IF EXISTS `aow_add_idx_if_missing`;
DROP PROCEDURE IF EXISTS `aow_copy_cm_to_mt`;
DROP PROCEDURE IF EXISTS `aow_drop_cm_if_migrated`;

-- ---- VERIFICA (facoltativa, da lanciare dopo) ---------------
--   Colonne presenti (devono esserci solo le _mt + axles_n):
--     SHOW COLUMNS FROM `02_free_ads` LIKE '%_mt';
--     SHOW COLUMNS FROM `02_free_ads` LIKE '%_cm';   -- deve dare 0 righe
--     SHOW COLUMNS FROM `03_ads` LIKE '%_mt';
--     SHOW COLUMNS FROM `03_ads` LIKE '%_cm';        -- deve dare 0 righe
--   Dati convertiti (1000 cm deve essere diventato 10.00):
--     SELECT id_ads, length_mt, width_mt, height_mt, axles_n
--       FROM `02_free_ads` WHERE length_mt IS NOT NULL;
--   Indice sulla colonna giusta:
--     SHOW INDEX FROM `02_free_ads` WHERE Key_name = 'idx_02_length';

-- Fine patch.
