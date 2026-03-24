<?php
class mod_video_embed_model extends ORM
{
    public $_table = "mod_video_embed";
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
}