CREATE TABLE `mod_mcp_oauth_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(64) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `redirect_uris` text NOT NULL,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
