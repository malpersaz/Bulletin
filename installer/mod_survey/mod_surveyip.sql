CREATE TABLE IF NOT EXISTS `mod_surveyip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_surveyip_survey` (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
