-- ============================================================
-- macro_hero_image.sql - TEMPLATE da compilare con i TUOI path immagine.
-- Popola product_macros.hero_image per le 5 famiglie. browse.php mostra
-- l'immagine in cima al box intro quando hero_image e' valorizzato
-- (se vuoto, non mostra nulla: nessuna regressione).
--
-- COSA DEVI FARE (lato tuo, NON da codice - dir. 15):
-- 1) Carica MANUALMENTE 5 immagini (consigliato ~1200x500 px, JPG/WebP)
--    in una cartella servita dal sito, es. images/macros/ .
--    Il codice non crea/cancella nulla in images/: le metti tu via FTP/pannello.
-- 2) Sostituisci i path qui sotto con quelli reali e lancia questo file sul DB.
-- 3) Verifica su /browse.php?macro=race-trailer (ecc.).
--
-- Idempotente (solo UPDATE per slug), MySQL 5.7. Nessun dato distrutto.
-- ============================================================

UPDATE `product_macros` SET `hero_image` = '/images/macros/race-trailer.jpg'     WHERE `slug` = 'race-trailer';
UPDATE `product_macros` SET `hero_image` = '/images/macros/hospitality.jpg'      WHERE `slug` = 'hospitality';
UPDATE `product_macros` SET `hero_image` = '/images/macros/mobile-clinic.jpg'    WHERE `slug` = 'mobile-clinic';
UPDATE `product_macros` SET `hero_image` = '/images/macros/shelter-container.jpg' WHERE `slug` = 'shelter-container';
UPDATE `product_macros` SET `hero_image` = '/images/macros/custom-projects.jpg'  WHERE `slug` = 'custom-projects';
