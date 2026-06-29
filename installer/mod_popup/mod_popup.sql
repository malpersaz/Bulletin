CREATE TABLE  `mod_popup` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11) NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`publish_at` TIMESTAMP NOT NULL ,
`expired_at` TIMESTAMP NOT NULL ,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`content` TEXT DEFAULT NULL ,
`duration` VARCHAR(255)  DEFAULT NULL ,
`video` ENUM(  'url',  'youtube',  'vimeo',  'instagram' ) NOT NULL DEFAULT  'url' ,
`link` VARCHAR(255)  DEFAULT NULL ,
`image` VARCHAR(20),
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
