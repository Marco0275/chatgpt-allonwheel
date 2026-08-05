-- ==============================================================================
-- AllOnWheel — CORE LEAD-CENTRIC (schema riconciliato con il DB reale)
-- Da: proposta Gemini (gemini-code-...sql) — CORRETTA e allineata.
-- Target REALE: MySQL 5.7 | InnoDB | utf8mb4_unicode_ci
-- Regole: NIENTE DROP (dir.19) · CREATE IF NOT EXISTS · tipi = INT UNSIGNED
--         (non BIGINT) · tassonomia reale (macro/vehicle_type slug, no category_id/brand_id)
--         · ALTER su quote_requests "run-once" (5.7 non ha ADD COLUMN IF NOT EXISTS).
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1) wanted_ads — richieste inverse "Cerco mezzo" (tassonomia reale)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wanted_ads` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_user`       INT UNSIGNED NOT NULL COMMENT 'acquirente che cerca',
  `title`         VARCHAR(255) NOT NULL,
  `macro`         VARCHAR(50)  DEFAULT NULL COMMENT 'product_macros.slug',
  `vehicle_type`  VARCHAR(50)  DEFAULT NULL COMMENT 'vehicle_types.slug',
  `budget`        DECIMAL(12,2) DEFAULT NULL,
  `country_code`  CHAR(2)      DEFAULT NULL COMMENT 'ISO-2 (IT, DE, FR...)',
  `description`   TEXT NOT NULL,
  `status`        ENUM('active','matched','closed') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_match`       (`macro`,`vehicle_type`,`status`),
  INDEX `idx_wanted_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2) ads_documents — documenti tecnici (planimetrie/certificati/schede)
--    NB: gli annunci stanno in DUE tabelle (02_free_ads, 03_ads) -> niente FK
--    cross-table (MySQL non la consente); si usa il discriminatore `ad_table`
--    + integrita' a livello applicativo. File su disco con nome HASH.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ads_documents` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_ads`        INT UNSIGNED NOT NULL,
  `ad_table`      ENUM('02_free_ads','03_ads') NOT NULL DEFAULT '03_ads',
  `document_type` ENUM('technical_sheet','floorplan','certificate','manual','other') NOT NULL DEFAULT 'other',
  `file_name`     VARCHAR(255) NOT NULL COMMENT 'nome HASH su disco: upload_image/ads_documents/',
  `original_name` VARCHAR(255) NOT NULL COMMENT 'nome mostrato al download',
  `mime`          VARCHAR(100) DEFAULT NULL,
  `size_bytes`    INT UNSIGNED DEFAULT NULL,
  `uploaded_by`   INT UNSIGNED DEFAULT NULL,
  `uploaded_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_docs_ad` (`ad_table`,`id_ads`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3) ads_document_downloads — log tracciamento per il proxy download_doc.php
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ads_document_downloads` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_document`   INT UNSIGNED NOT NULL,
  `id_user`       INT UNSIGNED DEFAULT NULL COMMENT 'NULL se ospite',
  `ip_hash`       CHAR(64) DEFAULT NULL COMMENT 'hash IP (GDPR)',
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dl_doc` (`id_document`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4) seller_statistics — metriche lead-centric per annuncio (discriminatore)
--    Corretto vs Gemini: id_ads NON e' chiave esterna verso se stessa; UNIQUE
--    sulla coppia (ad_table,id_ads).
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seller_statistics` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_ads`          INT UNSIGNED NOT NULL,
  `ad_table`        ENUM('02_free_ads','03_ads') NOT NULL DEFAULT '03_ads',
  `views`           INT UNSIGNED NOT NULL DEFAULT 0,
  `unique_views`    INT UNSIGNED NOT NULL DEFAULT 0,
  `rfq_received`    INT UNSIGNED NOT NULL DEFAULT 0,
  `pdf_downloads`   INT UNSIGNED NOT NULL DEFAULT 0,
  `phone_clicks`    INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_stats_ad` (`ad_table`,`id_ads`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5) seo_taxonomy_cache — landing aggregate (P5, bassa priorita'); tassonomia reale
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seo_taxonomy_cache` (
  `slug`         VARCHAR(255) PRIMARY KEY COMMENT 'es: race-trailer-mercedes (composto)',
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
-- 6) RFQ: NON creare `rfq_requests` (duplicherebbe `quote_requests` gia' in uso).
--    Si ESTENDE `quote_requests`. ESEGUIRE UNA SOLA VOLTA (5.7: no IF NOT EXISTS).
--    Se una colonna esiste gia', salta la riga corrispondente.
-- ------------------------------------------------------------------------------
ALTER TABLE `quote_requests`
  ADD COLUMN `id_ads`       INT UNSIGNED NULL COMMENT 'annuncio sorgente; NULL se Wanted/generico',
  ADD COLUMN `ad_table`     ENUM('02_free_ads','03_ads') NULL,
  ADD COLUMN `id_buyer`     INT UNSIGNED NULL COMMENT 'utente loggato se presente',
  ADD COLUMN `company_name` VARCHAR(255) NULL,
  ADD COLUMN `contact_name` VARCHAR(255) NULL,
  ADD COLUMN `phone`        VARCHAR(100) NULL,
  ADD COLUMN `country_code` CHAR(2) NULL;

-- ==============================================================================
-- NOTE DI MATCHING (corrette vs Gemini: tabelle/stati reali)
-- All'INSERT di un annuncio (02_free_ads/03_ads, status 'approved') trova i buyer:
--   SELECT id_user FROM wanted_ads
--    WHERE status='active'
--      AND (macro IS NULL OR macro = :new_macro)
--      AND (vehicle_type IS NULL OR vehicle_type = :new_vtype);
-- All'INSERT di una wanted_ads trova i veicoli compatibili (2 tabelle):
--   SELECT id_ads FROM 03_ads WHERE status='approved'
--      AND (product_macro = :w_macro OR :w_macro IS NULL);
--   UNION
--   SELECT id_ads FROM 02_free_ads WHERE status='approved'
--      AND (product_macro = :w_macro OR :w_macro IS NULL);   -- free INCLUSI nel match
-- ==============================================================================
