<?php
class newses_model extends ORM
{
    public $_table = "newses";
    public $_pk = "id";
    public $id;
    public $name;
    public $created_at;
    public $updated_at;
    public $status;
    public $slider;
    public $show_on_homepage;
    public $content;
    public $summary;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $gallery_id;
    public $file_id;
    public $acl_id;
    public $deck;
    public $image;
    public $image_alt;
    public $image_credit;
    public $lang;
    public $categoryname;
    public $category_name;
    public $category_seo;
    public $category_id;
    public $category_acl_id;
    public $username;
    public $user_id;
    public $u_name;
    public $u_last_name;
    public $user_image;
    public $group_name;
    public $gallery_count;
    public $video;
    public $view_count;

    public function __construct()
    {
        parent::__construct();
        $this->ensureViewExists();
    }

    private function ensureViewExists()
    {
        if ($this->viewExists('newses') && $this->hasUserImageColumn() && $this->hasViewCountColumn() && $this->hasCategoryAclIdColumn() && $this->hasUpdatedAtColumn() && $this->hasShowOnHomepageColumn() && $this->hasImageAltColumn() && $this->hasImageCreditColumn()) {
            return;
        }

        $this->ensureTableColumns();
        $this->dropViewIfExists('newses');

        $sql = "CREATE VIEW newses AS SELECT
  mod_news.id,
    mod_news.name,
    mod_news.created_at,
    mod_news.updated_at,
    mod_news.status,
    mod_news.slider,
    mod_news.show_on_homepage,
    mod_news.content,
    mod_news.summary,
    mod_news.seo,
    mod_news.title,
    mod_news.keywords,
    mod_news.description,
    mod_news.gallery_id AS gallery_id,
    mod_news.acl_id AS acl_id,
    mod_news.deck,
    mod_news.image,
    mod_news.image_alt,
    mod_news.image_credit,
    mod_news.file_id AS file_id,
    mod_news.lang,
    mod_news.video,
    mod_news.view_count,
    mod_news_category.name AS category_name,
    mod_news_category.name AS categoryname,
    mod_news_category.seo AS category_seo,
    mod_news_category.id AS category_id,
    mod_news_category.acl_id AS category_acl_id,
    mod_user.username,
    mod_user.id AS user_id,
    mod_user.name AS u_name,
    mod_user.last_name AS u_last_name,
    mod_user.image AS user_image,
    mod_user_group.name AS group_name,
    (
        SELECT count(mod_gallery.id)
        FROM mod_gallery
        WHERE mod_news.id = mod_gallery.vid
          AND mod_gallery.mod_name = 'mod_news'
    ) AS gallery_count
   FROM mod_news
   LEFT JOIN mod_user ON mod_news.user_id = mod_user.id
   LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
   LEFT JOIN mod_news_category ON mod_news.category_id = mod_news_category.id";

        $this->query($sql);
    }

    private function hasImageAltColumn()
    {
        return $this->hasViewColumn('image_alt');
    }

    private function hasImageCreditColumn()
    {
        return $this->hasViewColumn('image_credit');
    }

    private function ensureTableColumns()
    {
        try {
            if (!$this->hasTableColumn('mod_news', 'image_alt')) {
                $this->query("ALTER TABLE `mod_news` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL AFTER `image`");
            }
            if (!$this->hasTableColumn('mod_news', 'image_credit')) {
                $this->query("ALTER TABLE `mod_news` ADD COLUMN `image_credit` varchar(255) DEFAULT NULL AFTER `image_alt`");
            }
        } catch (\Throwable $e) {
            // Graceful fallback if SQLite or already added
        }
    }

    private function hasTableColumn($tableName, $columnName)
    {
        try {
            if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
                $safeTable = addslashes($tableName);
                $this->fetch_array("PRAGMA table_info('{$safeTable}')");
                if (is_array($this->_result)) {
                    foreach ($this->_result as $col) {
                        if (isset($col['name']) && $col['name'] === $columnName) {
                            return true;
                        }
                    }
                }
                return false;
            }

            $safeTable = addslashes($tableName);
            $safeCol = addslashes($columnName);
            $this->fetch_array("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeCol}'");
            return $this->getSize() !== 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasUserImageColumn()
    {
        return $this->hasViewColumn('user_image');
    }

    private function hasViewCountColumn()
    {
        return $this->hasViewColumn('view_count');
    }

    private function hasCategoryAclIdColumn()
    {
        return $this->hasViewColumn('category_acl_id');
    }

    private function hasUpdatedAtColumn()
    {
        return $this->hasViewColumn('updated_at');
    }

    private function hasShowOnHomepageColumn()
    {
        return $this->hasViewColumn('show_on_homepage');
    }

    private function hasViewColumn($columnName)
    {
        try {
            if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
                $this->fetch_array("PRAGMA table_info('newses')");
                if (is_array($this->_result)) {
                    foreach ($this->_result as $col) {
                        if (isset($col['name']) && $col['name'] === $columnName) {
                            return true;
                        }
                    }
                }
                return false;
            }

            $safeCol = addslashes($columnName);
            $this->fetch_array("SHOW COLUMNS FROM newses LIKE '" . $safeCol . "'");
            return $this->getSize() !== 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dropViewIfExists($viewName)
    {
        $safeView = addslashes($viewName);
        $this->query("DROP VIEW IF EXISTS " . $safeView);
    }

    private function viewExists($viewName)
    {
        $safeView = addslashes($viewName);
        if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->fetch_array("SELECT name FROM sqlite_master WHERE type='view' AND name='" . $safeView . "'");
            return $this->getSize() !== 0;
        }

        $this->fetch_array("SHOW FULL TABLES LIKE '" . $safeView . "'");
        return $this->getSize() !== 0;
    }
}
