CREATE TABLE IF NOT EXISTS `mod_surveyselect` (
  `id` int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `survey_id` int(8) NOT NULL DEFAULT '0',
  `option_text` varchar(255) NOT NULL,
  `hit` int(11) NOT NULL DEFAULT '0'
)   DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;