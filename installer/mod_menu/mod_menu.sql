CREATE TABLE `mod_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `target` enum('y','n') NOT NULL DEFAULT 'n',
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `html` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `acl_id` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_menu_lang` (`lang`),
  INDEX `idx_menu_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
