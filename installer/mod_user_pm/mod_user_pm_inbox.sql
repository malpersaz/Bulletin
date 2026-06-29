CREATE TABLE `mod_user_pm_inbox` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('y','n') NOT NULL DEFAULT 'n',
  `subject` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reader_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
