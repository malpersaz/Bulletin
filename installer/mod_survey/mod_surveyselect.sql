CREATE TABLE IF NOT EXISTS `mod_surveyselect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL DEFAULT 0,
  `option_text` varchar(255) NOT NULL,
  `hit` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_surveyopt_survey` (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
