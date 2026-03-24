<?php
class mod_headline_model extends ORM
{
    public $_table = "mod_headline";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $status;
        public $created_at;
        public $image;
        public $user_id;
    }