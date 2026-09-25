CREATE TABLE `mod_mcp_oauth_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_hash` varchar(64) NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `redirect_uri` text NOT NULL,
  `code_challenge` varchar(64) NOT NULL,
  `resource` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_hash` (`code_hash`),
  INDEX `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
