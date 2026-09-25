CREATE TABLE IF NOT EXISTS `mod_settings_social` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` enum('y','n') NOT NULL DEFAULT 'y',
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_soc_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
