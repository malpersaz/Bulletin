-- Üye Grupları
CREATE TABLE `mod_user_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `order` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `rolls` text COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 3 Adet Üye Groubu Ekleyelim
-- INSERT INTO `mod_user_group` VALUES(1, 'Site Yöneticisi', 1, 0, '', 1);
-- INSERT INTO `mod_user_group` VALUES(2, 'Normal Üye', 2, 1, '', 1);
-- INSERT INTO `mod_user_group` VALUES(3, 'Editörler', 3, 1, '', 1);