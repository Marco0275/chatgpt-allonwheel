-- ============================================================
-- 2026-07-16_shelter_macro_align.sql
-- Allineamento tassonomia: Shelter & Container
--
-- CONTESTO
-- shelter_container.php filtrava per `item_kind = 'shelter_container'`,
-- mentre le altre 4 famiglie filtrano per `product_macro`. Dal 16 lug 2026
-- usa `product_macro = 'shelter-container'` come tutte le altre.
--
-- PERCHE' QUESTA PATCH
-- ProductMacro::forAd() e il backfill di product_macros.sql mappano gia'
-- item_kind='shelter_container' -> product_macro='shelter-container'.
-- Questa patch e' la RETE DI SICUREZZA per eventuali annunci antecedenti
-- alla migrazione rimasti con product_macro NULL: senza, sparirebbero
-- dalla pagina Shelter & Container.
--
-- GARANZIE
--  - NON distruttiva (dir. 9): non cancella e non sovrascrive nulla.
--  - Agisce SOLO dove product_macro IS NULL, quindi rispetta le macro
--    impostate a mano da _admin/edit_ad.php (stessa filosofia del backfill
--    esistente).
--  - Idempotente: ri-eseguibile all'infinito senza effetti (dopo il primo
--    giro non trova piu' righe NULL da correggere).
--  - MySQL 5.7 compatibile (nessun costrutto 8.0+).
--
-- PREREQUISITO: sql/Changelog/product_macros.sql gia' applicata
-- (crea la colonna product_macro). Se non lo e', applica prima quella.
-- ============================================================

-- ---- 1) VERIFICA PRIMA (facoltativa ma consigliata) --------
-- Quanti annunci Shelter rischiano di non comparire?
-- Se torna 0, la patch non ha nulla da fare: sei gia' allineato.
--
--   SELECT '02_free_ads' AS tabella, COUNT(*) AS da_allineare
--     FROM `02_free_ads`
--    WHERE `item_kind` = 'shelter_container' AND `product_macro` IS NULL
--   UNION ALL
--   SELECT '03_ads', COUNT(*)
--     FROM `03_ads`
--    WHERE `item_kind` = 'shelter_container' AND `product_macro` IS NULL;

-- ---- 2) ALLINEAMENTO -----------------------------------------
UPDATE `02_free_ads`
   SET `product_macro` = 'shelter-container'
 WHERE `item_kind` = 'shelter_container'
   AND `product_macro` IS NULL;

UPDATE `03_ads`
   SET `product_macro` = 'shelter-container'
 WHERE `item_kind` = 'shelter_container'
   AND `product_macro` IS NULL;

-- ---- 3) CONTROLLO DOPO (facoltativo) -------------------------
-- Segnala le righe dove item_kind dice "shelter" ma la macro dice altro:
-- NON sono errori da correggere in automatico (potrebbero essere scelte
-- deliberate fatte in admin), ma vale la pena guardarle.
--
--   SELECT '02_free_ads' AS tabella, `id_ads`, `title`, `product_macro`
--     FROM `02_free_ads`
--    WHERE `item_kind` = 'shelter_container'
--      AND `product_macro` <> 'shelter-container'
--   UNION ALL
--   SELECT '03_ads', `id_ads`, `title`, `product_macro`
--     FROM `03_ads`
--    WHERE `item_kind` = 'shelter_container'
--      AND `product_macro` <> 'shelter-container';

-- Fine patch.
