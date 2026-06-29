CREATE TABLE  `mod_document` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11) NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`seo` VARCHAR(255)  NOT NULL ,
`status` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y' ,
`image` VARCHAR(20),
`title` VARCHAR(255)  NOT NULL ,
`keywords` VARCHAR(255)  NOT NULL ,
`description` VARCHAR(255)  NOT NULL ,
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
