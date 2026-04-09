<?php

class mod_user_social_model extends ORM
{
    public $_table = "mod_user_social";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $user_id;
    public $platform;
    public $url;
    public $icon_class;
    public $display_order;
    public $is_active;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        if ($this->tableExists('mod_user_social')) {
            return;
        }

        $isSqlite = $this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false;

        if ($isSqlite) {
            $this->query('CREATE TABLE IF NOT EXISTS "mod_user_social" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "user_id" INTEGER NOT NULL,
                "platform" VARCHAR(50) NOT NULL,
                "url" VARCHAR(500) NOT NULL,
                "icon_class" VARCHAR(100) DEFAULT NULL,
                "display_order" INTEGER NOT NULL DEFAULT 0,
                "is_active" CHAR(1) NOT NULL DEFAULT "y"
            )');
            $this->query('CREATE UNIQUE INDEX IF NOT EXISTS "uniq_user_platform" ON "mod_user_social" ("user_id", "platform")');
            $this->query('CREATE INDEX IF NOT EXISTS "idx_user_social_user" ON "mod_user_social" ("user_id")');
            return;
        }

        $this->query("CREATE TABLE IF NOT EXISTS `mod_user_social` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) NOT NULL,
            `platform` VARCHAR(50) NOT NULL,
            `url` VARCHAR(500) NOT NULL,
            `icon_class` VARCHAR(100) DEFAULT NULL,
            `display_order` INT(11) NOT NULL DEFAULT 0,
            `is_active` ENUM('y', 'n') NOT NULL DEFAULT 'y',
            UNIQUE KEY `uniq_user_platform` (`user_id`, `platform`),
            INDEX `idx_user_social_user` (`user_id`)
        ) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    }

    private function tableExists($tableName)
    {
        $safeTable = addslashes($tableName);
        if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . $safeTable . "'");
            return $this->getSize() !== 0;
        }

        $this->fetch_array("SHOW TABLES LIKE '" . $safeTable . "'");
        return $this->getSize() !== 0;
    }
}
