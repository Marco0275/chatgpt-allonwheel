-- ============================================================
-- profile_image.sql — Immagine del profilo utente.
-- Aggiunge la colonna `profile_image` alla tabella `users`.
--   Contiene il filename salvato in /upload_image/profile/
--   (sottocartelle original/ + thumbnail/, generate da UploadHelper).
--   NULL = nessuna immagine caricata -> in UI si usa images/avator.jpg.
-- Idempotente: eseguire una sola volta. Nessuna perdita dati (dir. 9).
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN `profile_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
  COMMENT 'Filename in /upload_image/profile/; NULL = avatar di default'
  AFTER `phone`;
