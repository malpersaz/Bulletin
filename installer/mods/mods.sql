-- Modüller Tablosu
CREATE TABLE `mods` (
  `mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_name` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`mod_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;