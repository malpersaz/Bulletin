CREATE TABLE `mod_redirect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_url` varchar(250) NOT NULL UNIQUE,
  `new_url` varchar(250) NOT NULL,
  `status_code` int(11) NOT NULL DEFAULT 301,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
