CREATE TABLE `mod_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `deck` enum('y','n') NOT NULL DEFAULT 'n',
  `summary` text NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery_id` int(11) NOT NULL DEFAULT 0,
  `file_id` int(11) NOT NULL DEFAULT 0,
  `acl_id` int(11) DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_ann_lang` (`lang`),
  INDEX `idx_ann_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
