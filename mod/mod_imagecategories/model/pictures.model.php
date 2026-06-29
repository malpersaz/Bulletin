<?php
class pictures_model extends ORM
{
    public $_table = "pictures";
    public $_pk = "id";
    public $id;
    public $name;
    public $lang;
    public $image;
    public $content;
    public $parent_id;
    public $order;
    public $root;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $acl_id;
    public $user_id;
    public $gallery_name;
    public $gallery_url;
    public $gallery_created_at;
    public $vid;

    public function __construct()
    {
        parent::__construct();
        $this->ensureViewExists();
    }

    private function ensureViewExists()
    {
        if ($this->viewExists('pictures')) {
            return;
        }

        $sql = "CREATE VIEW pictures AS SELECT
            mod_imagecategories.id AS id,
            mod_imagecategories.name AS name,
            mod_imagecategories.image AS image,
            mod_imagecategories.content AS content,
            mod_imagecategories.lang AS lang,
            mod_imagecategories.parent_id AS parent_id,
            mod_imagecategories.`order` AS `order`,
            mod_imagecategories.root AS root,
            mod_imagecategories.seo AS seo,
            mod_imagecategories.title AS title,
            mod_imagecategories.keywords AS keywords,
            mod_imagecategories.description AS description,
            mod_imagecategories.acl_id AS acl_id,
            mod_imagecategories.user_id AS user_id,
            mod_gallery.name AS gallery_name,
            mod_gallery.url AS gallery_url,
            mod_gallery.created_at AS gallery_created_at,
            mod_gallery.vid AS vid
        FROM mod_imagecategories
        LEFT JOIN mod_gallery ON mod_imagecategories.id = mod_gallery.vid
        WHERE mod_gallery.mod_name = 'mod_imagecategories'";

        $this->query($sql);
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
