<?php
class mod_news_category_model extends ORM
{
    public $_table = "mod_news_category";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $name;
    public $parent_id;
    public $order;
    public $root;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $menu;
    public $show_on_homepage;
    public $image;
    public $user_id;
    public $acl_id;
}