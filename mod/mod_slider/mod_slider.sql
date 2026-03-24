CREATE TABLE  `mod_slider` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` INT(11) NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`url` VARCHAR(255)  NOT NULL ,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`target` ENUM(  'y',  'n' ) NOT NULL DEFAULT  'y' ,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`description` TEXT DEFAULT NULL ,
`image` VARCHAR(20),
`acl_id` INT(11) DEFAULT 0 ,
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;