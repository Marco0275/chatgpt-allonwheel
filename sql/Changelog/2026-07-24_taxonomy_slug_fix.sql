-- ============================================================
-- 2026-07-24_taxonomy_slug_fix.sql
-- Allineamento slug fra tassonomia annunci e tassonomia fornitori
--
-- IL PROBLEMA
-- vehicle_types.slug e' la stessa chiave di 06_company_products.product_key
-- (lo dichiara libs/06_company.class.php riga 576): serve a mettere in
-- contatto un annuncio con i fornitori che producono quella tipologia.
-- Confrontando le due liste sono emersi slug che NON combaciano, quindi per
-- quelle tipologie il ponte annuncio -> fornitori non aggancia nessuno:
--
--   vehicle_types        CompanyManager::$products(_special)
--   -----------------    ----------------------------------
--   raicing_trailer      racing_trailer      <-- refuso di battitura
--   box_trailers         box_trailer         <-- plurale vs singolare
--
-- Questa patch corregge i due slug NEL DATABASE e propaga la correzione agli
-- annunci gia' pubblicati che li referenziano, cosi' nessun annuncio resta
-- orfano di tipologia.
--
-- GARANZIE
--  - Non distruttiva: solo UPDATE mirati su valori sbagliati.
--  - Idempotente: rieseguendola non trova piu' nulla da correggere.
--  - MySQL 5.7 / MariaDB compatibile.
--
-- RESTANO DA DECIDERE (non tocco: sono scelte di prodotto, non refusi)
--   roadshow_vehicles   e' in vehicle_types ma non fra i prodotti fornitore:
--                       nessun fornitore potra' mai essere associato.
--   street_food         in vehicle_types e' 'special', mentre il prodotto
--                       fornitore corrispondente (autonegozi_alimentari) e'
--                       fra i road: un annuncio street food e un fornitore
--                       di autonegozi non si incontrano.
--   motorhomes_mobilhomes  prodotto fornitore senza tipologia annuncio
--                       (in vehicle_types il corrispondente e' 'camper').
--   special_shelter     prodotto fornitore, mentre la tipologia annuncio si
--                       chiama 'shelter_container'.
--   Dimmi come vuoi allinearle e preparo la patch: vanno decise, non indovinate.
-- ============================================================

-- ---- 1) refuso: raicing_trailer -> racing_trailer ----------
UPDATE `vehicle_types` SET `slug` = 'racing_trailer'
 WHERE `slug` = 'raicing_trailer';

UPDATE `02_free_ads` SET `vehicle_type` = 'racing_trailer'
 WHERE `vehicle_type` = 'raicing_trailer';

UPDATE `03_ads` SET `vehicle_type` = 'racing_trailer'
 WHERE `vehicle_type` = 'raicing_trailer';

UPDATE `wanted_ads` SET `vehicle_type` = 'racing_trailer'
 WHERE `vehicle_type` = 'raicing_trailer';

-- ---- 2) plurale: box_trailers -> box_trailer ---------------
UPDATE `vehicle_types` SET `slug` = 'box_trailer'
 WHERE `slug` = 'box_trailers';

UPDATE `02_free_ads` SET `vehicle_type` = 'box_trailer'
 WHERE `vehicle_type` = 'box_trailers';

UPDATE `03_ads` SET `vehicle_type` = 'box_trailer'
 WHERE `vehicle_type` = 'box_trailers';

UPDATE `wanted_ads` SET `vehicle_type` = 'box_trailer'
 WHERE `vehicle_type` = 'box_trailers';

-- ---- VERIFICA (facoltativa) ---------------------------------
--   Devono dare 0 righe:
--     SELECT * FROM `vehicle_types` WHERE slug IN ('raicing_trailer','box_trailers');
--     SELECT id_ads, vehicle_type FROM `02_free_ads`
--       WHERE vehicle_type IN ('raicing_trailer','box_trailers');
--   Tipologie senza fornitore possibile (le decisioni aperte qui sopra):
--     SELECT slug, macro_category FROM `vehicle_types` ORDER BY macro_category, slug;

-- Fine patch.
