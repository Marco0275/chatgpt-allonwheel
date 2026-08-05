-- ============================================================
-- 06_company_products_special.sql
-- Tabella companion di 06_company_products per le categorie SPECIALI
-- (ramo "Special" del flowchart). Mirror della tabella regolare,
-- con product_key = chiave di CompanyManager::$products_special.
--
-- Categorie speciali disponibili (catalogo in CompanyManager::$products_special):
--   racing_trailer        -> Racing trailer
--   box_trailer           -> Box trailer
--   motorhomes_mobilhomes -> Motorhomes & Mobilhomes
--   hospitality_units     -> Hospitality units
--   paddock_trailers      -> Paddock trailers
--   special_shelter       -> Special Shelter
--
-- NOTA: e' una tabella di ASSOCIAZIONE (azienda <-> categoria speciale):
-- le righe vengono create quando un'azienda seleziona le categorie in
-- fase di registrazione/modifica. Non contiene righe "catalogo" slegate
-- da un'azienda (il catalogo dei 6 valori vive nella classe PHP).
-- ============================================================

CREATE TABLE IF NOT EXISTS `06_company_products_special` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `product_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave da CompanyManager::$products_special',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_special_company` (`company_id`),
  KEY `idx_special_key` (`product_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorie speciali dichiarate da ciascuna azienda';
