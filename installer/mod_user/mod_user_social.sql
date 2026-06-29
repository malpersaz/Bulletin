CREATE TABLE IF NOT EXISTS `mod_user_social` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) NOT NULL,
  `platform` VARCHAR(50) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `icon_class` VARCHAR(100) DEFAULT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` ENUM('y', 'n') NOT NULL DEFAULT 'y',
  UNIQUE KEY `uniq_user_platform` (`user_id`, `platform`),
  INDEX `idx_user_social_user` (`user_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
