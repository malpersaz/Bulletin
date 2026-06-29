CREATE TABLE `mod_user_password_reset` (
  `email` varchar(191) NOT NULL,
  `code` varchar(250) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
