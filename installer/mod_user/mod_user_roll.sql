CREATE TABLE `mod_user_roll` (
  `roll_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_id` int(11) NOT NULL,
  `roll_name` varchar(50) NOT NULL,
  PRIMARY KEY (`roll_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
