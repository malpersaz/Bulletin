CREATE TABLE IF NOT EXISTS `mod_faq` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lang` VARCHAR(10) NOT NULL DEFAULT 'en',
  `mod_name` VARCHAR(100) NOT NULL,
  `vid` INT(11) NOT NULL,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('y', 'n') NOT NULL DEFAULT 'y',
  `user_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_faq_mod_vid` (`mod_name`, `vid`),
  INDEX `idx_faq_lang` (`lang`),
  INDEX `idx_faq_status` (`status`),
  INDEX `idx_faq_order` (`sort_order`),
  INDEX `idx_faq_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
