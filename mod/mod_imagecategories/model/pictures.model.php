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

}
