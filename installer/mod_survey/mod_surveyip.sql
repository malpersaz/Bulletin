CREATE TABLE IF NOT EXISTS `mod_surveyip` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `survey_id` int(8) NOT NULL,
  `ip` varchar(15) NOT NULL
)   DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;