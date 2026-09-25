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
    public $gallery_id = 0;
    public $file_id = 0;
    public $acl_id = 0;
    public $view_count = 0;
    public $user_id;
}