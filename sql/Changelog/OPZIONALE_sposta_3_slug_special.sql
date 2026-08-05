-- =====================================================================
-- OPZIONALE — allineamento dati fornitori (RIVEDERE prima di eseguire)
-- =====================================================================
-- Contesto: nel vecchio catalogo statico CompanyManager::$products (asse
-- "regular"/road -> 06_company_products) erano finiti 3 slug che con la
-- nuova tassonomia sono SPECIAL (stanno in special_types):
--     camper, laboratori_medici_mobili, uffici_mobili
--
-- L'RFQ (04_request_offer) li mostra ora nella sezione SPECIAL, quindi un
-- buyer li richiede via product_special[] -> match su 06_company_products_special.
-- I fornitori che li avevano dichiarati come "regular" (06_company_products)
-- NON verrebbero intercettati da quelle richieste finche' non si spostano.
--
-- Questo script sposta le dichiarazioni esistenti sull'asse special,
-- preservando la nota. Idempotente (NOT EXISTS + DELETE mirato).
-- FARE BACKUP delle due tabelle prima di eseguire.
-- =====================================================================

INSERT INTO `06_company_products_special` (company_id, product_key, note)
SELECT p.company_id, p.product_key, p.note
FROM `06_company_products` p
WHERE p.product_key IN ('camper','laboratori_medici_mobili','uffici_mobili')
  AND NOT EXISTS (
      SELECT 1 FROM `06_company_products_special` s
      WHERE s.company_id = p.company_id
        AND s.product_key = p.product_key
  );

DELETE FROM `06_company_products`
WHERE product_key IN ('camper','laboratori_medici_mobili','uffici_mobili');

-- Verifica (attesi 0 nella prima query dopo l'esecuzione):
-- SELECT COUNT(*) FROM `06_company_products`
--   WHERE product_key IN ('camper','laboratori_medici_mobili','uffici_mobili');
