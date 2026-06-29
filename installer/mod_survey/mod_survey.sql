CREATE TABLE  `mod_survey` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`lang` VARCHAR(11)  NOT NULL ,
`question` VARCHAR(255)  NOT NULL ,
 `status` ENUM('y', 'n') NOT NULL DEFAULT 'y' ,
`acl_id` INT(11)  NOT NULL,
`user_id` INT(11) NOT NULL
)   DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
