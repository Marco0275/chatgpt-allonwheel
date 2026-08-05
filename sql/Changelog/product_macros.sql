-- ============================================================
-- product_macros.sql — Overlay macro-categorie MOTORSPORT (Fase 1).
-- Keystone della ristrutturazione: introduce le 5 macro di brand
-- (Race Trailer, Hospitality, Mobile Clinic, Shelter, Custom Projects)
-- SOPRA la tassonomia esistente (vehicle_types / flag annunci), senza
-- distruggere nulla (dir. 9).
--
-- Contenuto:
--   1) Tabella `product_macros` (catalogo 5 macro, gestibile da admin
--      come vehicle_types). slug = chiave di ProductMacro::* (PHP).
--   2) Colonna `product_macro` su 02_free_ads e 03_ads (overlay).
--   3) Backfill NON distruttivo dai dati esistenti (flag + vehicle_type).
--
-- Target DB: MySQL 5.7 (no ADD COLUMN IF NOT EXISTS).
-- Idempotente sui dati: il seed usa INSERT IGNORE (UNIQUE slug) e il
-- backfill agisce solo dove product_macro IS NULL.
-- Le due ALTER vanno eseguite UNA SOLA VOLTA (5.7 non supporta
-- ADD COLUMN IF NOT EXISTS): se gia' applicate, saltarle.
-- ============================================================

-- ------------------------------------------------------------
-- 1) Catalogo delle 5 macro di brand
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_macros` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Label EN in UI (catalogo B2B)',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave macro (ProductMacro::*), usata in 02/03_ads.product_macro',
  `sort_order` smallint(5) NOT NULL DEFAULT 0,
  `intro_text` text COLLATE utf8mb4_unicode_ci COMMENT 'Testo introduttivo landing macro (Fase 1)',
  `hero_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Immagine hero landing macro',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_macro_slug` (`slug`),
  KEY `idx_macro_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Macro-categorie motorsport di brand (overlay Fase 1)';

-- Seed delle 5 macro (idempotente: UNIQUE slug + INSERT IGNORE)
INSERT IGNORE INTO `product_macros` (`name`, `slug`, `sort_order`) VALUES
  ('Race Trailer',        'race-trailer',      10),
  ('Hospitality',         'hospitality',       20),
  ('Mobile Clinic',       'mobile-clinic',     30),
  ('Shelter & Container', 'shelter-container', 40),
  ('Custom Projects',     'custom-projects',   50);

-- ------------------------------------------------------------
-- 2) Colonna overlay sugli annunci (free + premium)
--    NB: soft-reference verso product_macros.slug (nessuna FK,
--    coerente con vehicle_type). Eseguire UNA SOLA VOLTA.
-- ------------------------------------------------------------
ALTER TABLE `02_free_ads`
  ADD COLUMN `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
    COMMENT 'Overlay macro motorsport (product_macros.slug); NULL = nessuna macro di brand'
    AFTER `vehicle_type`,
  ADD KEY `idx_02_product_macro` (`product_macro`);

ALTER TABLE `03_ads`
  ADD COLUMN `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
    COMMENT 'Overlay macro motorsport (product_macros.slug); NULL = nessuna macro di brand'
    AFTER `vehicle_type`,
  ADD KEY `idx_03_product_macro` (`product_macro`);

-- ------------------------------------------------------------
-- 3) Backfill non distruttivo (stessa priorita' di ProductMacro::forAd)
--    Agisce solo dove product_macro IS NULL -> ri-eseguibile in sicurezza.
--    Priorita': shelter > racing > hospitality > medical > project.
--    Motorhome/Street food NON mappano a macro (restano facet marketplace).
-- ------------------------------------------------------------
UPDATE `02_free_ads` SET `product_macro` = CASE
    WHEN `item_kind` = 'shelter_container' THEN 'shelter-container'
    WHEN `racing` = 1 THEN 'race-trailer'
    WHEN `hospitality` = 1 THEN 'hospitality'
    WHEN `medical` = 1
         OR `vehicle_type` IN ('laboratori_medici_mobili', 'ambulanze', 'disabili')
      THEN 'mobile-clinic'
    WHEN `type` = 'Project' OR `conditions` = 'Project' THEN 'custom-projects'
    ELSE `product_macro`
  END
  WHERE `product_macro` IS NULL;

UPDATE `03_ads` SET `product_macro` = CASE
    WHEN `item_kind` = 'shelter_container' THEN 'shelter-container'
    WHEN `racing` = 1 THEN 'race-trailer'
    WHEN `hospitality` = 1 THEN 'hospitality'
    WHEN `medical` = 1
         OR `vehicle_type` IN ('laboratori_medici_mobili', 'ambulanze', 'disabili')
      THEN 'mobile-clinic'
    WHEN `type` = 'Project' OR `conditions` = 'Project' THEN 'custom-projects'
    ELSE `product_macro`
  END
  WHERE `product_macro` IS NULL;
