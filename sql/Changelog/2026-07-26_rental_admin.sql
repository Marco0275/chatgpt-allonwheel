-- Allinea 07_rent_requests.status al lifecycle delle RFQ (new/distributed/quoted/won/lost).
-- Idempotente e non distruttiva (rimappa i vecchi valori active/closed).
ALTER TABLE `07_rent_requests` MODIFY COLUMN `status`
  ENUM('active','closed','new','distributed','quoted','won','lost') NOT NULL DEFAULT 'new';
UPDATE `07_rent_requests` SET `status`='new'  WHERE `status`='active';
UPDATE `07_rent_requests` SET `status`='lost' WHERE `status`='closed';
ALTER TABLE `07_rent_requests` MODIFY COLUMN `status`
  ENUM('new','distributed','quoted','won','lost') NOT NULL DEFAULT 'new';
