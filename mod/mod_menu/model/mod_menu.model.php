<?php
class mod_menu_model extends ORM
{
    public $_table = "mod_menu";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $url;
        public $file;
        public $parent_id;
        public $order;
        public $target;
        public $status;
        public $html;
        public $icon;
        public $acl_id;
        public $user_id;
    }