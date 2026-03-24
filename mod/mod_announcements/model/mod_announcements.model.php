<?php
class mod_announcements_model extends ORM
{
    public $_table = "mod_announcements";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $created_at;
        public $status;
        public $deck;
        public $summary;
        public $content;
        public $image;
        public $acl_id;
        public $user_id;
    }