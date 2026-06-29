CREATE TABLE `mod_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(250) NOT NULL,
  `ext` varchar(250) NOT NULL,
  `original_name` varchar(250) NOT NULL,
  `type` varchar(250) NOT NULL,
  `url` varchar(250) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mod_name` varchar(250) NOT NULL,
  `vid` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `view_count` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
