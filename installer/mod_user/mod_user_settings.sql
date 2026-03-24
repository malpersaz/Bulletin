-- User Settings
CREATE TABLE `mod_user_settings` (
  `k` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `v` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`k`),
  UNIQUE KEY `k` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Insert Default User Settings
-- INSERT INTO `mod_user_settings` VALUES('activation', '2');
-- INSERT INTO `mod_user_settings` VALUES('status', '1');
-- INSERT INTO `mod_user_settings` VALUES('group_id', '3');