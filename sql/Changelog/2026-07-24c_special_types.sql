-- ============================================================
-- 2026-07-24c_special_types.sql
-- Nuova tassonomia: Road da vehicle_types, Special e Shelter da special_types
--
-- LA LOGICA (sostituisce ogni regola precedente)
--   ROAD     -> tabella `vehicle_types`: la lista estratta dal codice della
--               strada italiano. E' un elenco chiuso, di riferimento.
--   SPECIAL  -> tabella `special_types` (NUOVA): la lista curata
--               dall'amministratore. Puo' contenere voci scritte a mano e
--               voci DUPLICATE da vehicle_types (es. "Ambulanze" esiste come
--               veicolo stradale e puo' esistere anche come allestimento
--               speciale).
--   SHELTER  -> usa la STESSA `special_types`: uno shelter e' un allestimento
--               speciale costruito su container invece che su un veicolo.
--
-- Di conseguenza vehicle_types torna a contenere SOLO road: le voci speciali
-- che oggi stanno li' vengono spostate in special_types, non cancellate.
--
-- GARANZIE
--  - Non distruttiva sui dati: le voci speciali vengono prima COPIATE nella
--    nuova tabella e solo dopo rimosse da vehicle_types. Gli annunci che le
--    referenziano continuano a funzionare: lo slug non cambia, cambia solo
--    la tabella in cui il codice lo cerca.
--  - Idempotente: rieseguendola non duplica nulla.
--  - MySQL 5.7 / MariaDB compatibile (niente sintassi 8.0).
-- ============================================================

-- ------------------------------------------------------------
-- 1) La tabella, identica a vehicle_types
--    `source_slug` e' l'unica aggiunta: serve a ricordare da quale voce di
--    vehicle_types una riga e' stata duplicata, cosi' l'admin vede l'origine
--    e la duplicazione non si ripete per sbaglio. NULL = voce nata qui.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `special_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Label inglese visualizzata in UI',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Chiave: stessa convenzione di vehicle_types.slug',
  `sort_order` smallint(5) NOT NULL DEFAULT '0',
  `macro_category` enum('road','special') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special'
      COMMENT 'Sempre special: colonna tenuta per simmetria con vehicle_types',
  `source_slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
      COMMENT 'Se duplicata da vehicle_types, lo slug di origine',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_special_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tipologie speciali (veicoli speciali e shelter), curate da admin';

-- ------------------------------------------------------------
-- 2) Migrazione: le voci gia' marcate 'special' in vehicle_types
--    diventano il contenuto iniziale di special_types.
--    L'UNIQUE su slug + INSERT IGNORE rendono l'operazione ripetibile.
-- ------------------------------------------------------------
INSERT IGNORE INTO `special_types` (`name`, `slug`, `sort_order`, `macro_category`, `source_slug`)
SELECT v.`name`, v.`slug`, v.`sort_order`, 'special', NULL
  FROM `vehicle_types` v
 WHERE v.`macro_category` = 'special';

-- ------------------------------------------------------------
-- 3) vehicle_types torna a essere la sola lista Road.
--    Si eliminano SOLO le voci che sono state effettivamente copiate:
--    se una copia non fosse riuscita, la riga originale resta al suo posto.
-- ------------------------------------------------------------
DELETE v FROM `vehicle_types` v
 WHERE v.`macro_category` = 'special'
   AND EXISTS (SELECT 1 FROM `special_types` s WHERE s.`slug` = v.`slug`);

-- Le rimanenti sono tutte road: si allinea la colonna, che resta solo per
-- compatibilita' con il codice esistente.
UPDATE `vehicle_types` SET `macro_category` = 'road'
 WHERE `macro_category` <> 'road';

-- ------------------------------------------------------------
-- 4) "On demand": la voce che l'utente sceglie quando non si riconosce
--    in nessun elenco. Vive in special_types perche' e' una richiesta
--    fuori catalogo, quindi da trattare come speciale.
-- ------------------------------------------------------------
INSERT IGNORE INTO `special_types` (`name`, `slug`, `sort_order`, `macro_category`, `source_slug`)
VALUES ('On demand', 'on_demand', 999, 'special', NULL);

-- ---- VERIFICA (facoltativa) ---------------------------------
--   Road: deve restare solo road
--     SELECT macro_category, COUNT(*) FROM `vehicle_types` GROUP BY macro_category;
--   Special: le voci migrate + On demand
--     SELECT slug, name, source_slug FROM `special_types` ORDER BY sort_order, name;
--   Nessun annuncio deve puntare a uno slug che non esiste piu' in NESSUNA
--   delle due tabelle:
--     SELECT DISTINCT a.vehicle_type
--       FROM `02_free_ads` a
--       LEFT JOIN `vehicle_types` v ON v.slug = a.vehicle_type
--       LEFT JOIN `special_types`  s ON s.slug = a.vehicle_type
--      WHERE a.vehicle_type NOT IN ('', 'shelter_container')
--        AND v.slug IS NULL AND s.slug IS NULL;

-- Fine patch.
