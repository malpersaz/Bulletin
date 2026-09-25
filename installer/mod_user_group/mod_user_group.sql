CREATE TABLE `mod_user_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `roles` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
