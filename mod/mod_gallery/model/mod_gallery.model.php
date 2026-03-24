<?php
class mod_gallery_model extends ORM
{
    public $_table = "mod_gallery";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $name;
    public $url;
    public $created_at;
    public $mod_name;
    public $vid;
    public $user_id;
}