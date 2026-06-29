<?php
class mod_video_model extends ORM
{
    public $_table = "mod_video";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $name;
    public $duration;
    public $created_at;
    public $status;
    public $description;
    public $image;
    public $flv;
    public $original;
    public $acl_id;
    public $view_count;
    public $user_id;
}