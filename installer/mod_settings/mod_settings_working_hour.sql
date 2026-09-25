CREATE TABLE IF NOT EXISTS `mod_settings_working_hour` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day_of_week` tinyint(1) NOT NULL,
  `is_closed` enum('y','n') NOT NULL DEFAULT 'n',
  `open_time` time NOT NULL DEFAULT '09:00:00',
  `close_time` time NOT NULL DEFAULT '18:00:00',
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_working_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
