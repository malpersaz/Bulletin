CREATE TABLE `mod_popup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `publish_at` timestamp NOT NULL,
  `expired_at` timestamp NOT NULL,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `content` text DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `video` enum('url','youtube','vimeo','instagram') NOT NULL DEFAULT 'url',
  `link` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_popup_lang` (`lang`),
  INDEX `idx_popup_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
