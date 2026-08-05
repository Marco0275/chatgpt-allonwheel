-- ============================================================
-- 2026-07-24b_taxonomy_merge.sql
-- Unificazione degli slug doppi (decisioni di Marco, 24 lug 2026)
--
-- Prosegue 2026-07-24_taxonomy_slug_fix.sql (che correggeva i due refusi
-- raicing_trailer e box_trailers). Qui si chiudono i disallineamenti
-- rimasti, secondo le indicazioni ricevute:
--
--   roadshow_vehicles      ->  hospitality_units
--   street_food            ->  autonegozi_alimentari   (uno solo, non due)
--   motorhomes_mobilhomes  ->  camper                  (motorhome e mobilhome
--                                                        sono camper)
--   special_shelter        ->  shelter_container       (vedi nota in fondo)
--
-- PERCHE' SERVE
-- vehicle_types.slug e 06_company_products(.._special).product_key sono la
-- STESSA chiave: e' il ponte fra un annuncio e i fornitori che producono
-- quella tipologia. Due nomi diversi per la stessa cosa = nessun fornitore
-- trovato. Inoltre "Street food" compariva DUE volte nelle tendine, una
-- volta come road e una come special.
--
-- GARANZIE
--  - Non distruttiva sui DATI: prima si ripuntano annunci, wanted e
--    fornitori sullo slug che resta, poi si elimina la riga doppia dalla
--    tabella delle tipologie. Nessun record perde la sua classificazione.
--  - Idempotente: rieseguendola non trova piu' nulla da spostare.
--  - MySQL 5.7 / MariaDB compatibile.
-- ============================================================

-- =====================================================================
-- 1) roadshow_vehicles -> hospitality_units
--    Entrambe sono gia' in vehicle_types con macro 'special': si ripuntano
--    i riferimenti e si elimina la riga in piu'.
-- =====================================================================
UPDATE `02_free_ads` SET `vehicle_type` = 'hospitality_units'
 WHERE `vehicle_type` = 'roadshow_vehicles';
UPDATE `03_ads`      SET `vehicle_type` = 'hospitality_units'
 WHERE `vehicle_type` = 'roadshow_vehicles';
UPDATE `wanted_ads`  SET `vehicle_type` = 'hospitality_units'
 WHERE `vehicle_type` = 'roadshow_vehicles';

DELETE FROM `vehicle_types` WHERE `slug` = 'roadshow_vehicles';

-- =====================================================================
-- 2) street_food -> autonegozi_alimentari
--    ATTENZIONE: cambia anche la MACRO. street_food era 'special',
--    autonegozi_alimentari e' 'road': gli annunci che passano allo slug
--    che resta devono passare anche alla macro giusta, altrimenti
--    resterebbero classificati speciali con una tipologia stradale.
--    Le due voci avevano perfino la stessa etichetta "Street food": e' il
--    doppione che si vedeva nelle tendine.
-- =====================================================================
UPDATE `02_free_ads`
   SET `vehicle_type` = 'autonegozi_alimentari', `macro_category` = 'road'
 WHERE `vehicle_type` = 'street_food';
UPDATE `03_ads`
   SET `vehicle_type` = 'autonegozi_alimentari', `macro_category` = 'road'
 WHERE `vehicle_type` = 'street_food';
UPDATE `wanted_ads` SET `vehicle_type` = 'autonegozi_alimentari'
 WHERE `vehicle_type` = 'street_food';

DELETE FROM `vehicle_types` WHERE `slug` = 'street_food';

