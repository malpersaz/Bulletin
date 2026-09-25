CREATE TABLE  `mod_video_embed` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11) NOT NULL ,
`category_id` INT(11)  NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`original` VARCHAR(255)  DEFAULT NULL ,
`duration` VARCHAR(8)  NOT NULL ,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`embed` TEXT NOT NULL ,
`content` TEXT NOT NULL ,
`title` VARCHAR(255)  NOT NULL ,
`keywords` VARCHAR(255)  NOT NULL ,
`description` VARCHAR(255)  NOT NULL ,
`image` VARCHAR(20),
`acl_id` INT(11) DEFAULT 0 ,
`user_id` INT(11) NOT NULL,
`slider` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n'
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;