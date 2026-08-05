-- ============================================================
-- 2026-07-20_dimensions.sql
-- Misure dimensionali NUMERICHE sugli annunci (base per le faccette)
--
-- CONTESTO / PERCHE' ORA E' SICURO
-- Le uniche misure esistenti (ext_length/width/height/axles) stavano in
-- 03_ads_tech_details come VARCHAR (stringhe libere, non filtrabili) e SOLO
-- per il premium. Nel DB reale quella tabella e' VUOTA: non c'e' nessun dato
-- storico da migrare, quindi possiamo introdurre misure numeriche PULITE dal
-- primo giorno, senza il rischio di corrompere dati esistenti (dir. 9).
--
-- COSA AGGIUNGE
-- 4 colonne numeriche su ENTRAMBE le tabelle annunci (02_free_ads, 03_ads),
-- cosi' free e premium sono filtrabili allo stesso modo (dir. 13: la
-- differenza free/premium resta solo altrove, non sulle misure di base):
--   length_cm   lunghezza in centimetri  (INT, evita virgole/unita ambigue)
--   width_cm    larghezza in centimetri
--   height_cm   altezza in centimetri
--   axles_n     numero di assi           (TINYINT)
-- Unita' FISSA (cm / numero): niente "12,5 m" vs "12500mm". La UI mostra
-- l'unita' nell'etichetta, l'utente scrive solo il numero.
-- NULL = non specificato (dir. 14: nessun valore inventato).
--
-- GARANZIE
--  - NON distruttiva: aggiunge colonne, non tocca dati ne' ext_length &c.
--    (che restano dove sono, come dettaglio testuale del premium).
--  - Idempotente (procedura information_schema, 5.7).
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

-- --- 02_free_ads ---
CALL `aow_add_dim_col`('02_free_ads', 'length_cm', '`length_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Lunghezza in cm (NULL = non specificata)''');
CALL `aow_add_dim_col`('02_free_ads', 'width_cm',  '`width_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Larghezza in cm''');
CALL `aow_add_dim_col`('02_free_ads', 'height_cm', '`height_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Altezza in cm''');
CALL `aow_add_dim_col`('02_free_ads', 'axles_n',   '`axles_n` TINYINT UNSIGNED DEFAULT NULL COMMENT ''Numero di assi''');

-- --- 03_ads ---
CALL `aow_add_dim_col`('03_ads', 'length_cm', '`length_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Lunghezza in cm (NULL = non specificata)''');
CALL `aow_add_dim_col`('03_ads', 'width_cm',  '`width_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Larghezza in cm''');
CALL `aow_add_dim_col`('03_ads', 'height_cm', '`height_cm` SMALLINT UNSIGNED DEFAULT NULL COMMENT ''Altezza in cm''');
CALL `aow_add_dim_col`('03_ads', 'axles_n',   '`axles_n` TINYINT UNSIGNED DEFAULT NULL COMMENT ''Numero di assi''');

DROP PROCEDURE IF EXISTS `aow_add_dim_col`;

-- Indici per la faccetta (filtro per range su length/height sono i piu' usati).
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
