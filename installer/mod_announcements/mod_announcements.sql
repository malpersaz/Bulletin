CREATE TABLE  `mod_announcements` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11)  NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`deck` ENUM(  'y',  'n' ) NOT NULL DEFAULT 'n' ,
`summary` TEXT NOT NULL ,
`content` TEXT NOT NULL ,
`image` VARCHAR(20),
`gallery_id` INT(11) NOT NULL,
`file_id` INT(11) NOT NULL,
`acl_id` INT(11)  DEFAULT NULL ,
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
