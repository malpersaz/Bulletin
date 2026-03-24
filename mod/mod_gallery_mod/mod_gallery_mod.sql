CREATE TABLE  `mod_gallery_mod` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`name` VARCHAR(255)  NOT NULL ,
`mod_name` VARCHAR(255)  NOT NULL ,
`mod_key` VARCHAR(255)  NOT NULL ,
`mod_value` VARCHAR(255)  NOT NULL ,
`user_id` INT(11) NOT NULL
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;