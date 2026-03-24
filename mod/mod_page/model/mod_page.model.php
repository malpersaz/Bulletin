<?php
class mod_page_model extends ORM
{
    public $_table = "mod_page";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $name;
    public $parent_id;
    public $order;
    public $root;
    public $status;
    public $deck;
    public $summary;
    public $content;
    public $gallery_id;
    public $file_id;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $image;
    public $acl_id;
    public $category_id;
    public $menu;
    public $user_id;
}
