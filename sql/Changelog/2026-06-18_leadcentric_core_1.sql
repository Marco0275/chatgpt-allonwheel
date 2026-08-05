-- ==============================================================================
-- AllOnWheel — CORE LEAD-CENTRIC (versione IDEMPOTENTE, ri-eseguibile)
-- Target REALE: MySQL 5.7 | InnoDB | utf8mb4_unicode_ci
--
-- SICURA SUL DB ATTUALE (gia' migrato):
--   * Le 5 CREATE sono `IF NOT EXISTS` -> no-op se le tabelle esistono.
--   * Le 7 colonne di `quote_requests` si aggiungono SOLO se mancanti, tramite
--     procedura temporanea (5.7 non ha ADD COLUMN IF NOT EXISTS) -> nessun errore
--     "Duplicate column" rieseguendo la patch.
-- Richiede un client che supporti le routine/DELIMITER (phpMyAdmin, mysql CLI).
-- Niente DROP di dati (dir.19). Tipi = INT UNSIGNED (allineati al reale).
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1) wanted_ads — richieste inverse "Cerco mezzo" (tassonomia reale)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wanted_ads` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_user`      INT UNSIGNED NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `macro`        VARCHAR(50)  DEFAULT NULL,
  `vehicle_type` VARCHAR(50)  DEFAULT NULL,
  `budget`       DECIMAL(12,2) DEFAULT NULL,
  `country_code` CHAR(2)      DEFAULT NULL,
  `description`  TEXT NOT NULL,
  `status`       ENUM('active','matched','closed') NOT NULL DEFAULT 'active',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_match`       (`macro`,`vehicle_type`,`status`),
  INDEX `idx_wanted_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2) ads_documents — documenti tecnici (discriminatore ad_table, niente FK cross-table)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ads_documents` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_ads`        INT UNSIGNED NOT NULL,
  `ad_table`      ENUM('02_free_ads','03_ads') NOT NULL DEFAULT '03_ads',
  `document_type` ENUM('technical_sheet','floorplan','certificate','manual','other') NOT NULL DEFAULT 'other',
  `file_name`     VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime`          VARCHAR(100) DEFAULT NULL,
  `size_bytes`    INT UNSIGNED DEFAULT NULL,
  `uploaded_by`   INT UNSIGNED DEFAULT NULL,
  `uploaded_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_docs_ad` (`ad_table`,`id_ads`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3) ads_document_downloads — log download per il proxy download_doc.php
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ads_document_downloads` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_document`   INT UNSIGNED NOT NULL,
  `id_user`       INT UNSIGNED DEFAULT NULL,
  `ip_hash`       CHAR(64) DEFAULT NULL,
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dl_doc` (`id_document`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4) seller_statistics — metriche per annuncio (UNIQUE per l'upsert dei contatori)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seller_statistics` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_ads`        INT UNSIGNED NOT NULL,
  `ad_table`      ENUM('02_free_ads','03_ads') NOT NULL DEFAULT '03_ads',
  `views`         INT UNSIGNED NOT NULL DEFAULT 0,
  `unique_views`  INT UNSIGNED NOT NULL DEFAULT 0,
  `rfq_received`  INT UNSIGNED NOT NULL DEFAULT 0,
  `pdf_downloads` INT UNSIGNED NOT NULL DEFAULT 0,
  `phone_clicks`  INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_stats_ad` (`ad_table`,`id_ads`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Nota: sul DB attuale esiste anche la colonna `whatsapp_clicks` (da una patch
-- precedente). E' inutilizzata e innocua; non viene rimossa (nessun DROP).

-- ------------------------------------------------------------------------------
-- 5) seo_taxonomy_cache — landing aggregate (P5)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seo_taxonomy_cache` (
  `slug`         VARCHAR(255) PRIMARY KEY,
  `lang`         CHAR(2) NOT NULL DEFAULT 'en',
  `macro`        VARCHAR(50) DEFAULT NULL,
  `vehicle_type` VARCHAR(50) DEFAULT NULL,
  `total_ads`    INT UNSIGNED NOT NULL DEFAULT 0,
  `total_wanted` INT UNSIGNED NOT NULL DEFAULT 0,
  `min_price`    DECIMAL(12,2) DEFAULT NULL,
  `max_price`    DECIMAL(12,2) DEFAULT NULL,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_seo_lookup` (`lang`,`macro`,`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6) quote_requests — aggiunge le colonne RFQ SOLO se mancanti (idempotente).
--    Procedura temporanea: controlla information_schema prima di ogni ADD COLUMN.
-- ------------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `aow_add_col`;
DELIMITER $$
CREATE PROCEDURE `aow_add_col`(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl VARCHAR(512))
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) THEN
    SET @aow_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
    PREPARE aow_st FROM @aow_sql;
    EXECUTE aow_st;
    DEALLOCATE PREPARE aow_st;
  END IF;
END$$
DELIMITER ;

CALL `aow_add_col`('quote_requests','id_ads',       '`id_ads` INT UNSIGNED NULL');
CALL `aow_add_col`('quote_requests','ad_table',     '`ad_table` ENUM(''02_free_ads'',''03_ads'') NULL');
CALL `aow_add_col`('quote_requests','id_buyer',     '`id_buyer` INT UNSIGNED NULL');
CALL `aow_add_col`('quote_requests','company_name', '`company_name` VARCHAR(255) NULL');
CALL `aow_add_col`('quote_requests','contact_name', '`contact_name` VARCHAR(255) NULL');
CALL `aow_add_col`('quote_requests','phone',        '`phone` VARCHAR(100) NULL');
CALL `aow_add_col`('quote_requests','country_code', '`country_code` CHAR(2) NULL');

DROP PROCEDURE IF EXISTS `aow_add_col`;

-- ==============================================================================
-- NOTE DI MATCHING (tabelle/stati reali; free INCLUSI)
-- All'INSERT di un annuncio (02_free_ads/03_ads, status 'approved') -> buyer:
--   SELECT id_user FROM wanted_ads
--    WHERE status='active'
--      AND (macro IS NULL OR macro = :new_macro)
--      AND (vehicle_type IS NULL OR vehicle_type = :new_vtype);
-- All'INSERT di una wanted_ads -> veicoli compatibili (2 tabelle):
--   SELECT id_ads FROM 03_ads WHERE status='approved'
--      AND (product_macro = :w_macro OR :w_macro IS NULL)
--   UNION
--   SELECT id_ads FROM 02_free_ads WHERE status='approved'
--      AND (product_macro = :w_macro OR :w_macro IS NULL);   -- free INCLUSI
-- ==============================================================================
