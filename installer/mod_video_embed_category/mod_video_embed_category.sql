CREATE TABLE `mod_video_embed_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `root` enum('y','n') NOT NULL DEFAULT 'n',
  `seo` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_vecat_lang_seo` (`lang`, `seo`),
  INDEX `idx_vecat_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
