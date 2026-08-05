-- ============================================================
-- quote_request_consent.sql — Consenso GDPR sul lead RFQ.
-- Il consenso alla condivisione del contatto con i fornitori vive sul
-- lead stesso (legato all'acquirente), non nel consent_log cookie
-- (che e' anonimo e specifico per i cookie). Evidenza minima e robusta:
-- flag + versione del testo + hash IP (no IP in chiaro) + user agent + data.
--
-- Dipendenza: eseguire DOPO quote_requests.sql.
-- Target DB: MySQL 5.7 (no ADD COLUMN IF NOT EXISTS) -> eseguire UNA VOLTA.
-- ============================================================

ALTER TABLE `quote_requests`
  ADD COLUMN `consent_given` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Consenso esplicito alla condivisione del contatto con i fornitori'
    AFTER `message`,
  ADD COLUMN `consent_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
    COMMENT 'Versione del testo di consenso mostrato'
    AFTER `consent_given`,
  ADD COLUMN `consent_ip_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
    COMMENT 'SHA-256 dell IP (no IP in chiaro)'
    AFTER `consent_version`,
  ADD COLUMN `consent_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
    AFTER `consent_ip_hash`,
  ADD COLUMN `consent_at` datetime DEFAULT NULL
    COMMENT 'Timestamp del consenso'
    AFTER `consent_user_agent`;

-- Rimuove il placeholder mai utilizzato: il consenso ora vive sulle colonne
-- qui sopra. (Se preferisci conservarlo, salta questa singola istruzione.)
ALTER TABLE `quote_requests` DROP COLUMN `consent_id`;
