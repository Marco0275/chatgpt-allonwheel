-- ============================================================
-- 2026-06-17_macro_hero_images.sql
-- B5: le hero delle macro non si vedevano perche' product_macros.hero_image
-- puntava a /images/macros/<slug>.jpg ma i file caricati sono IMG_15xx.JPG.
-- Riallineo hero_image ai nomi reali dei file in images/macros/.
-- MySQL 5.7 compatibile. Idempotente (UPDATE per slug).
--
-- ATTENZIONE all'abbinamento: i nomi file sono generici (IMG_15xx), quindi
-- ho usato l'ordine macro (sort_order) come default. Se una foto non
-- corrisponde alla famiglia giusta, scambia il nome file nelle righe sotto.
-- Su Linux i nomi sono CASE-SENSITIVE: tieni l'estensione .JPG maiuscola.
-- ============================================================

UPDATE `product_macros` SET `hero_image` = '/images/macros/IMG_1505.JPG' WHERE `slug` = 'race-trailer';
UPDATE `product_macros` SET `hero_image` = '/images/macros/IMG_1516.JPG' WHERE `slug` = 'hospitality';
UPDATE `product_macros` SET `hero_image` = '/images/macros/IMG_1517.JPG' WHERE `slug` = 'mobile-clinic';
UPDATE `product_macros` SET `hero_image` = '/images/macros/IMG_1518.JPG' WHERE `slug` = 'shelter-container';
UPDATE `product_macros` SET `hero_image` = '/images/macros/IMG_1519.JPG' WHERE `slug` = 'custom-projects';
