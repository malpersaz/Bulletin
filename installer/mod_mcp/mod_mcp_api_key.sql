CREATE TABLE `mod_mcp_api_key` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `api_key_hash` varchar(64) NOT NULL,
  `key_prefix` varchar(16) NOT NULL,
  `group_id` int(11) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key_hash` (`api_key_hash`),
  INDEX `idx_group` (`group_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
