-- ============================================================
-- Alter users table - Akreditasi App (IDEMPOTENT VERSION)
-- Aman dijalankan berkali-kali — hanya menambah kolom jika belum ada.
-- Jalankan per-blok jika ada error, atau jalankan semua sekaligus.
-- ============================================================

-- 1. cas_username
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cas_username');
SET @sql = IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `cas_username` VARCHAR(191) NULL UNIQUE AFTER `id`',
    'SELECT "cas_username already exists, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. koha_patron_id
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'koha_patron_id');
SET @sql = IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `koha_patron_id` INT NULL AFTER `cas_username`',
    'SELECT "koha_patron_id already exists, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Buat email nullable
ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(191) NULL;

-- 4. categorycode
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'categorycode');
SET @sql = IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `categorycode` VARCHAR(50) NULL AFTER `email`',
    'SELECT "categorycode already exists, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. role
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @sql = IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `role` ENUM(''librarian'', ''patron'') NOT NULL DEFAULT ''patron'' AFTER `categorycode`',
    'SELECT "role already exists, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. cardnumber
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cardnumber');
SET @sql = IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `cardnumber` VARCHAR(191) NULL AFTER `role`',
    'SELECT "cardnumber already exists, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. Drop kolom username lama (hanya jika masih ada)
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username');
SET @sql = IF(@col > 0,
    'ALTER TABLE `users` DROP COLUMN `username`',
    'SELECT "username already dropped, skipping"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verifikasi struktur akhir:
DESCRIBE `users`;
