<?php
class mod_imagecategories_model extends ORM
{
    public $_table = "mod_imagecategories";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $name;
    public $content;
    public $parent_id;
    public $order;
    public $root;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $acl_id;
    public $menu;
    public $image;
    public $user_id;
}
