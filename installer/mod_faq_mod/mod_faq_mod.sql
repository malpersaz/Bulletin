CREATE TABLE IF NOT EXISTS `mod_faq_mod` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `mod_name` VARCHAR(255) NOT NULL,
  `mod_key` VARCHAR(255) NOT NULL DEFAULT 'id',
  `mod_value` VARCHAR(255) NOT NULL DEFAULT 'name',
  `user_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_faq_mod_name` (`mod_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
