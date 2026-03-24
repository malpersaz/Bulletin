CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(32) NOT NULL,
  `data` text NOT NULL,
  `stime` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX(`id`)
) ENGINE=InnoDB;