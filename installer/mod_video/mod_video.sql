CREATE TABLE  `mod_video` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(255)  NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`duration` VARCHAR(8)  NOT NULL ,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`description` TEXT DEFAULT NULL ,
`image` VARCHAR(20),
`flv` VARCHAR(20),
`original` varchar(20) DEFAULT NULL,
`acl_id` INT(11)  DEFAULT 0 ,
`user_id` INT(11) NOT NULL
)   DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;