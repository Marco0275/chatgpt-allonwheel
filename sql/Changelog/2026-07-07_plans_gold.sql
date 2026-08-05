-- 2026-07-07: piani di pubblicazione dalla landing (Basic / Silver / Gold).
-- Basic  = tier 'free'    -> max 2 annunci totali
-- Silver = tier 'premium' -> max 15 annunci totali
-- Gold   = tier 'gold'    -> annunci illimitati e prioritari, fisso in cima alla directory
-- Run-once, MySQL 5.7.
ALTER TABLE `users`
  MODIFY COLUMN `user_tier` ENUM('free','premium','gold','admin')
  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free'
  COMMENT 'free=Basic(2 ads) / premium=Silver(15 ads) / gold=Gold(unlimited+priority) / admin';
