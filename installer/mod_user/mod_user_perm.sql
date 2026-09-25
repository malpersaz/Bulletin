CREATE TABLE `mod_user_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `mod_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_perm_group` (`group_id`),
  INDEX `idx_perm_mod` (`mod_id`),
  INDEX `idx_perm_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