-- =====================================================================
-- 3) motorhomes_mobilhomes -> camper
--    'camper' e' gia' una tipologia (vehicle_types, macro 'special') ed e'
--    fra i prodotti REGOLARI del fornitore, mentre motorhomes_mobilhomes
--    stava fra i prodotti SPECIALI: i fornitori vanno spostati di tabella,
--    non solo rinominati.
--    L'INSERT ... SELECT evita di creare doppioni per chi avesse gia'
--    dichiarato 'camper'.
-- =====================================================================
-- NOTA IMPORTANTE (verificata importando il dump reale ed eseguendo la query):
-- `06_company_products` ha una FOREIGN KEY verso `06_company`
-- (fk_06prod_company su company_id), mentre `06_company_products_special`
-- NON ce l'ha. Per questo la tabella speciali ha accumulato righe ORFANE:
-- prodotti dichiarati da aziende che non esistono piu'. Spostandole cosi'
-- com'erano si otteneva:
--     ERROR 1452: Cannot add or update a child row: a foreign key
--                 constraint fails (fk_06prod_company)
-- Quindi si spostano SOLO le righe di aziende realmente esistenti. Le
-- orfane restano dove sono e non vengono toccate: sono gia' invisibili al
-- sito (ogni pagina fa JOIN sull'azienda) e cancellarle e' una tua scelta,
-- non una decisione da prendere dentro una migrazione. In fondo al file
-- trovi la query pronta, commentata, se vuoi ripulirle.
--
-- Le altre colonne: `note` viene portata dietro, `id` e' AUTO_INCREMENT e si
-- lascia generare. Non esiste un UNIQUE su (company_id, product_key), quindi
-- la guardia NOT EXISTS serve davvero.
INSERT INTO `06_company_products` (`company_id`, `product_key`, `note`)
SELECT s.`company_id`, 'camper', s.`note`
  FROM `06_company_products_special` s
 WHERE s.`product_key` = 'motorhomes_mobilhomes'
   AND EXISTS (
       SELECT 1 FROM `06_company` c WHERE c.`id` = s.`company_id`
   )
   AND NOT EXISTS (
       SELECT 1 FROM `06_company_products` p
        WHERE p.`company_id` = s.`company_id` AND p.`product_key` = 'camper'
   );

-- Si eliminano dalla tabella speciali SOLO le righe effettivamente spostate,
-- cioe' quelle di aziende esistenti. Le orfane restano intatte.
DELETE s FROM `06_company_products_special` s
 WHERE s.`product_key` = 'motorhomes_mobilhomes'
   AND EXISTS (SELECT 1 FROM `06_company` c WHERE c.`id` = s.`company_id`);

UPDATE `02_free_ads` SET `vehicle_type` = 'camper'
 WHERE `vehicle_type` = 'motorhomes_mobilhomes';
UPDATE `03_ads`      SET `vehicle_type` = 'camper'
 WHERE `vehicle_type` = 'motorhomes_mobilhomes';
UPDATE `wanted_ads`  SET `vehicle_type` = 'camper'
 WHERE `vehicle_type` = 'motorhomes_mobilhomes';

-- =====================================================================
-- 4) special_shelter -> shelter_container
--    NOTA: questo non me l'hai indicato esplicitamente, lo allineo perche'
--    e' l'unica opzione tecnicamente coerente e senza di essa il ponte
--    resta rotto. Il lato annuncio NON e' modificabile a mano: lo shelter
--    scrive vehicle_type = 'shelter_container' perche' e' una costante del
--    codice (VehicleTaxonomy::SHELTER_SLUG), usata in tutto il sito. Quindi
--    si allinea la chiave del fornitore a quella costante.
--    Se preferisci il contrario (costante -> 'special_shelter') dimmelo e
--    preparo la patch inversa: sono 6 righe fornitore o una costante.
-- =====================================================================
-- Anche qui la colonna e' `company_id`. Si rinomina solo per le aziende che
-- non hanno gia' shelter_container, poi si eliminano le eventuali righe
-- residue: cosi' nessuna azienda si ritrova la stessa voce due volte.
UPDATE `06_company_products_special`
   SET `product_key` = 'shelter_container'
 WHERE `product_key` = 'special_shelter'
   AND `company_id` NOT IN (
       SELECT * FROM (
           SELECT `company_id` FROM `06_company_products_special`
            WHERE `product_key` = 'shelter_container'
       ) AS gia_presente
   );

-- Qui non c'e' vincolo di chiave esterna (la tabella speciali non ne ha),
-- quindi la rinomina vale anche per le eventuali righe orfane: restano
-- coerenti con la nuova tassonomia invece di puntare a uno slug sparito.
DELETE FROM `06_company_products_special` WHERE `product_key` = 'special_shelter';

-- =====================================================================
-- 5) Refuso nel NOME VISIBILE della tipologia
--    Lo slug era gia' stato corretto (racing_trailer), ma l'etichetta che
--    l'utente legge nelle tendine diceva ancora "Raicing trailer".
--    Verificato sul dump reale importato.
-- =====================================================================
UPDATE `vehicle_types` SET `name` = 'Racing trailer'
 WHERE `slug` = 'racing_trailer' AND `name` <> 'Racing trailer';

-- ---- VERIFICA (facoltativa) ---------------------------------
--   Devono dare 0 righe:
--     SELECT * FROM `vehicle_types`
--      WHERE slug IN ('roadshow_vehicles','street_food');
--     SELECT * FROM `06_company_products_special`
--      WHERE product_key IN ('motorhomes_mobilhomes','special_shelter');
--   "Street food" deve comparire UNA volta sola:
--     SELECT slug, name, macro_category FROM `vehicle_types`
--      WHERE name LIKE '%Street food%';
--   Nessun annuncio deve puntare a uno slug sparito:
--     SELECT DISTINCT a.vehicle_type FROM `02_free_ads` a
--      LEFT JOIN `vehicle_types` t ON t.slug = a.vehicle_type
--      WHERE a.vehicle_type <> '' AND a.vehicle_type <> 'shelter_container'
--        AND t.slug IS NULL;

-- Fine patch.

-- =====================================================================
-- APPENDICE (facoltativa, NON eseguita): righe orfane
-- Nel dump che mi hai passato, 06_company e' vuota e le 36 righe di
-- 06_company_products_special puntano ad aziende (id 2..7) che non
-- esistono: sono residui di aziende cancellate. Il sito non le mostra
-- comunque, perche' ogni query fa JOIN sull'azienda.
-- Se vuoi ripulirle, questa e' la query - decidi tu:
--
--   DELETE s FROM `06_company_products_special` s
--    WHERE NOT EXISTS (SELECT 1 FROM `06_company` c WHERE c.id = s.company_id);
--
-- Per vederle prima di cancellare:
--   SELECT s.company_id, s.product_key, COUNT(*)
--     FROM `06_company_products_special` s
--     LEFT JOIN `06_company` c ON c.id = s.company_id
--    WHERE c.id IS NULL GROUP BY s.company_id, s.product_key;
-- =====================================================================
