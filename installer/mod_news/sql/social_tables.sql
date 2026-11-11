-- Amed Framework: mod_news automated social sharing schema
-- Tables: mod_news_social_accounts, mod_news_social_prefs, mod_news_social_jobs

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

CREATE TABLE IF NOT EXISTS `mod_news_social_prefs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `news_id` int(11) unsigned NOT NULL,
  `channels_json` text NOT NULL,
  `custom_caption` varchar(500) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_id` (`news_id`),
  INDEX `idx_news_id` (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mod_news_social_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `news_id` int(11) unsigned NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `platform` varchar(32) NOT NULL,
  `idempotency_key` varchar(160) NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `scheduled_at` datetime NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `next_retry_at` datetime DEFAULT NULL,
  `external_container_id` varchar(128) DEFAULT NULL,
  `external_post_id` varchar(128) DEFAULT NULL,
  `external_post_url` varchar(500) DEFAULT NULL,
  `story_image_path` varchar(255) DEFAULT NULL,
  `worker_id` varchar(64) DEFAULT NULL,
  `heartbeat_at` datetime DEFAULT NULL,
  `last_error_code` varchar(64) DEFAULT NULL,
  `last_error_message` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency_key` (`idempotency_key`),
  INDEX `idx_news_id` (`news_id`),
  INDEX `idx_account_id` (`account_id`),
  INDEX `idx_status_scheduled` (`status`, `scheduled_at`),
  INDEX `idx_next_retry` (`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
