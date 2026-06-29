-- Languages Table
CREATE TABLE `lang` (
  `name` varchar(10) NOT NULL,
  `seo` varchar(10) NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'y',
  `is_default` char(1) NOT NULL DEFAULT 'n',
  PRIMARY KEY(seo)

) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

