CREATE TABLE `mod_newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `lang` varchar(10) NOT NULL DEFAULT 'en',
  `source` varchar(50) DEFAULT 'footer',
  `status` enum('active','unsubscribed') NOT NULL DEFAULT 'active',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_newsletter_email` (`email`),
  INDEX `idx_newsletter_lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
