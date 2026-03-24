<?php
class mod_document_model extends ORM
{
    public $_table = "mod_document";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $seo;
        public $status;
        public $image;
        public $title;
        public $keywords;
        public $description;
        public $menu;
        public $user_id;
    }