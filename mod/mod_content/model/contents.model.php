<?php
class contents_model extends ORM
{
    public $_table = "contents";
    public $_pk = "id";
    public $id;
    public $name;
    public $created_at;
    public $status;
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
    public $hit;
    public $lang;
    public $category_name;
    public $categoryname;
    public $category_seo;
    public $category_id;
    public $username;
    public $user_id;
    public $u_name;
    public $u_last_name;
    public $groupad;
    public $rcount;
    public $video;

    public function __construct()
    {
        parent::__construct();
        $this->ensureViewExists();
    }

    private function ensureViewExists()
    {
        if ($this->viewExists('contents')) {
            return;
        }

        $sql = "CREATE VIEW contents AS SELECT
  mod_content.id,
    mod_content.name,
    mod_content.created_at,
    mod_content.status,
    mod_content.content,
    mod_content.summary,
    mod_content.seo,
    mod_content.title,
    mod_content.keywords,
    mod_content.description,
    mod_content.gallery_id AS gallery_id,
    mod_content.acl_id AS acl_id,
    mod_content.deck,
    mod_content.image,
    mod_content.file_id AS file_id,
    mod_content.lang,
    mod_content.video,
    mod_content_category.name AS category_name,
    mod_content_category.name AS categoryname,
    mod_content_category.seo AS category_seo,
    mod_content_category.id AS category_id,
    mod_user.username,
    mod_user.id AS user_id,
    mod_user.name AS u_name,
    mod_user.last_name AS u_last_name,
    mod_user_group.name AS group_name,
    ( SELECT count(mod_gallery.id) AS count
           FROM mod_gallery
          WHERE mod_content.id = mod_gallery.vid
          AND mod_gallery.mod_name='mod_content') AS gallery_count
   FROM mod_content
   LEFT JOIN mod_user ON mod_content.user_id = mod_user.id
   LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
   LEFT JOIN mod_content_category ON mod_content.category_id = mod_content_category.id";

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
