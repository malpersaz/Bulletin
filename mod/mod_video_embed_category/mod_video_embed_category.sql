CREATE TABLE  `mod_video_embed_category` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11) NOT NULL ,
`name` VARCHAR(255)  NOT NULL ,
`parent_id` INT(11) NOT NULL,
`sira` INT(11) NOT NULL,
`root` ENUM(  'y',  'n' ) NOT NULL DEFAULT  'n' ,
`seo` VARCHAR(255)  NOT NULL ,
`title` VARCHAR(255)  NOT NULL ,
`keywords` VARCHAR(255)  NOT NULL ,
`description` VARCHAR(255)  NOT NULL ,
`image` VARCHAR(20),
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;