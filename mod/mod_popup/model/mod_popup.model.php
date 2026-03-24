<?php
class mod_popup_model extends ORM
{
    public $_table = "mod_popup";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $lang;
        public $name;
        public $publish_at;
        public $expired_at;
        public $status;
        public $content;
        public $duration;
        public $video;
        public $link;
        public $image;
        public $user_id;
    }