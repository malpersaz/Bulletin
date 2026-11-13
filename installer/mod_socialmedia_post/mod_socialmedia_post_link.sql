CREATE TABLE IF NOT EXISTS `mod_socialmedia_post_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `socialmedia_post_id` int(11) NOT NULL,
  `mod_name` varchar(64) NOT NULL,
  `vid` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_post_link` (`socialmedia_post_id`, `mod_name`, `vid`),
  KEY `idx_social_post_host` (`mod_name`, `vid`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
