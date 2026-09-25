CREATE TABLE `mod_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `url` varchar(250) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mod_name` varchar(250) NOT NULL,
  `vid` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_gal_mod_vid` (`mod_name`, `vid`),
  INDEX `idx_gal_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
