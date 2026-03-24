<?php
class publications_model extends ORM
{
    public $_table = "publications";
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
    public $category_seo;
    public $category_id;
    public $username;
    public $user_id;
    public $u_name;
    public $u_last_name;
    public $user_image;
    public $group_name;
    public $gallery_count;
    public $video;
}
