CREATE TABLE IF NOT EXISTS `mod_settings_social` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `platform` VARCHAR(50) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `icon_class` VARCHAR(100) DEFAULT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` ENUM('y', 'n') NOT NULL DEFAULT 'y',
  `user_id` INT(11) NOT NULL,
  INDEX `idx_platform` (`platform`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
