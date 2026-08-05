-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: m-10.th.seeweb.it
-- Creato il: Ago 05, 2026 alle 23:10
-- Versione del server: 5.7.42
-- Versione PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `allonwhe80316`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `02_free_ads`
--

CREATE TABLE `02_free_ads` (
  `id_ads` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Title',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` enum('New on sell','Used on sell','For rent','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New on sell',
  `conditions` enum('New','As good as new','Used','Poor','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `item_kind` enum('vehicle','shelter_container') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vehicle' COMMENT 'Step 1 wizard: veicolo o shelter/container',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Step 2 wizard: road/special (shelter => special)',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Step 3: vehicle_types.slug (o shelter_container)',
  `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Overlay macro motorsport (product_macros.slug); NULL = nessuna macro di brand'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `02_free_ads`
--

INSERT INTO `02_free_ads` (`id_ads`, `id_user`, `status`, `author`, `email`, `phone`, `title`, `subtitle`, `list_price`, `type`, `conditions`, `image_original`, `image_thumbnail`, `description`, `created_at`, `expires_at`, `item_kind`, `macro_category`, `vehicle_type`, `product_macro`) VALUES
(95, 41, 'approved', 'All_on_Wheel', 'info@allonwheel.com', '+39020000', 'Vehicle Special Box trailers', 'Vehicle Special Box trailers', 250000.00, 'New on sell', 'New', 'ad_95_0173cebba3447c91.jpg', 'ad_95_0173cebba3447c91.jpg', 'Vehicle Special Box trailers', '2026-08-02 15:06:23', '2026-09-16 17:06:23', 'vehicle', 'special', 'box_trailer', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `02_free_ads_gallery`
--

CREATE TABLE `02_free_ads_gallery` (
  `id_images` bigint(20) NOT NULL,
  `id_ads` int(10) UNSIGNED NOT NULL,
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `03_ads`
--

CREATE TABLE `03_ads` (
  `id_ads` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Title',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` enum('New on sell','Used on sell','For rent','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New on sell',
  `conditions` enum('New','As good as new','Used','Poor','Project') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `item_kind` enum('vehicle','shelter_container') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vehicle' COMMENT 'Step 1 wizard: veicolo o shelter/container',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Step 2 wizard: road/special (shelter => special)',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Step 3: vehicle_types.slug (o shelter_container)',
  `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Overlay macro motorsport (product_macros.slug); NULL = nessuna macro di brand',
  `axles_n` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Numero di assi',
  `length_mt` decimal(6,2) DEFAULT NULL COMMENT 'Lunghezza in metri (NULL = non specificata)',
  `width_mt` decimal(6,2) DEFAULT NULL COMMENT 'Larghezza in metri',
  `height_mt` decimal(6,2) DEFAULT NULL COMMENT 'Altezza in metri'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `03_ads_gallery`
--

CREATE TABLE `03_ads_gallery` (
  `id_images` bigint(20) NOT NULL,
  `id_ads` int(10) UNSIGNED NOT NULL,
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `03_ads_tech_details`
--

CREATE TABLE `03_ads_tech_details` (
  `id_tech` int(10) UNSIGNED NOT NULL,
  `id_ads` int(10) UNSIGNED NOT NULL,
  `cars` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `Awning` tinyint(1) NOT NULL DEFAULT '0',
  `Workshop` tinyint(1) NOT NULL DEFAULT '0',
  `Belly` tinyint(1) NOT NULL DEFAULT '0',
  `Kitchen` tinyint(1) NOT NULL DEFAULT '0',
  `Beds` tinyint(1) NOT NULL DEFAULT '0',
  `Genset` tinyint(1) NOT NULL DEFAULT '0',
  `Bathroom` tinyint(1) NOT NULL DEFAULT '0',
  `SAT` tinyint(1) NOT NULL DEFAULT '0',
  `Lift_manufactorer` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Lift_length` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Lift_width` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Lift_capacity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0 kg',
  `rails` tinyint(1) NOT NULL DEFAULT '0',
  `LED` tinyint(1) NOT NULL DEFAULT '0',
  `independent_entrance_cargo` tinyint(1) NOT NULL DEFAULT '0',
  `Fixing` tinyint(1) NOT NULL DEFAULT '0',
  `Cabinets` tinyint(1) NOT NULL DEFAULT '0',
  `Adjustable` tinyint(1) NOT NULL DEFAULT '0',
  `Workbenches` tinyint(1) NOT NULL DEFAULT '0',
  `HVAC` tinyint(1) NOT NULL DEFAULT '0',
  `Telemetry` tinyint(1) NOT NULL DEFAULT '0',
  `independent_entrance_office` tinyint(1) NOT NULL DEFAULT '0',
  `Electrical` tinyint(1) NOT NULL DEFAULT '0',
  `office_other` tinyint(1) NOT NULL DEFAULT '0',
  `Windows` tinyint(1) NOT NULL DEFAULT '0',
  `TV` tinyint(1) NOT NULL DEFAULT '0',
  `Main_panel` tinyint(1) NOT NULL DEFAULT '0',
  `batteries` tinyint(1) NOT NULL DEFAULT '0',
  `Charger` tinyint(1) NOT NULL DEFAULT '0',
  `Connection` tinyint(1) NOT NULL DEFAULT '0',
  `Switchgear` tinyint(1) NOT NULL DEFAULT '0',
  `electrical_other` tinyint(1) NOT NULL DEFAULT '0',
  `Sockets` tinyint(1) NOT NULL DEFAULT '0',
  `Rema` tinyint(1) NOT NULL DEFAULT '0',
  `Plywood` tinyint(1) NOT NULL DEFAULT '0',
  `painted` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Sandwich` tinyint(1) NOT NULL DEFAULT '0',
  `Stickers` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Special` tinyint(1) NOT NULL DEFAULT '0',
  `Stepdeck` tinyint(1) NOT NULL DEFAULT '0',
  `axles` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `Straightline` tinyint(1) NOT NULL DEFAULT '0',
  `MGW` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `chassis_special` tinyint(1) NOT NULL DEFAULT '0',
  `Saddle` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ext_length` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ext_width` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ext_height` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `06_company`
--

CREATE TABLE `06_company` (
  `id` int(11) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'FK → users.id_user',
  `ragione_sociale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Company name (form 06_10)',
  `partita_iva` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'VAT number',
  `codice_fiscale` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tax code (opzionale)',
  `indirizzo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Address',
  `cap` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Postal code',
  `citta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'City',
  `provincia` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Province (2-5 char)',
  `nazione` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Italia' COMMENT 'Country',
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cellulare` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Company e-mail',
  `pec` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sito_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descrizione` text COLLATE utf8mb4_unicode_ci COMMENT 'Free text description',
  `descrizione_it` text COLLATE utf8mb4_unicode_ci COMMENT 'Descrizione in italiano (i18n, fallback su descrizione)',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Logo filename in /uploads/06_company/',
  `referente_nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referente_cognome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referente_ruolo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referente_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attiva` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=visibile in directory, 0=nascosta',
  `founding_partner` tinyint(1) NOT NULL DEFAULT '0',
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_modifica` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `wants_pm_list` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=azienda vuole ricevere elenco PM/consulenti',
  `cert_iso9001` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 9001 cert file (upload_image/06_company/certs/)',
  `cert_iso14001` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 14001 cert file',
  `cert_iso45001` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 45001 cert file',
  `associazioni` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Associazioni/albi di appartenenza',
  `referenze` text COLLATE utf8mb4_unicode_ci COMMENT 'Referenze / clienti / progetti',
  `area_servita` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Area servita (locale/nazionale/internazionale o testo)',
  `offers_rental` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=azienda offre noleggio veicoli speciali',
  `general_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Nota unica del form registrazione azienda'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Anagrafica aziende fornitrici - modulo 06_company';

-- --------------------------------------------------------

--
-- Struttura della tabella `06_company_gallery`
--

CREATE TABLE `06_company_gallery` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'Proprietario, denormalizzato da 06_company.user_id',
  `immagine` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Filename in /uploads/06_company/',
  `didascalia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordine` int(11) NOT NULL DEFAULT '0',
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Immagini gallery di ciascuna azienda';

-- --------------------------------------------------------

--
-- Struttura della tabella `06_company_products`
--

CREATE TABLE `06_company_products` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave da CompanyManager::$products',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificazioni_prodotto` tinyint(1) NOT NULL DEFAULT '0',
  `campioni_gratuiti` tinyint(1) NOT NULL DEFAULT '0',
  `assistenza_posa` tinyint(1) NOT NULL DEFAULT '0',
  `progettazione_supporto` tinyint(1) NOT NULL DEFAULT '0',
  `schede_tecniche` tinyint(1) NOT NULL DEFAULT '0',
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Prodotti dichiarati da ciascuna azienda';

-- --------------------------------------------------------

--
-- Struttura della tabella `06_company_products_special`
--

CREATE TABLE `06_company_products_special` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave da CompanyManager::$products_special',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorie speciali dichiarate da ciascuna azienda';

--
-- Dump dei dati per la tabella `06_company_products_special`
--

INSERT INTO `06_company_products_special` (`id`, `company_id`, `product_key`, `note`, `data_inserimento`) VALUES
(7, 2, 'racing_trailer', '', '2026-06-02 21:34:33'),
(8, 2, 'box_trailer', '', '2026-06-02 21:34:33'),
(9, 2, 'motorhomes_mobilhomes', '', '2026-06-02 21:34:33'),
(10, 2, 'hospitality_units', '', '2026-06-02 21:34:33'),
(11, 2, 'paddock_trailers', '', '2026-06-02 21:34:33'),
(12, 2, 'shelter_container', '', '2026-06-02 21:34:33'),
(13, 3, 'racing_trailer', '', '2026-06-02 21:52:18'),
(14, 3, 'box_trailer', '', '2026-06-02 21:52:18'),
(15, 3, 'motorhomes_mobilhomes', '', '2026-06-02 21:52:18'),
(16, 3, 'hospitality_units', '', '2026-06-02 21:52:18'),
(17, 3, 'paddock_trailers', '', '2026-06-02 21:52:18'),
(18, 3, 'shelter_container', '', '2026-06-02 21:52:18'),
(19, 4, 'racing_trailer', '', '2026-06-05 18:58:22'),
(20, 4, 'box_trailer', '', '2026-06-05 18:58:22'),
(21, 4, 'motorhomes_mobilhomes', '', '2026-06-05 18:58:22'),
(22, 4, 'hospitality_units', '', '2026-06-05 18:58:22'),
(23, 4, 'paddock_trailers', '', '2026-06-05 18:58:22'),
(24, 4, 'shelter_container', '', '2026-06-05 18:58:22'),
(25, 5, 'racing_trailer', '', '2026-06-24 16:59:23'),
(26, 5, 'box_trailer', '', '2026-06-24 16:59:23'),
(27, 5, 'motorhomes_mobilhomes', '', '2026-06-24 16:59:23'),
(28, 5, 'hospitality_units', '', '2026-06-24 16:59:23'),
(29, 5, 'paddock_trailers', '', '2026-06-24 16:59:23'),
(30, 5, 'shelter_container', '', '2026-06-24 16:59:23'),
(37, 7, 'racing_trailer', '', '2026-07-03 14:49:15'),
(38, 7, 'box_trailer', '', '2026-07-03 14:49:15'),
(39, 7, 'motorhomes_mobilhomes', '', '2026-07-03 14:49:15'),
(40, 7, 'hospitality_units', '', '2026-07-03 14:49:15'),
(41, 7, 'paddock_trailers', '', '2026-07-03 14:49:15'),
(42, 7, 'shelter_container', '', '2026-07-03 14:49:15'),
(43, 6, 'racing_trailer', '', '2026-07-17 12:42:07'),
(44, 6, 'box_trailer', '', '2026-07-17 12:42:07'),
(45, 6, 'motorhomes_mobilhomes', '', '2026-07-17 12:42:07'),
(46, 6, 'hospitality_units', '', '2026-07-17 12:42:07'),
(47, 6, 'paddock_trailers', '', '2026-07-17 12:42:07'),
(48, 6, 'shelter_container', '', '2026-07-17 12:42:07'),
(54, 8, 'racing_trailer', '', '2026-07-25 09:11:37'),
(55, 8, 'box_trailer', '', '2026-07-25 09:11:37'),
(56, 8, 'hospitality_units', '', '2026-07-25 09:11:37'),
(57, 8, 'paddock_trailers', '', '2026-07-25 09:11:37'),
(58, 8, 'shelter_container', '', '2026-07-25 09:11:37'),
(59, 9, 'box_trailer', '', '2026-07-29 18:31:47'),
(60, 9, 'hospitality_units', '', '2026-07-29 18:31:47'),
(61, 9, 'paddock_trailers', '', '2026-07-29 18:31:47'),
(62, 9, 'racing_trailer', '', '2026-07-29 18:31:47'),
(63, 9, 'camper', '', '2026-07-29 18:31:47'),
(64, 9, 'laboratori_medici_mobili', '', '2026-07-29 18:31:47'),
(65, 9, 'uffici_mobili', '', '2026-07-29 18:31:47'),
(66, 9, 'on_demand', '', '2026-07-29 18:31:47');

-- --------------------------------------------------------

--
-- Struttura della tabella `06_company_services`
--

CREATE TABLE `06_company_services` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `service_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave da CompanyManager::$services',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inserimento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Servizi accessori dichiarati da ciascuna azienda';

-- --------------------------------------------------------

--
-- Struttura della tabella `07_rent_ads`
--

CREATE TABLE `07_rent_ads` (
  `id_ads` int(10) UNSIGNED NOT NULL,
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
  `image_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_image.jpg',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `item_kind` enum('vehicle','shelter_container') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vehicle',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Noleggio: sempre special',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'special_types.slug',
  `product_macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `07_rent_requests`
--

CREATE TABLE `07_rent_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rental request',
  `vehicle_types` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Slug special_types selezionati (CSV)',
  `budget` decimal(10,2) DEFAULT NULL COMMENT 'Budget max per giorno (opzionale)',
  `country_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rent_from` date DEFAULT NULL,
  `rent_to` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','distributed','quoted','won','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `07_rent_requests`
--

INSERT INTO `07_rent_requests` (`id`, `id_user`, `title`, `vehicle_types`, `budget`, `country_code`, `rent_from`, `rent_to`, `description`, `status`, `created_at`) VALUES
(1, 38, 'Rental request', 'hospitality_units', 1500.00, 'IT', '2026-07-01', '2026-07-03', 'test', 'distributed', '2026-07-30 07:55:01');

-- --------------------------------------------------------

--
-- Struttura della tabella `07_rent_request_recipients`
--

CREATE TABLE `07_rent_request_recipients` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Proprietario annuncio noleggio (destinatario)',
  `company_id` int(11) DEFAULT NULL,
  `tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
  `rank_pos` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `emailed_at` datetime DEFAULT NULL COMMENT 'NULL = non notificato via email (solo in area lead)',
  `claimed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `07_rent_request_recipients`
--

INSERT INTO `07_rent_request_recipients` (`id`, `request_id`, `id_user`, `company_id`, `tier`, `rank_pos`, `emailed_at`, `claimed_at`, `created_at`) VALUES
(1, 1, 39, 9, 'premium', 1, '2026-07-30 09:55:02', NULL, '2026-07-30 07:55:02'),
(2, 1, 35, NULL, 'premium', 2, '2026-07-30 09:55:03', NULL, '2026-07-30 07:55:03');

-- --------------------------------------------------------

--
-- Struttura della tabella `admin_audit_log`
--

CREATE TABLE `admin_audit_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_user_id` int(11) UNSIGNED NOT NULL COMMENT 'L admin che ha eseguito',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'grant_premium, revoke_premium, login, ...',
  `target_user_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'L utente bersaglio (NULL per azioni globali)',
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit di tutte le azioni amministrative — non eliminare le righe';

--
-- Dump dei dati per la tabella `admin_audit_log`
--

INSERT INTO `admin_audit_log` (`id`, `admin_user_id`, `action`, `target_user_id`, `details`, `ip_address`, `created_at`) VALUES
(181, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-26 20:29:19'),
(182, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-27 09:03:57'),
(183, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-28 08:46:47'),
(184, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-28 16:53:39'),
(185, 33, 'moderate_ad', NULL, '02_free_ads #90 marked as approved', '127.0.0.2', '2026-07-28 16:54:08'),
(186, 33, 'moderate_ad', NULL, '02_free_ads #89 marked as approved', '127.0.0.2', '2026-07-28 16:54:10'),
(187, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-28 18:00:54'),
(188, 33, 'moderate_ad', NULL, '03_ads #19 marked as approved', '127.0.0.2', '2026-07-28 18:00:58'),
(189, 33, 'moderate_ad', NULL, '02_free_ads #91 marked as approved', '127.0.0.2', '2026-07-28 18:02:21'),
(190, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-28 20:47:14'),
(191, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-29 08:53:43'),
(192, 33, 'moderate_ad', NULL, '03_ads #20 marked as approved', '127.0.0.2', '2026-07-29 08:54:22'),
(193, 33, 'moderate_ad', NULL, '03_ads #21 marked as approved', '127.0.0.2', '2026-07-29 09:14:52'),
(194, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-29 14:57:35'),
(195, 33, 'grant_premium', NULL, 'Granted premium tier to user_id=39', '127.0.0.2', '2026-07-29 14:57:58'),
(196, 33, 'moderate_ad', NULL, '02_free_ads #92 marked as approved', '127.0.0.2', '2026-07-29 15:01:28'),
(197, 33, 'moderate_ad', NULL, '02_free_ads #93 marked as approved', '127.0.0.2', '2026-07-29 15:09:05'),
(198, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-29 19:14:34'),
(201, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-30 10:20:40'),
(203, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-30 12:45:28'),
(204, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-30 21:07:36'),
(205, 33, 'ad_update', 33, 'Premium Ad updated (id_ads=21, table=03_ads)', '127.0.0.2', '2026-07-30 21:08:59'),
(206, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-31 05:54:36'),
(207, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-31 06:31:35'),
(208, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-31 10:42:04'),
(209, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-07-31 14:47:54'),
(210, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-08-02 16:13:18'),
(211, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-08-02 17:03:22'),
(212, 33, 'admin_logout', NULL, 'Admin logout', '127.0.0.2', '2026-08-02 17:04:12'),
(213, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-08-03 08:58:14'),
(214, 33, 'admin_login', NULL, 'Admin login successful', '127.0.0.2', '2026-08-05 22:52:40');

-- --------------------------------------------------------

--
-- Struttura della tabella `ads_documents`
--

CREATE TABLE `ads_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ads` int(10) UNSIGNED NOT NULL,
  `ad_table` enum('02_free_ads','03_ads') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '03_ads',
  `document_type` enum('technical_sheet','floorplan','certificate','manual','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'nome HASH su disco: upload_image/ads_documents/',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'nome mostrato al download',
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ads_document_downloads`
--

CREATE TABLE `ads_document_downloads` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_document` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL se ospite',
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'hash IP (GDPR)',
  `downloaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ad_drafts`
--

CREATE TABLE `ad_drafts` (
  `id` int(10) UNSIGNED NOT NULL,
  `draft_token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token casuale (bin2hex(random_bytes(32))), in cookie httponly',
  `user_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Soft-ref users.id_user. NULL = bozza di un ospite',
  `listing` enum('free','prem') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'Tabella di destinazione: free -> 02_free_ads, prem -> 03_ads',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Campi del wizard serializzati in JSON (solo testo)',
  `step` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Ultimo step completato: per riprendere da dove aveva lasciato',
  `contact_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Email indicata nel wizard (per ricollegare la bozza)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL COMMENT 'Oltre questa data la bozza va cancellata (GDPR: dati di non registrati)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bozze annuncio compilate prima di avere un account (punto 2)';

-- --------------------------------------------------------

--
-- Struttura della tabella `ai_daily_log`
--

CREATE TABLE `ai_daily_log` (
  `run_date` date NOT NULL,
  `blog_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ai_logs`
--

CREATE TABLE `ai_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` enum('INFO','WARNING','ERROR') NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `message` text,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struttura della tabella `blog`
--

CREATE TABLE `blog` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore (users.id_user)',
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `queue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slug SEO univoco',
  `category` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slug categoria (blog_categories.slug)',
  `language` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN',
  `translation_group` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID condiviso dalle versioni tradotte',
  `excerpt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sommario breve (riga in corsivo)',
  `question` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Domanda dell utente (Ask the Experts)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Corpo articolo; paragrafi separati da newline',
  `outlines` text COLLATE utf8mb4_unicode_ci COMMENT 'Scaletta / punti chiave (una riga per voce)',
  `faq_json` text COLLATE utf8mb4_unicode_ci COMMENT 'FAQ per schema.org FAQPage: JSON [{q,a}]',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cover in /upload_image/blog/',
  `status` enum('draft','pending','scheduled','published','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `published_at` datetime DEFAULT NULL COMMENT 'Data/ora di pubblicazione (scheduling)',
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web' COMMENT 'Origine: web | api',
  `ai_provider` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_prompt_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_generated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Articoli del blog pubblicati dagli utenti registrati';

-- --------------------------------------------------------

--
-- Struttura della tabella `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Etichetta EN visibile',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorie dell hub editoriale Ask the Experts';

--
-- Dump dei dati per la tabella `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `slug`, `name`, `sort_order`) VALUES
(1, 'technical-design', 'Technical / Design', 10),
(2, 'feasibility', 'Feasibility', 20),
(3, 'costs', 'Costs', 30),
(4, 'registration', 'Registration', 40);

-- --------------------------------------------------------

--
-- Struttura della tabella `blog_comments`
--

CREATE TABLE `blog_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_blog` int(10) UNSIGNED NOT NULL COMMENT 'Articolo (blog.id)',
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore del commento (users.id_user)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Testo del commento (niente HTML/immagini)',
  `status` enum('visible','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Commenti agli articoli del blog (solo testo)';

-- --------------------------------------------------------

--
-- Struttura della tabella `blog_leads`
--

CREATE TABLE `blog_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_blog` int(10) UNSIGNED DEFAULT NULL COMMENT 'Articolo di provenienza (blog.id)',
  `category` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Categoria articolo al momento del lead',
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `intent` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'feasibility_study | custom_quote | question',
  `status` enum('new','contacted','qualified','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `consent_given` tinyint(1) NOT NULL DEFAULT '0',
  `consent_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 IP (GDPR)',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lead B2B raccolti dai form a fine articolo del blog';

-- --------------------------------------------------------

--
-- Struttura della tabella `blog_translation_jobs`
--

CREATE TABLE `blog_translation_jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `blog_id` int(10) UNSIGNED NOT NULL,
  `from_lang` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation_group` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','done','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `consent_log`
--

CREATE TABLE `consent_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consent_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 dell IP (no IP in chiaro)',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `categories` json NOT NULL,
  `consent_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` enum('grant','deny','update','withdraw') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'grant',
  `form` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `consent_log`
--

INSERT INTO `consent_log` (`id`, `consent_id`, `ip_hash`, `user_agent`, `categories`, `consent_version`, `action`, `form`, `created_at`) VALUES
(1, 'a90807b3-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:09:59'),
(2, 'aa4c8378-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:01'),
(3, 'aae1ae23-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:02'),
(4, 'ac0d97b0-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:04'),
(5, 'b5a09d37-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:20'),
(6, 'b673650e-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:21'),
(7, 'b820ab39-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:24'),
(8, 'bb3aa177-8d02-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-07-31 19:10:29'),
(9, '3c5b1561-8d6a-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-01 07:31:24'),
(10, 'ec9b906d-8d7f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-01 10:06:39'),
(11, 'decd2c7e-8d82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-01 10:27:44'),
(12, '8fcd57a3-8dbb-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 Vivaldi/5.3.2679.68', '{\"form_consent\": true}', '2026-01', 'grant', 'contact', '2026-08-01 17:13:33'),
(13, 'c18780e3-8e07-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/141.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 02:18:58'),
(14, 'c3179252-8e07-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/141.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 02:19:01'),
(15, '3ecfc556-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:12:50'),
(16, '403477e4-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:12:52'),
(17, '4cdb8ee6-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:13:14'),
(18, '68a5ede5-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:14:00'),
(19, '6a082229-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:14:03'),
(20, 'eec18cfc-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:17:45'),
(21, 'fe00e534-8e7c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:18:11'),
(22, '04fa5986-8e7d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:18:23'),
(23, '10575340-8e7d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:18:42'),
(24, '64714f44-8e7f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:35:22'),
(25, 'e7e11af9-8e80-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:46:12'),
(26, 'e8e33877-8e80-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:46:13'),
(27, 'ea49da4e-8e80-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:46:16'),
(28, 'f458c8a6-8e80-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:46:33'),
(29, '4a89c183-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"scope\": \"registration\", \"terms\": true, \"privacy\": true, \"user_id\": 41, \"marketing\": true}', 'reg-1.0', 'grant', NULL, '2026-08-02 16:48:57'),
(30, '4b775baf-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:48:59'),
(31, '73ecb9c7-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:50:07'),
(32, '768179c0-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:50:11'),
(33, '7b410103-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:50:19'),
(34, '7d07c791-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:50:22'),
(35, '825e7b84-8e81-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:50:31'),
(36, '12602843-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:33'),
(37, '12fc98c7-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:34'),
(38, '13c9a2dd-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:35'),
(39, '145387ae-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:36'),
(40, '14fbde24-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:37'),
(41, '158dc6d9-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:38'),
(42, '1615fd69-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:39'),
(43, '1c208e63-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:54:49'),
(44, '3e7d7f40-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:55:47'),
(45, '43795011-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:55:55'),
(46, '74780ed4-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:57:17'),
(47, 'ba6101f4-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:59:14'),
(48, 'c7e55f70-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:59:37'),
(49, 'd4617df1-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 16:59:58'),
(50, 'd57ce0be-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:00'),
(51, 'd64000b7-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:01'),
(52, 'dd3cf761-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:13'),
(53, 'deefbff7-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:16'),
(54, 'eb84f4f6-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:37'),
(55, 'ee1992ca-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:41'),
(56, 'f1e993cd-8e82-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:00:48'),
(57, '021e05db-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:15'),
(58, '07307e6e-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:23'),
(59, '0aadfb73-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:29'),
(60, '1463cc44-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:45'),
(61, '16cb89f2-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:49'),
(62, '1989102f-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:54'),
(63, '1bc473b5-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:01:58'),
(64, '1d041b22-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:02:00'),
(65, '1e21b67e-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:02:02'),
(66, '2083754d-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:02:06'),
(67, '2218196e-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:02:08'),
(68, '45798304-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:03:08'),
(69, '6c2e53d0-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:04:13'),
(70, '6d904052-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:04:15'),
(71, '6f968e1e-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:04:18'),
(72, '8d12b81b-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:05:08'),
(73, '996415c1-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:29'),
(74, '9b378f4d-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:32'),
(75, '9bc072c8-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:33'),
(76, '9d70863b-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:35'),
(77, '9eb3b17a-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:37'),
(78, '9f66f8e5-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:39'),
(79, 'a0f31881-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:41'),
(80, 'a24eb9ea-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:44'),
(81, 'a331063a-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:45'),
(82, 'a3eca32e-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:46'),
(83, 'a5e29730-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:50'),
(84, 'a7486ba0-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:05:52'),
(85, 'ac345983-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:00'),
(86, 'ad88057c-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:02'),
(87, 'b0164562-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:07'),
(88, 'b1baae7a-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:09'),
(89, 'b9e660e2-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:23'),
(90, 'c2a6ef0b-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:06:38'),
(91, 'd133f5cd-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:02'),
(92, 'e4630b71-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:34'),
(93, 'e64eeb03-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:38'),
(94, 'ea20452c-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:44'),
(95, 'ed30f2f9-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:49'),
(96, 'eec5b897-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:07:52'),
(97, 'f04a2ea8-8e83-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-02 17:07:54'),
(98, '217ec382-8e84-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:09:17'),
(99, '226596d5-8e84-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:09:18'),
(100, '2323504e-8e84-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:09:20'),
(101, '37f69ed7-8e89-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 17:45:42'),
(102, '03a2aa84-8e8d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 18:12:52'),
(103, '226215cb-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:32'),
(104, '25cfdca8-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:38'),
(105, '2759e44a-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:40'),
(106, '289b5397-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:42'),
(107, '293aff32-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:43'),
(108, '29da4755-8ea5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:05:44'),
(109, '33af2b37-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:10'),
(110, '38cbb39b-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:19'),
(111, '3b16b6a3-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:23'),
(112, '3bec5960-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:24'),
(113, '3cced212-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:26'),
(114, '3ef56bf8-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:29'),
(115, '3fdeab11-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:31'),
(116, '40f1f648-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:13:33'),
(117, '58cae950-8ea6-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:14:13'),
(118, '1cd683f5-8ea7-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:19:41'),
(119, 'c4bd3a88-8ea7-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:24:23'),
(120, '8de932c7-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:01'),
(121, '8ed89826-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:02'),
(122, '91da3389-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:07'),
(123, '94b8855a-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:12'),
(124, '95a78de2-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:14'),
(125, '967360fc-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:15'),
(126, '98845438-8ea8-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-02 21:30:18'),
(127, '3da9793b-8efe-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 07:43:23'),
(128, '406aca7d-8efe-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 07:43:27'),
(129, '413a3d8c-8efe-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 07:43:29'),
(130, '537bfc7a-8efe-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 07:43:59'),
(131, '545644a7-8eff-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 07:51:10'),
(132, 'bc4e3943-8f05-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:37:02'),
(133, 'bd6e2bbb-8f05-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:37:04'),
(134, 'bdf883e7-8f05-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:37:04'),
(135, 'be6969e5-8f05-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:37:05'),
(136, 'bc2072b4-8f07-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:51:20'),
(137, 'aed2375c-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:58:08'),
(138, 'e097552a-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:31'),
(139, 'e18073bf-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:33'),
(140, 'e7a37ac8-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:43'),
(141, 'e919ce8d-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:45'),
(142, 'e9eb59f3-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:47'),
(143, 'eaaae2ad-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:48'),
(144, 'ecdce2b6-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:52'),
(145, 'edbaa6b0-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:53'),
(146, 'ef312cea-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:56'),
(147, 'efac43f0-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:56'),
(148, 'f0154697-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:57'),
(149, 'f05e1918-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 08:59:58'),
(150, 'f198bec2-8f08-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:00:00'),
(151, '1ba514af-8f09-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:01:10'),
(152, '1c0dfdcf-8f09-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:01:11'),
(153, '1ea20b03-8f09-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:01:15'),
(154, '2029d3e5-8f09-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:01:18'),
(155, 'c6b76dfe-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:41:45'),
(156, 'd263cf8f-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:04'),
(157, 'd361e800-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:06'),
(158, 'd5cd3381-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:10'),
(159, 'd68d7d70-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:11'),
(160, 'd70339aa-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:12');
INSERT INTO `consent_log` (`id`, `consent_id`, `ip_hash`, `user_agent`, `categories`, `consent_version`, `action`, `form`, `created_at`) VALUES
(161, 'd75e04f7-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:13'),
(162, 'd8ec6ae0-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:15'),
(163, 'd9682474-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:16'),
(164, 'd9d4b5dc-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:17'),
(165, 'e0cfd3b3-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:28'),
(166, 'e15b19e1-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:29'),
(167, 'e1ca8c8b-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:30'),
(168, 'f05f2fad-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:55'),
(169, 'f121818e-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:42:56'),
(170, 'f460c861-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:43:01'),
(171, 'f4dddc22-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:43:02'),
(172, 'f54860e2-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:43:03'),
(173, 'f5c7e179-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:43:04'),
(174, 'f715723d-8f0e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:43:06'),
(175, '6de1a152-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:25'),
(176, '6e712be6-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:26'),
(177, '6ef739c2-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:27'),
(178, '7558bb89-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:38'),
(179, '7c00e48f-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:49'),
(180, '7c32ac79-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:46:49'),
(181, '85b6e908-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:47:05'),
(182, '8c83d181-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:47:16'),
(183, '993a90fa-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:47:38'),
(184, 'a0320901-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:47:50'),
(185, 'b4504cee-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:48:23'),
(186, 'c2cab73d-8f0f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 09:48:48'),
(187, '09128be2-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:37:44'),
(188, '0abe2001-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:37:47'),
(189, '0d810f6d-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:37:52'),
(190, '10980786-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:37:57'),
(191, '118516bf-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:37:58'),
(192, '2474dedb-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:38:30'),
(193, '263847b2-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:38:33'),
(194, '28f986e8-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:38:38'),
(195, '542a7eb2-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:39:50'),
(196, '56e76968-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:39:55'),
(197, '5862ef8c-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:39:57'),
(198, '5b301b5f-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:02'),
(199, '5c788be1-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:04'),
(200, '5d84b5f6-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:06'),
(201, '5e34be1c-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:07'),
(202, '5eb017c6-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:08'),
(203, '60979139-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:11'),
(204, '617fccd2-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:13'),
(205, '62246235-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:40:14'),
(206, '80898592-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-03 19:41:05'),
(207, '97662295-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-03 19:41:43'),
(208, '989ccaa5-8f62-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-03 19:41:45'),
(209, '61bc1063-8f65-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-03 20:01:41'),
(210, '8a76ce6c-8fc9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 07:58:39'),
(211, '8b99e105-8fc9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 07:58:41'),
(212, '8db0cda8-8fc9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 07:58:45'),
(213, '8ef8432c-8fc9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 07:58:47'),
(214, '90d02493-8fc9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 07:58:50'),
(215, 'be040bd6-9018-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:25:36'),
(216, 'c052e9f0-901b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:47:09'),
(217, 'fa92af60-901b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:48:46'),
(218, 'fbc563a4-901b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:48:48'),
(219, 'fcba907a-901b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:48:50'),
(220, '29ea093a-901c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:50:06'),
(221, '2c633d9d-901c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:50:10'),
(222, '4b4e715e-901c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:51:02'),
(223, '4de3fffb-901c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:51:06'),
(224, '4edbe380-901c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 17:51:08'),
(225, '7e91317d-901f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 18:13:56'),
(226, '80fbedcf-901f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-04 18:14:00'),
(227, '86b7e167-901f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:14:10'),
(228, 'aa0d38cf-901f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:15:09'),
(229, 'b132505b-901f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:15:21'),
(230, '227f5d76-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:31'),
(231, '23cbc1f7-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:33'),
(232, '2663f446-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:38'),
(233, '277a3672-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:40'),
(234, '2823d39d-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:41'),
(235, '2c385a5d-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:48'),
(236, '2dd06eb0-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:50'),
(237, '2ffa3362-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:18:54'),
(238, '3510dc5f-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:19:02'),
(239, 'b0327f82-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:22:29'),
(240, 'fd9c6256-9020-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:24:39'),
(241, '25a0e676-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:25:46'),
(242, '266ffd8d-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:25:47'),
(243, '27649d84-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:25:49'),
(244, '31d6e855-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:26:06'),
(245, '382918fd-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:26:17'),
(246, '39216f3c-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:26:19'),
(247, '435d05ab-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:26:36'),
(248, '44d054a3-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:26:38'),
(249, '7e91854a-9021-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:28:15'),
(250, '40522d85-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:33:40'),
(251, '5fd4b52b-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:34:33'),
(252, '60d94df5-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:34:35'),
(253, '94c1385c-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:02'),
(254, '960fcb92-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:04'),
(255, 'ab5731e4-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:40'),
(256, 'acf2c001-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:43'),
(257, 'ad74ac9a-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:43'),
(258, 'ae14aef4-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:44'),
(259, 'aead0f64-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:45'),
(260, 'b2dfcbae-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:52'),
(261, 'b51d1a78-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:36:56'),
(262, 'b71c4d6d-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:37:00'),
(263, 'b9a81719-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:37:04'),
(264, 'ec477f4a-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:38:29'),
(265, 'ec67c995-9022-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:38:29'),
(266, '0cd026e0-9023-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:39:23'),
(267, '0d28b723-9023-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:39:24'),
(268, '0e083878-9023-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-04 18:39:25'),
(269, 'd792e7c3-909f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 09:32:41'),
(270, '868e4b44-90a2-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 09:51:54'),
(271, '890684fe-90a2-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 09:51:58'),
(272, 'a4da2aee-90a5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 10:14:13'),
(273, 'abd421e1-90a5-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 10:14:25'),
(274, '4fdf61a4-90cb-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 14:43:51'),
(275, '51ed79f0-90cb-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 14:43:55'),
(276, '52b65c01-90cb-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 14:43:56'),
(277, '547d51ae-90cb-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 14:43:59'),
(278, '467be2da-90d7-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:09:30'),
(279, '512baaa4-90d7-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:09:47'),
(280, 'ff57deb9-90d9-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:28:59'),
(281, '0101d0e9-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:01'),
(282, '12fc31b7-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:32'),
(283, '1599d609-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:36'),
(284, '170e9446-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:38'),
(285, '1bd93157-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:46'),
(286, '21d24345-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:56'),
(287, '22aee5f1-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:58'),
(288, '23755efd-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:29:59'),
(289, '23ee0e04-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:00'),
(290, '244c7d5f-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:01'),
(291, '2a6aab11-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:11'),
(292, '33cf9422-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:27'),
(293, '35b31c02-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:30'),
(294, '3f6bee3c-90da-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 16:30:46'),
(295, '9c24afb8-90ef-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-05 19:03:41'),
(296, '07ab8f12-9100-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '{\"v\": \"1.0\", \"analytics\": false, \"marketing\": false}', '1.0', 'deny', NULL, '2026-08-05 21:01:13'),
(297, 'd9463105-910b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:25:50'),
(298, 'e10102d4-910b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:26:03'),
(299, 'e2a9bd1d-910b-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:26:05'),
(300, 'b1cc37b1-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"scope\": \"registration\", \"terms\": true, \"privacy\": true, \"user_id\": 42, \"marketing\": true}', 'reg-1.0', 'grant', NULL, '2026-08-05 22:31:53'),
(301, 'b2d2384b-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:31:55'),
(302, 'bc0389a4-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:32:10'),
(303, 'bf41609f-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:32:15'),
(304, 'd67cbe20-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:32:54'),
(305, 'dadd32c9-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:02'),
(306, 'e57faf78-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:20'),
(307, 'e7e5dc3a-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:24'),
(308, 'e8d230da-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:25'),
(309, 'eab25acf-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:28'),
(310, 'ee4961dd-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:34'),
(311, 'f96cc614-910c-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:33:53'),
(312, '1c3693c1-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:34:51'),
(313, '1d39b559-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:34:53'),
(314, '1e196148-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:34:55'),
(315, '1f2f66ea-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:34:56'),
(316, 'c14a2103-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:39:28'),
(317, 'c26ea692-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:39:30'),
(318, 'dc4ae3eb-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:14'),
(319, 'de238ba3-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:17'),
(320, 'df0add4f-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:18'),
(321, 'e695bb77-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:31');
INSERT INTO `consent_log` (`id`, `consent_id`, `ip_hash`, `user_agent`, `categories`, `consent_version`, `action`, `form`, `created_at`) VALUES
(322, 'e7653155-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:32'),
(323, 'ef3dc474-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:45'),
(324, 'f50e4183-910d-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:40:55'),
(325, '0c93c08d-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:41:35'),
(326, '22bd0076-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:42:12'),
(327, '24198595-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:42:14'),
(328, '24fa3df0-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:42:16'),
(329, '261bbfcb-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:42:18'),
(330, '57f4be66-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:43:41'),
(331, '9a38f379-910e-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:45:32'),
(332, '4ec355a5-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:50:35'),
(333, '522ab388-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:50:41'),
(334, '560e44a7-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:50:47'),
(335, '6171f389-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:51:07'),
(336, '707a2d3b-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:51:32'),
(337, '92ab4243-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:52:29'),
(338, '9662d7fc-910f-11f1-ad29-525400192758', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '{\"v\": \"1.0\", \"analytics\": true, \"marketing\": true}', '1.0', 'grant', NULL, '2026-08-05 22:52:35');

-- --------------------------------------------------------

--
-- Struttura della tabella `editorial_queue`
--

CREATE TABLE `editorial_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `publish_at` datetime NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '5',
  `language` char(2) NOT NULL DEFAULT 'EN',
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `secondary_keywords` text,
  `target_words` smallint(5) UNSIGNED NOT NULL DEFAULT '1500',
  `source_file` varchar(255) DEFAULT NULL,
  `source_row` int(11) DEFAULT NULL,
  `prompt_version` varchar(20) NOT NULL DEFAULT '1.0',
  `ai_model` varchar(100) NOT NULL,
  `blog_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('NEW','PROCESSING','GENERATED','TRANSLATED','PUBLISHED','ERROR') NOT NULL DEFAULT 'NEW',
  `retries` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `last_error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struttura della tabella `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempted_at`) VALUES
(60, 'william.peth@icloud.com', '127.0.0.2', '2026-07-31 21:37:04'),
(61, 'hellojb23@proton.me', '127.0.0.2', '2026-08-01 20:01:03'),
(62, 'i.ren.e.belc@gmail.com', '127.0.0.2', '2026-08-01 22:40:20'),
(64, 'yoder510@yahoo.com', '127.0.0.2', '2026-08-02 23:11:26'),
(65, 'mam@mamgroup.ae', '127.0.0.2', '2026-08-02 23:11:37'),
(66, '6092135227@txt.att.net', '127.0.0.2', '2026-08-03 08:54:46'),
(67, 'k.is.l.erq.s@gmail.com', '127.0.0.2', '2026-08-03 16:56:22'),
(68, 'dianalawrence726@hotmail.com', '127.0.0.2', '2026-08-03 16:56:31'),
(69, 'btibbetts@agavewellness.org', '127.0.0.2', '2026-08-03 19:34:22'),
(70, 'petert@aero.org', '127.0.0.2', '2026-08-03 19:34:32'),
(71, 'yudieskimontesdeoca@yahoo.com', '127.0.0.2', '2026-08-03 21:34:21'),
(72, 'jack@pws.bz', '127.0.0.2', '2026-08-04 00:13:22'),
(73, 'accouting@chilleruptime.com', '127.0.0.2', '2026-08-04 00:14:30'),
(74, 'admin@blueangelshc.com', '127.0.0.2', '2026-08-04 02:34:30'),
(75, 'nicolelowes@yahoo.com', '127.0.0.2', '2026-08-04 08:39:41'),
(76, 'j.e.far.m.s.t@gmail.com', '127.0.0.2', '2026-08-04 08:39:42'),
(77, 'a.a.r.on.mv.h.ardy@gmail.com', '127.0.0.2', '2026-08-04 11:40:41'),
(78, 'r.a.dte.ch.m.o.m.o@gmail.com', '127.0.0.2', '2026-08-04 17:13:48'),
(79, 'breid@samuelsgroup.net', '127.0.0.2', '2026-08-04 17:13:55'),
(80, 'districtoffice@smvwcd.org', '127.0.0.2', '2026-08-04 19:53:49'),
(81, 'c.ent.ra.l.val.l.e.yh.om.e.i.m.p.r.o.v.ement@gmail.com', '127.0.0.2', '2026-08-04 19:53:54'),
(82, 'ter.r.ic.owe.n.6.9@gmail.com', '127.0.0.2', '2026-08-04 21:44:57'),
(83, 'bbjraf@yahoo.com', '127.0.0.2', '2026-08-04 23:01:34'),
(84, 'b.4u.2.d.0d@gmail.com', '127.0.0.2', '2026-08-04 23:05:06'),
(85, 'ho...m.ing.t.s.ung@gmail.com', '127.0.0.2', '2026-08-05 00:49:44'),
(86, 'p.a.v.i.1.98.6@gmail.com', '127.0.0.2', '2026-08-05 00:49:58'),
(87, 'invoice.3k@3kronor.com', '127.0.0.2', '2026-08-05 03:52:00'),
(88, 'anguz_chris@hotmail.com', '127.0.0.2', '2026-08-05 07:33:25'),
(89, 'vitilevu@hanmail.net', '127.0.0.2', '2026-08-05 07:33:39'),
(90, 'saber@vtgooo.com', '127.0.0.2', '2026-08-05 14:57:05'),
(91, 'Oliver Smith', '127.0.0.2', '2026-08-05 22:33:01');

-- --------------------------------------------------------

--
-- Struttura della tabella `product_macros`
--

CREATE TABLE `product_macros` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Label EN in UI (catalogo B2B)',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave macro (ProductMacro::*), usata in 02/03_ads.product_macro',
  `sort_order` smallint(5) NOT NULL DEFAULT '0',
  `intro_text` text COLLATE utf8mb4_unicode_ci COMMENT 'Testo introduttivo landing macro (Fase 1)',
  `intro_text_it` text COLLATE utf8mb4_unicode_ci COMMENT 'Intro macro in italiano (i18n, fallback su intro_text)',
  `hero_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Immagine hero landing macro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Macro-categorie motorsport di brand (overlay Fase 1)';

--
-- Dump dei dati per la tabella `product_macros`
--

INSERT INTO `product_macros` (`id`, `name`, `slug`, `sort_order`, `intro_text`, `intro_text_it`, `hero_image`) VALUES
(1, 'Race Trailer', 'race-trailer', 10, 'The complete paddock solution for professional race teams. Garage, workshop and office in one transporter, with electrical and HVAC systems, telemetry connections, tail lift and belly storage. From two-car decks to three-car configurations with demountable upper deck, browse current offers from sellers and rental operators worldwide.', 'La soluzione paddock completa per i team professionistici. Garage, officina e ufficio in un unico trasportatore, con impianti elettrici e di climatizzazione, connessioni per la telemetria, sponda idraulica e stivaggio nel sottocassa. Dai pianali per due vetture alle configurazioni per tre vetture con piano superiore smontabile: sfoglia le offerte di venditori e operatori di noleggio in tutto il mondo.', '/upload_image/macros/race-trailer-1785135952.jpg'),
(2, 'Hospitality', 'hospitality', 20, 'Your brand on the road. Multi-storey hospitality structures with office space, driver areas, dining rooms, kitchens, roof terraces and sponsors lounges, the pinnacle of coachbuilding found at the front of every elite paddock. Explore new builds and second-hand units, and connect with certified bodybuilders.', 'Il tuo marchio su strada. Strutture di hospitality multipiano con uffici, aree per i piloti, sale pranzo, cucine, terrazze panoramiche e lounge per gli sponsor: l\'apice dell\'allestimento, in prima fila in ogni paddock d\'élite. Scopri allestimenti nuovi e usati e mettiti in contatto con allestitori certificati.', '/upload_image/macros/hospitality-1785740318.jpg'),
(3, 'Mobile Clinic', 'mobile-clinic', 30, 'Medical and care units built for the demands of the paddock. Self-contained mobile clinics and medical centres for race events and large-scale activations, with treatment rooms, equipment bays and integrated power systems on a truck or trailer base. Browse available units and specialist builders.', 'Unità mediche e di assistenza costruite per le esigenze del paddock. Cliniche mobili e centri medici autonomi per gare ed eventi su larga scala, con sale visita, vani per le attrezzature e sistemi di alimentazione integrati su base autocarro o rimorchio. Sfoglia le unità disponibili e gli allestitori specializzati.', '/upload_image/macros/mobile-clinic-1785135967.jpg'),
(4, 'Shelter & Container', 'shelter-container', 40, 'Deployable shelters and converted containers for any environment. Modular shelters and ISO-container conversions for storage, command posts, workshops and technical rooms, rugged, transportable and quick to deploy at the circuit or on site. Browse listings or connect with a builder.', 'Shelter dispiegabili e container allestiti per ogni ambiente. Shelter modulari e conversioni di container ISO per stoccaggio, posti di comando, officine e locali tecnici: robusti, trasportabili e rapidi da installare in circuito o in cantiere. Sfoglia gli annunci o contatta un allestitore.', '/upload_image/macros/shelter-container-1785135982.jpg'),
(5, 'Custom Projects', 'custom-projects', 50, 'Bespoke builds when nothing off-the-shelf will do. One-off roadshow units, brand-experience vehicles and fully tailored paddock structures designed around your team or campaign. Tell us what you need and we will connect you with the right specialist.', 'Realizzazioni su misura quando il prodotto standard non basta. Unità roadshow uniche, veicoli per brand experience e strutture paddock interamente personalizzate, progettate attorno al tuo team o alla tua campagna. Dicci cosa ti serve e ti metteremo in contatto con lo specialista giusto.', '/upload_image/macros/custom-projects-1784972808.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `prompt_templates`
--

CREATE TABLE `prompt_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables` json DEFAULT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'openai',
  `model` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gpt-5.5',
  `temperature` decimal(3,2) NOT NULL DEFAULT '0.70',
  `max_tokens` int(11) NOT NULL DEFAULT '4096',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `prompt_templates`
--

INSERT INTO `prompt_templates` (`id`, `code`, `category`, `version`, `name`, `description`, `content`, `variables`, `provider`, `model`, `temperature`, `max_tokens`, `active`, `created_at`, `updated_at`) VALUES
(1, 'article_generate', 'articles', 1, 'Generate Article', 'Complete article generation', 'You are an experienced B2B technical writer.\r\n\r\nWrite a complete article using the following information.\r\n\r\nTopic:\r\n{{topic}}\r\n\r\nKeywords:\r\n{{keywords}}\r\n\r\nAudience:\r\n{{audience}}\r\n\r\nCountry:\r\n{{country}}\r\n\r\nLanguage:\r\n{{language}}\r\n\r\nTone:\r\n{{tone}}\r\n\r\nLength:\r\n{{length}}\r\n\r\nPurpose:\r\n{{purpose}}\r\n\r\nReferences:\r\n{{references}}\r\n\r\nReturn ONLY valid JSON.\r\n\r\n{\r\n\"title\":\"\",\r\n\"excerpt\":\"\",\r\n\"body\":\"\"\r\n}', '[\"topic\", \"keywords\", \"audience\", \"country\", \"language\", \"tone\", \"length\", \"purpose\", \"references\"]', 'openai', 'gpt-5.5', 0.70, 4096, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(2, 'article_rewrite', 'articles', 1, 'Rewrite Article', 'Rewrite article maintaining meaning', 'Rewrite the following article.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"title\":\"\",\r\n\"excerpt\":\"\",\r\n\"body\":\"\"\r\n}', '[\"title\", \"body\"]', 'openai', 'gpt-5.5', 0.60, 4096, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(3, 'article_seo', 'articles', 1, 'SEO Optimization', 'Optimize article for SEO', 'Optimize this article for SEO.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"title\":\"\",\r\n\"excerpt\":\"\",\r\n\"body\":\"\"\r\n}', '[\"title\", \"body\"]', 'openai', 'gpt-5.5', 0.40, 4096, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(4, 'article_translate', 'articles', 1, 'Translate Article', 'Translate article', 'Translate this article.\r\n\r\nTarget language:\r\n{{language}}\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"title\":\"\",\r\n\"excerpt\":\"\",\r\n\"body\":\"\"\r\n}', '[\"language\", \"title\", \"body\"]', 'openai', 'gpt-5.5', 0.20, 4096, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(5, 'article_tags', 'articles', 1, 'Generate Tags', 'Generate SEO tags', 'Generate up to 15 SEO tags.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"tags\":[]\r\n}', '[\"title\", \"body\"]', 'openai', 'gpt-5.5-mini', 0.30, 1000, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(6, 'article_faq', 'articles', 1, 'Generate FAQ', 'Generate FAQ', 'Generate FAQ for the following article.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"faq\":[]\r\n}', '[\"title\", \"body\"]', 'openai', 'gpt-5.5-mini', 0.40, 2000, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(7, 'article_schema', 'articles', 1, 'Generate Schema', 'Generate JSON-LD', 'Generate Article Schema.org JSON-LD.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nBody:\r\n{{body}}\r\n\r\nReturn ONLY JSON.\r\n\r\n{\r\n\"schema\":{}\r\n}', '[\"title\", \"body\"]', 'openai', 'gpt-5.5-mini', 0.20, 2000, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35'),
(8, 'article_social', 'marketing', 1, 'Social Post', 'Generate social post', 'Create a {{platform}} post.\r\n\r\nTitle:\r\n{{title}}\r\n\r\nExcerpt:\r\n{{excerpt}}\r\n\r\nReturn only text.', '[\"platform\", \"title\", \"excerpt\"]', 'openai', 'gpt-5.5-mini', 0.80, 1000, 1, '2026-08-04 20:09:35', '2026-08-04 20:09:35');

-- --------------------------------------------------------

--
-- Struttura della tabella `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `buyer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'product_macros.slug se richiesta via configuratore',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categories_json` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON {macro, regular[], special[]} inoltrato',
  `message` text COLLATE utf8mb4_unicode_ci COMMENT 'Messaggio libero del richiedente',
  `consent_given` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Consenso esplicito alla condivisione del contatto con i fornitori',
  `consent_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Versione del testo di consenso mostrato',
  `consent_ip_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SHA-256 dell IP (no IP in chiaro)',
  `consent_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `consent_at` datetime DEFAULT NULL COMMENT 'Timestamp del consenso',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new' COMMENT 'new|distributed|quoted|won|lost',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_ads` int(10) UNSIGNED DEFAULT NULL COMMENT 'annuncio sorgente; NULL se Wanted/generico',
  `ad_table` enum('02_free_ads','03_ads') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_buyer` int(10) UNSIGNED DEFAULT NULL COMMENT 'utente loggato se presente',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lead: richieste di offerta (RFQ) inoltrate ai fornitori';

--
-- Dump dei dati per la tabella `quote_requests`
--

INSERT INTO `quote_requests` (`id`, `buyer_name`, `buyer_email`, `macro`, `vehicle_type`, `categories_json`, `message`, `consent_given`, `consent_version`, `consent_ip_hash`, `consent_user_agent`, `consent_at`, `status`, `created_at`, `id_ads`, `ad_table`, `id_buyer`, `company_name`, `contact_name`, `phone`, `country_code`) VALUES
(10, 'Marco', 'marco.candian@yahoo.it', NULL, NULL, '{\"section\":\"special\",\"macro\":null,\"regular\":[],\"special\":[\"box_trailer\",\"hospitality_units\",\"paddock_trailers\",\"racing_trailer\",\"laboratori_medici_mobili\"]}', 'I consent to my contact details being shared with the matching suppliers so they can send me an offer.', 1, 'rfq-1', 'f376df726c6dae071fa355c7a56167444c16d60aab5a5527309c52fa623c0a99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-29 18:55:11', 'new', '2026-07-29 18:55:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `quote_request_recipients`
--

CREATE TABLE `quote_request_recipients` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL COMMENT 'Soft-ref 06_company.id',
  `sent_ok` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = mail() riuscita, 0 = fallita',
  `deliver_at` datetime DEFAULT NULL COMMENT 'Quando il lead diventa inviabile/visibile al fornitore (ritardo per piano)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `match_score` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Chiavi prodotto in comune con la richiesta (pertinenza)',
  `rank_pos` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Posizione in graduatoria al momento dell''invio (1 = piu'' pertinente)',
  `claimed_at` datetime DEFAULT NULL COMMENT 'Quando il fornitore ha preso in carico il lead (NULL = non ancora)',
  `claimed_by` int(10) UNSIGNED DEFAULT NULL COMMENT '06_company.id che ha preso in carico (di norma = company_id)',
  `reminded_at` datetime DEFAULT NULL COMMENT 'Quando e-mail di sollecito e stata inviata al fornitore (cron)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Destinatari di ciascuna RFQ con esito di invio';

--
-- Dump dei dati per la tabella `quote_request_recipients`
--

INSERT INTO `quote_request_recipients` (`id`, `request_id`, `company_id`, `sent_ok`, `deliver_at`, `created_at`, `match_score`, `rank_pos`, `claimed_at`, `claimed_by`, `reminded_at`) VALUES
(6, 10, 9, 0, '2026-08-01 18:55:12', '2026-07-29 18:55:12', 5, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `q` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vtype` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `freq` enum('daily','weekly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confirm_token` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `seller_statistics`
--

CREATE TABLE `seller_statistics` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ads` int(10) UNSIGNED NOT NULL,
  `ad_table` enum('02_free_ads','03_ads') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '03_ads',
  `views` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `unique_views` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rfq_received` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pdf_downloads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `phone_clicks` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `whatsapp_clicks` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `seller_statistics`
--

INSERT INTO `seller_statistics` (`id`, `id_ads`, `ad_table`, `views`, `unique_views`, `rfq_received`, `pdf_downloads`, `phone_clicks`, `whatsapp_clicks`, `updated_at`) VALUES
(1, 95, '02_free_ads', 18, 13, 0, 0, 0, 0, '2026-08-05 19:49:55');

-- --------------------------------------------------------

--
-- Struttura della tabella `seo_taxonomy_cache`
--

CREATE TABLE `seo_taxonomy_cache` (
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'es: race-trailer-mercedes (composto)',
  `lang` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `total_wanted` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `min_price` decimal(12,2) DEFAULT NULL,
  `max_price` decimal(12,2) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave univoca dell impostazione (es. hero_image)',
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Valore. Per hero_image: percorso relativo tipo upload_image/hero/xxx.jpg',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'Soft-ref: admin che ha modificato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Impostazioni di sito chiave/valore';

--
-- Dump dei dati per la tabella `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`, `updated_by`) VALUES
('ad_limit_free', '5', '2026-07-31 10:42:25', 33),
('ad_limit_premium', '15', '2026-07-31 06:51:41', 33),
('hero_image', 'upload_image/hero/hero-1785487874.png', '2026-07-31 10:51:14', 33);

-- --------------------------------------------------------

--
-- Struttura della tabella `social_posts`
--

CREATE TABLE `social_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'users.id_user (proprietario azienda)',
  `company_id` int(10) UNSIGNED DEFAULT NULL COMMENT '06_company.id (opzionale)',
  `platform` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'facebook|instagram|linkedin|...',
  `permalink` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `posted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'quando l''IA ha pubblicato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pubblicazioni social gestite dall''IA (contatore quota per piano)';

-- --------------------------------------------------------

--
-- Struttura della tabella `special_types`
--

CREATE TABLE `special_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Label inglese visualizzata in UI',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave: stessa convenzione di vehicle_types.slug',
  `sort_order` smallint(5) NOT NULL DEFAULT '0',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Sempre special: colonna tenuta per simmetria con vehicle_types',
  `source_slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Se duplicata da vehicle_types, lo slug di origine'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipologie speciali (veicoli speciali e shelter), curate da admin';

--
-- Dump dei dati per la tabella `special_types`
--

INSERT INTO `special_types` (`id`, `name`, `slug`, `sort_order`, `macro_category`, `source_slug`) VALUES
(1, 'Motorhomes', 'camper', 50, 'special', NULL),
(2, 'Mobile medical labs', 'laboratori_medici_mobili', 150, 'special', NULL),
(3, 'Mobile offices', 'uffici_mobili', 260, 'special', NULL),
(4, 'Racing trailer', 'racing_trailer', 0, 'special', NULL),
(5, 'Box trailers', 'box_trailer', 0, 'special', NULL),
(6, 'Hospitality units', 'hospitality_units', 0, 'special', NULL),
(7, 'Paddock trailers', 'paddock_trailers', 0, 'special', NULL),
(8, 'On demand', 'on_demand', 999, 'special', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id_user` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_tier` enum('free','premium','gold','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'free=Basic(2 ads) / premium=Silver(15 ads) / gold=Gold(unlimited+priority) / admin',
  `premium_requested` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = utente ha cliccato "Request premium" (in attesa di approvazione admin)',
  `premium_requested_at` datetime DEFAULT NULL COMMENT 'Timestamp della richiesta (per ordinamento coda admin)',
  `premium_granted_at` datetime DEFAULT NULL COMMENT 'Timestamp ultima concessione (resta valorizzato anche dopo revoca)',
  `public_contact` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Consenso: mostra profilo/contatti nella directory professionisti'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id_user`, `username`, `phone`, `profile_image`, `email`, `password`, `email_verification_token`, `is_verified`, `reset_token`, `reset_expires`, `created_at`, `user_tier`, `premium_requested`, `premium_requested_at`, `premium_granted_at`, `public_contact`) VALUES
(33, 'Marco', '+3930000', 'profile_33_5c03059e9ae98c85.jpg', 'marco.candian@yahoo.it', '$2y$12$qByZmKYPMc6QOU.W9LSFA.9eiRijHg/vrcGju6qUZGMFiAokoJIE6', NULL, 1, NULL, NULL, '2026-04-28 07:45:56', 'admin', 0, NULL, NULL, 1),
(41, 'All_on_Wheel', '+39020000', 'profile_faa997e627c7bc4c.jpg', 'info@allonwheel.com', '$2y$10$2qRZkWHPph3qEgPPqKkhBeXu1ua919CkAxSL70xywJQd9BMtSRYIS', NULL, 1, NULL, NULL, '2026-08-02 14:48:57', 'free', 0, NULL, NULL, 0),
(42, 'Oliver Smith', '+393209536273', 'profile_f5700a99ed697207.png', 'candian46@gmail.com', '$2y$10$0c.sbDGowJKon50CEaA5ge.2p.pjOlMr2A9d1ly0pX4oAba5VkP5i', NULL, 1, NULL, NULL, '2026-08-05 20:31:53', 'admin', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'FK users.id_user',
  `role` enum('expert','project_manager','consultant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ruoli multipli per utente (Esperto/PM/Consulente)';

--
-- Dump dei dati per la tabella `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES
(1, 33, 'expert', '2026-06-17 18:46:48'),
(3, 33, 'consultant', '2026-06-17 18:46:48'),
(10, 34, 'expert', '2026-06-19 21:03:40'),
(11, 34, 'project_manager', '2026-06-19 21:03:40'),
(12, 34, 'consultant', '2026-06-19 21:03:40'),
(13, 39, 'expert', '2026-06-23 13:25:27'),
(14, 39, 'project_manager', '2026-06-23 13:25:27'),
(15, 39, 'consultant', '2026-06-23 13:25:27'),
(19, 35, 'expert', '2026-06-23 14:02:21'),
(20, 35, 'project_manager', '2026-06-23 14:02:21'),
(21, 35, 'consultant', '2026-06-23 14:02:21'),
(26, 40, 'expert', '2026-07-28 16:29:30'),
(27, 40, 'project_manager', '2026-07-28 16:29:30'),
(28, 40, 'consultant', '2026-07-28 16:29:30'),
(30, 33, 'project_manager', '2026-07-29 14:55:49'),
(32, 41, 'expert', '2026-08-02 14:48:57'),
(33, 41, 'project_manager', '2026-08-02 14:48:57'),
(34, 41, 'consultant', '2026-08-02 14:48:57'),
(35, 42, 'expert', '2026-08-05 20:31:53'),
(36, 42, 'project_manager', '2026-08-05 20:31:53'),
(37, 42, 'consultant', '2026-08-05 20:31:53');

-- --------------------------------------------------------

--
-- Struttura della tabella `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Label inglese visualizzata in UI',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chiave in CompanyManager::$products (06_company_products.product_key)',
  `sort_order` smallint(5) NOT NULL DEFAULT '0',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special' COMMENT 'Macro-categoria flowchart: road (elenco chiuso) / special (complemento)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `slug`, `sort_order`, `macro_category`) VALUES
(1, 'Ambulances', 'ambulanze', 10, 'road'),
(2, 'Street food', 'autonegozi_alimentari', 20, 'road'),
(3, 'Haberdashery', 'autonegozi_mercerie', 30, 'road'),
(4, 'Armored', 'blindati', 40, 'road'),
(6, 'Tow trucks', 'carrattrezzi', 60, 'road'),
(7, 'Tippers', 'cassoni', 70, 'road'),
(8, 'Curtain-side bodies', 'centinati', 80, 'road'),
(9, 'Insulated bodies', 'coibentati', 90, 'road'),
(10, 'Disabled access vehicles', 'disabili', 100, 'road'),
(11, 'Law enforcement', 'forze_dell_ordine', 110, 'road'),
(12, 'Refrigerated bodies', 'frigoriferi', 120, 'road'),
(13, 'Box vans', 'furgonature_box', 130, 'road'),
(14, 'Isothermal bodies', 'isotermici', 140, 'road'),
(16, 'Minibuses', 'minibus', 160, 'road'),
(17, 'Mobile workshops', 'officine_mobili', 170, 'road'),
(18, 'Aerial platforms / Cranes', 'piattaforme_aeree_gru', 180, 'road'),
(19, 'Public administration', 'pubblica_amministrazione', 190, 'road'),
(20, 'School buses', 'scuolabus', 200, 'road'),
(21, 'Waste collection vehicles', 'servizi_ecologici', 210, 'road'),
(22, 'Lifting systems', 'sistemi_di_sollevamento', 220, 'road'),
(23, 'Leisure', 'tempo_libero', 230, 'road'),
(24, 'Garment transport', 'trasporto_abiti', 240, 'road'),
(25, 'Animal transport', 'trasporto_animali', 250, 'road'),
(27, 'Fire dept. / Civil protection', 'vvf_protezione_civile', 270, 'road');

-- --------------------------------------------------------

--
-- Struttura della tabella `wanted_ads`
--

CREATE TABLE `wanted_ads` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'acquirente che cerca',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'product_macros.slug',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'vehicle_types.slug',
  `budget` decimal(12,2) DEFAULT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO-2 (IT, DE, FR...)',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','matched','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `02_free_ads`
--
ALTER TABLE `02_free_ads`
  ADD PRIMARY KEY (`id_ads`),
  ADD KEY `idx_02freeads_user` (`id_user`),
  ADD KEY `idx_02freeads_created` (`created_at`),
  ADD KEY `idx_02freeads_type` (`type`),
  ADD KEY `idx_02_free_ads_status` (`status`),
  ADD KEY `idx_02freeads_expires` (`expires_at`),
  ADD KEY `idx_macro_kind` (`macro_category`,`item_kind`),
  ADD KEY `idx_02_product_macro` (`product_macro`);

--
-- Indici per le tabelle `02_free_ads_gallery`
--
ALTER TABLE `02_free_ads_gallery`
  ADD PRIMARY KEY (`id_images`),
  ADD KEY `idx_02gallery_ads` (`id_ads`);

--
-- Indici per le tabelle `03_ads`
--
ALTER TABLE `03_ads`
  ADD PRIMARY KEY (`id_ads`),
  ADD KEY `idx_03ads_user` (`id_user`),
  ADD KEY `idx_03ads_created` (`created_at`),
  ADD KEY `idx_03ads_type` (`type`),
  ADD KEY `idx_03_ads_status` (`status`),
  ADD KEY `idx_03ads_expires` (`expires_at`),
  ADD KEY `idx_macro_kind` (`macro_category`,`item_kind`),
  ADD KEY `idx_03_product_macro` (`product_macro`),
  ADD KEY `idx_03_length` (`length_mt`);

--
-- Indici per le tabelle `03_ads_gallery`
--
ALTER TABLE `03_ads_gallery`
  ADD PRIMARY KEY (`id_images`),
  ADD KEY `idx_03gallery_ads` (`id_ads`);

--
-- Indici per le tabelle `03_ads_tech_details`
--
ALTER TABLE `03_ads_tech_details`
  ADD PRIMARY KEY (`id_tech`),
  ADD UNIQUE KEY `uq_03tech_id_ads` (`id_ads`);

--
-- Indici per le tabelle `06_company`
--
ALTER TABLE `06_company`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_06company_user` (`user_id`),
  ADD UNIQUE KEY `uk_06company_partiva` (`partita_iva`),
  ADD KEY `idx_06company_attiva` (`attiva`),
  ADD KEY `idx_06company_citta` (`citta`),
  ADD KEY `idx_06company_ragione` (`ragione_sociale`);

--
-- Indici per le tabelle `06_company_gallery`
--
ALTER TABLE `06_company_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_06gal_company` (`company_id`,`ordine`,`id`),
  ADD KEY `idx_06gal_user` (`user_id`);

--
-- Indici per le tabelle `06_company_products`
--
ALTER TABLE `06_company_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_06prod_company_key` (`company_id`,`product_key`),
  ADD KEY `idx_06prod_key` (`product_key`);

--
-- Indici per le tabelle `06_company_products_special`
--
ALTER TABLE `06_company_products_special`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_special_company` (`company_id`),
  ADD KEY `idx_special_key` (`product_key`);

--
-- Indici per le tabelle `06_company_services`
--
ALTER TABLE `06_company_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_06srv_company_key` (`company_id`,`service_key`),
  ADD KEY `idx_06srv_key` (`service_key`);

--
-- Indici per le tabelle `07_rent_ads`
--
ALTER TABLE `07_rent_ads`
  ADD PRIMARY KEY (`id_ads`),
  ADD KEY `idx_rentads_match` (`status`,`vehicle_type`),
  ADD KEY `idx_rentads_user` (`id_user`);

--
-- Indici per le tabelle `07_rent_requests`
--
ALTER TABLE `07_rent_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rentreq_user` (`id_user`);

--
-- Indici per le tabelle `07_rent_request_recipients`
--
ALTER TABLE `07_rent_request_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_req_user` (`request_id`,`id_user`),
  ADD KEY `idx_recip_user` (`id_user`);

--
-- Indici per le tabelle `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_audit_admin` (`admin_user_id`,`created_at`),
  ADD KEY `idx_admin_audit_target` (`target_user_id`),
  ADD KEY `idx_admin_audit_action` (`action`,`created_at`);

--
-- Indici per le tabelle `ads_documents`
--
ALTER TABLE `ads_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_docs_ad` (`ad_table`,`id_ads`,`document_type`);

--
-- Indici per le tabelle `ads_document_downloads`
--
ALTER TABLE `ads_document_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dl_doc` (`id_document`);

--
-- Indici per le tabelle `ad_drafts`
--
ALTER TABLE `ad_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_draft_token` (`draft_token`),
  ADD KEY `idx_draft_user` (`user_id`),
  ADD KEY `idx_draft_email` (`contact_email`),
  ADD KEY `idx_draft_expires` (`expires_at`);

--
-- Indici per le tabelle `ai_daily_log`
--
ALTER TABLE `ai_daily_log`
  ADD PRIMARY KEY (`run_date`);

--
-- Indici per le tabelle `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_id` (`queue_id`),
  ADD KEY `level` (`level`);

--
-- Indici per le tabelle `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_slug` (`slug`),
  ADD KEY `idx_blog_user` (`id_user`),
  ADD KEY `idx_blog_status` (`status`),
  ADD KEY `ix_blog_cat` (`category`),
  ADD KEY `ix_blog_sched` (`status`,`published_at`),
  ADD KEY `language` (`language`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `queue_id` (`queue_id`),
  ADD KEY `status` (`status`),
  ADD KEY `ix_blog_lang_status` (`language`,`status`),
  ADD KEY `ix_blog_txgroup` (`translation_group`);

--
-- Indici per le tabelle `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blogcat_slug` (`slug`);

--
-- Indici per le tabelle `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comment_blog` (`id_blog`),
  ADD KEY `idx_comment_user` (`id_user`);

--
-- Indici per le tabelle `blog_leads`
--
ALTER TABLE `blog_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_leads_status` (`status`),
  ADD KEY `ix_leads_blog` (`id_blog`);

--
-- Indici per le tabelle `blog_translation_jobs`
--
ALTER TABLE `blog_translation_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_btj_blog` (`blog_id`),
  ADD KEY `ix_btj_status` (`status`);

--
-- Indici per le tabelle `consent_log`
--
ALTER TABLE `consent_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consent_created` (`created_at`);

--
-- Indici per le tabelle `editorial_queue`
--
ALTER TABLE `editorial_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `uq_eq_source` (`source_file`(150),`source_row`),
  ADD KEY `status` (`status`),
  ADD KEY `publish_at` (`publish_at`),
  ADD KEY `language` (`language`),
  ADD KEY `category` (`category`);

--
-- Indici per le tabelle `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`attempted_at`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indici per le tabelle `product_macros`
--
ALTER TABLE `product_macros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_macro_slug` (`slug`),
  ADD KEY `idx_macro_sort` (`sort_order`);

--
-- Indici per le tabelle `prompt_templates`
--
ALTER TABLE `prompt_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prompt_version` (`code`,`version`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`active`);

--
-- Indici per le tabelle `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qr_macro` (`macro`),
  ADD KEY `idx_qr_status` (`status`),
  ADD KEY `idx_qr_created` (`created_at`);

--
-- Indici per le tabelle `quote_request_recipients`
--
ALTER TABLE `quote_request_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qrr_request` (`request_id`),
  ADD KEY `idx_qrr_company` (`company_id`),
  ADD KEY `idx_qrr_claim` (`claimed_at`);

--
-- Indici per le tabelle `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_due` (`active`,`freq`,`last_sent_at`),
  ADD KEY `idx_confirm` (`confirm_token`),
  ADD KEY `idx_email` (`email`);

--
-- Indici per le tabelle `seller_statistics`
--
ALTER TABLE `seller_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stats_ad` (`ad_table`,`id_ads`);

--
-- Indici per le tabelle `seo_taxonomy_cache`
--
ALTER TABLE `seo_taxonomy_cache`
  ADD PRIMARY KEY (`slug`),
  ADD KEY `idx_seo_lookup` (`lang`,`macro`,`vehicle_type`);

--
-- Indici per le tabelle `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indici per le tabelle `social_posts`
--
ALTER TABLE `social_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_year` (`user_id`,`posted_at`);

--
-- Indici per le tabelle `special_types`
--
ALTER TABLE `special_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_special_slug` (`slug`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_reset_token` (`reset_token`),
  ADD KEY `idx_users_verify_token` (`email_verification_token`),
  ADD KEY `idx_users_created` (`created_at`),
  ADD KEY `idx_users_tier` (`user_tier`),
  ADD KEY `idx_users_premium_req` (`premium_requested`,`premium_requested_at`);

--
-- Indici per le tabelle `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_role` (`user_id`,`role`),
  ADD KEY `idx_role` (`role`);

--
-- Indici per le tabelle `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vt_slug` (`slug`),
  ADD KEY `idx_macro` (`macro_category`);

--
-- Indici per le tabelle `wanted_ads`
--
ALTER TABLE `wanted_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_match` (`macro`,`vehicle_type`,`status`),
  ADD KEY `idx_wanted_user` (`id_user`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `02_free_ads`
--
ALTER TABLE `02_free_ads`
  MODIFY `id_ads` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT per la tabella `02_free_ads_gallery`
--
ALTER TABLE `02_free_ads_gallery`
  MODIFY `id_images` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `03_ads`
--
ALTER TABLE `03_ads`
  MODIFY `id_ads` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT per la tabella `03_ads_gallery`
--
ALTER TABLE `03_ads_gallery`
  MODIFY `id_images` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT per la tabella `03_ads_tech_details`
--
ALTER TABLE `03_ads_tech_details`
  MODIFY `id_tech` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `06_company`
--
ALTER TABLE `06_company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `06_company_gallery`
--
ALTER TABLE `06_company_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `06_company_products`
--
ALTER TABLE `06_company_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336;

--
-- AUTO_INCREMENT per la tabella `06_company_products_special`
--
ALTER TABLE `06_company_products_special`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT per la tabella `06_company_services`
--
ALTER TABLE `06_company_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT per la tabella `07_rent_ads`
--
ALTER TABLE `07_rent_ads`
  MODIFY `id_ads` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `07_rent_requests`
--
ALTER TABLE `07_rent_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `07_rent_request_recipients`
--
ALTER TABLE `07_rent_request_recipients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT per la tabella `ads_documents`
--
ALTER TABLE `ads_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `ads_document_downloads`
--
ALTER TABLE `ads_document_downloads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `ad_drafts`
--
ALTER TABLE `ad_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `blog_leads`
--
ALTER TABLE `blog_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `blog_translation_jobs`
--
ALTER TABLE `blog_translation_jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `consent_log`
--
ALTER TABLE `consent_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- AUTO_INCREMENT per la tabella `editorial_queue`
--
ALTER TABLE `editorial_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT per la tabella `product_macros`
--
ALTER TABLE `product_macros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `prompt_templates`
--
ALTER TABLE `prompt_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `quote_request_recipients`
--
ALTER TABLE `quote_request_recipients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `seller_statistics`
--
ALTER TABLE `seller_statistics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `special_types`
--
ALTER TABLE `special_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT per la tabella `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT per la tabella `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT per la tabella `wanted_ads`
--
ALTER TABLE `wanted_ads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `02_free_ads`
--
ALTER TABLE `02_free_ads`
  ADD CONSTRAINT `fk_02freeads_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `02_free_ads_gallery`
--
ALTER TABLE `02_free_ads_gallery`
  ADD CONSTRAINT `fk_02gallery_ads` FOREIGN KEY (`id_ads`) REFERENCES `02_free_ads` (`id_ads`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `03_ads`
--
ALTER TABLE `03_ads`
  ADD CONSTRAINT `fk_03ads_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `03_ads_gallery`
--
ALTER TABLE `03_ads_gallery`
  ADD CONSTRAINT `fk_03gallery_ads` FOREIGN KEY (`id_ads`) REFERENCES `03_ads` (`id_ads`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `03_ads_tech_details`
--
ALTER TABLE `03_ads_tech_details`
  ADD CONSTRAINT `fk_03tech_ads` FOREIGN KEY (`id_ads`) REFERENCES `03_ads` (`id_ads`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `06_company`
--
ALTER TABLE `06_company`
  ADD CONSTRAINT `fk_06company_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `06_company_gallery`
--
ALTER TABLE `06_company_gallery`
  ADD CONSTRAINT `fk_06gal_company` FOREIGN KEY (`company_id`) REFERENCES `06_company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `06_company_products`
--
ALTER TABLE `06_company_products`
  ADD CONSTRAINT `fk_06prod_company` FOREIGN KEY (`company_id`) REFERENCES `06_company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `06_company_services`
--
ALTER TABLE `06_company_services`
  ADD CONSTRAINT `fk_06srv_company` FOREIGN KEY (`company_id`) REFERENCES `06_company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD CONSTRAINT `fk_admin_audit_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `fk_admin_audit_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Limiti per la tabella `quote_request_recipients`
--
ALTER TABLE `quote_request_recipients`
  ADD CONSTRAINT `fk_qrr_request` FOREIGN KEY (`request_id`) REFERENCES `quote_requests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
