-- ============================================================
-- 2026-07-26_rental_core.sql  -- Feature NOLEGGIO (07_rent), parte 2.
-- Tabelle: annunci noleggio (ricalcano 02_free_ads), richieste noleggio
-- (ricalcano wanted_ads) e destinatari (pattern RFQ recipients).
-- Idempotente: CREATE TABLE IF NOT EXISTS. MySQL 5.7. Non distruttiva.
-- ============================================================

CREATE TABLE IF NOT EXISTS `07_rent_ads` (
  `id_ads` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Title',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Tariffa di noleggio (per giorno)',
  `type` enum('New on sell','Used on sell','For rent','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'For rent',
  `conditions` enum('New','As good as new','Used','Poor','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'As good as new',
  `racing` tinyint(1) NOT NULL DEFAULT '0',
  `promotion` tinyint(1) NOT NULL DEFAULT '0',
  `horse` tinyint(1) NOT NULL DEFAULT '0',
  `hospitality` tinyint(1) NOT NULL DEFAULT '0',
  `medical` tinyint(1) NOT NULL DEFAULT '0',
  `military` tinyint(1) NOT NULL DEFAULT '0',
  `motorhome` tinyint(1) NOT NULL DEFAULT '0',
  `technology` tinyint(1) NOT NULL DEFAULT '0',
  `street_food` tinyint(1) NOT NULL DEFAULT '0',
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `item_kind` enum('vehicle','shelter_container') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vehicle',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Noleggio: sempre special',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'special_types.slug',
  `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_ads`),
  KEY `idx_rentads_match` (`status`,`vehicle_type`),
  KEY `idx_rentads_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `07_rent_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rental request',
  `vehicle_types` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Slug special_types selezionati (CSV)',
  `budget` decimal(10,2) DEFAULT NULL COMMENT 'Budget max per giorno (opzionale)',
  `country_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rent_from` date DEFAULT NULL,
  `rent_to` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','distributed','quoted','won','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rentreq_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `07_rent_request_recipients` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Proprietario annuncio noleggio (destinatario)',
  `company_id` int(11) DEFAULT NULL,
  `tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
  `rank_pos` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `emailed_at` datetime DEFAULT NULL COMMENT 'NULL = non notificato via email (solo in area lead)',
  `claimed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_req_user` (`request_id`,`id_user`),
  KEY `idx_recip_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
