-- Modüller Tablosu
CREATE TABLE `mods` (
  `mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_name` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`mod_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Görüntüleme Logları Tablosu (IP Bazlı)
CREATE TABLE `sys_view_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `item_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`table_name`,`item_id`,`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;