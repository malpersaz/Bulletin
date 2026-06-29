<?php
class videos_model extends ORM
{
    public $_table = "videos";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $category_id;
    public $name;
    public $original;
    public $duration;
    public $created_at;
    public $status;
    public $embed;
    public $content;
    public $title;
    public $keywords;
    public $description;
    public $image;
    public $acl_id;
    public $user_id;
    public $categoryname;
    public $category_seo;
    public $username;
    public $u_name;
    public $u_last_name;
    public $groupad;

    public function __construct()
    {
        parent::__construct();
        $this->ensureViewExists();
    }

    private function ensureViewExists()
    {
        if ($this->viewExists('videos')) {
            return;
        }

        $sql = "CREATE VIEW videos AS SELECT
  mod_video_embed.id,
    mod_video_embed.name,
    mod_video_embed.created_at,
    mod_video_embed.status,
    mod_video_embed.content,
    mod_video_embed.original,
    mod_video_embed.duration,
    mod_video_embed.embed,

    mod_video_embed.title,
    mod_video_embed.keywords,
    mod_video_embed.description,
    mod_video_embed.acl_id AS acl_id,
    mod_video_embed.image,
    mod_video_embed.lang,
    mod_video_embed_category.name AS category_name,
    mod_video_embed_category.seo AS category_seo,
    mod_video_embed_category.id AS category_id,
    mod_user.username,
    mod_user.id AS user_id,
    mod_user.name AS u_name,
    mod_user.last_name AS u_last_name,
    mod_user_group.name AS groupname
   FROM mod_video_embed
   LEFT JOIN mod_user ON mod_video_embed.user_id = mod_user.id
   LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
   LEFT JOIN mod_video_embed_category ON mod_video_embed.category_id = mod_video_embed_category.id";

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
