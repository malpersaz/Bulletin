CREATE TABLE IF NOT EXISTS `mod_settings_cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(10) NOT NULL,
  `cookie_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'necessary',
  `provider` varchar(150) NOT NULL,
  `purpose` text NOT NULL,
  `duration` varchar(100) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` enum('y','n') NOT NULL DEFAULT 'y',
  `user_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_cookie_lang` (`lang`),
  INDEX `idx_cookie_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
