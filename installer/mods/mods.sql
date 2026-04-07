-- Module registry table.
CREATE TABLE `mods` (
  `mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_name` text COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'installed',
  `schema_ready` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  `installed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`mod_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
