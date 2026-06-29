CREATE TABLE `mod_user_pm_sent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reader_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
