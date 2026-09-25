<?php
class mod_content_model extends ORM
{
    public $_table = "mod_content";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $category_id;
        public $status;
        public $created_at;
        public $updated_at;
        public $deck;
        public $summary;
        public $content;
        public $seo;
        public $title;
        public $keywords;
        public $description;
        public $gallery_id;
        public $file_id;
        public $image;
        public $acl_id;
        public $video;
        public $user_id;
        public $slider;
    }