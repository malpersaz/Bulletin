-- Avatar System Migration for mod_user table
-- Adds required fields for modern avatar upload system

-- 1. Update existing image column (varchar(20) -> varchar(255))
ALTER TABLE `mod_user`
MODIFY COLUMN `image` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL;

-- 2. Add avatar system fields
ALTER TABLE `mod_user`
ADD COLUMN `avatar_type` enum('default','custom','gravatar') NOT NULL DEFAULT 'default'
  COMMENT 'Avatar type: default, custom, or gravatar',
ADD COLUMN `avatar_updated_at` timestamp NULL DEFAULT NULL
  COMMENT 'Last avatar update timestamp',
ADD COLUMN `avatar_size` int(11) NULL DEFAULT NULL
  COMMENT 'Avatar file size (bytes)',
ADD COLUMN `avatar_mime` varchar(50) NULL DEFAULT NULL
  COMMENT 'Avatar MIME type (image/jpeg, image/png, etc)';

-- 3. Add indexes
ALTER TABLE `mod_user`
ADD INDEX `idx_avatar_type` (`avatar_type`),
ADD INDEX `idx_avatar_updated` (`avatar_updated_at`);

-- 4. Set existing users to default avatar
UPDATE `mod_user`
SET `avatar_type` = 'default'
WHERE `image` IS NULL OR `image` = '';

-- 5. Fix users with image data but no valid file path
UPDATE `mod_user`
SET `image` = NULL,
    `avatar_type` = 'default'
WHERE `image` != '' AND `image` NOT LIKE '%.jpg' AND `image` NOT LIKE '%.png' AND `image` NOT LIKE '%.svg';

-- 6. Set default avatar size and MIME info for existing images
UPDATE `mod_user`
SET `avatar_size` = 10240,
    `avatar_mime` = 'image/jpeg',
    `avatar_type` = 'custom'
WHERE `image` IS NOT NULL AND `image` != '';

-- Migration log table (optional)
CREATE TABLE IF NOT EXISTS `mod_user_avatar_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('upload','delete','update','reset') NOT NULL,
  `old_avatar` varchar(255) DEFAULT NULL,
  `new_avatar` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Avatar change logs';

-- Foreign key constraint (optional)
ALTER TABLE `mod_user_avatar_log`
ADD CONSTRAINT `fk_avatar_log_user`
FOREIGN KEY (`user_id`) REFERENCES `mod_user` (`id`)
ON DELETE CASCADE ON UPDATE CASCADE;