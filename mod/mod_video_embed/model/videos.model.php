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
}
