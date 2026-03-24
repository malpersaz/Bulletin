<?php
class mod_slider_model extends ORM
{
    public $_table = "mod_slider";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $url;
        public $status;
        public $target;
        public $created_at;
        public $description;
        public $image;
        public $image_mobile;
        public $acl_id;
        public $user_id;
    }