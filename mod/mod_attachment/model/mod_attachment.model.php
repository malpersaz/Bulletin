<?php
class mod_attachment_model extends ORM
{
    public $_table = "mod_attachment";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $filename;
    public $url;
    public $ext;
    public $original_name;
    public $type;
    public $created_at;
    public $mod_name;
    public $vid;
    public $user_id;
}