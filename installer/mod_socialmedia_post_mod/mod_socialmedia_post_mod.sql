CREATE TABLE IF NOT EXISTS `mod_socialmedia_post_mod` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mod_name` varchar(255) NOT NULL,
  `mod_key` varchar(255) NOT NULL,
  `mod_value` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_post_mod_name` (`mod_name`),
  KEY `idx_social_post_mod_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
