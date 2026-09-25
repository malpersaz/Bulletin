CREATE TABLE IF NOT EXISTS `mod_settings_closure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_full_day` enum('y','n') NOT NULL DEFAULT 'y',
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `closure_type` enum('holiday','vacation','maintenance','emergency') NOT NULL DEFAULT 'holiday',
  `is_recurring_yearly` enum('y','n') NOT NULL DEFAULT 'n',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_closure_range` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
