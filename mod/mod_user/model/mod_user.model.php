<?php
class mod_user_model extends ORM
{
    public $_table = "mod_user";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $group_id;
    public $status;
    public $name;
    public $last_name;
    public $phone;
    public $updated_at;
    public $created_at;
    public $username;
    public $password;
    public $email;
    public $sid;
    public $ip;
    public $image;
    public $city;
    public $blog_name;
    public $about;
}