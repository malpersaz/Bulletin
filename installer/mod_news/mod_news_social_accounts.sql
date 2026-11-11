CREATE TABLE IF NOT EXISTS `mod_news_social_accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(32) NOT NULL,
  `lang_scope` varchar(12) NOT NULL DEFAULT '*',
  `account_label` varchar(120) NOT NULL,
  `external_account_id` varchar(128) DEFAULT NULL,
  `credentials_cipher` text NOT NULL,
  `post_mode` varchar(32) NOT NULL DEFAULT 'link_card',
  `auto_share_default` enum('y','n') NOT NULL DEFAULT 'y',
  `daily_limit` int(11) unsigned NOT NULL DEFAULT 50,
  `is_active` enum('y','n') NOT NULL DEFAULT 'n',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_platform_active` (`platform`, `is_active`),
  INDEX `idx_lang_scope` (`lang_scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
