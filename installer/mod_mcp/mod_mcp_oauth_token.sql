CREATE TABLE `mod_mcp_oauth_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `access_token_hash` varchar(64) NOT NULL,
  `refresh_token_hash` varchar(64) NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `resource` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_token_hash` (`access_token_hash`),
  UNIQUE KEY `refresh_token_hash` (`refresh_token_hash`),
  INDEX `idx_client` (`client_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
