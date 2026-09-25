CREATE TABLE IF NOT EXISTS `mod_newsletter` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `lang` VARCHAR(10) NOT NULL DEFAULT 'ku',
  `source` VARCHAR(50) NOT NULL DEFAULT 'offline_launch',
  `status` ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
