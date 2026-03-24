<?php
class mod_video_embed_category_model extends ORM
{
    public $_table = "mod_video_embed_category";
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
        public $image;
        public $user_id;
    }