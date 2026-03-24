<?php
class log_model extends ORM
{
    public $_table = "mod_user_log";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $username;
    public $password;
    public $created_at;
    public $ip;
    public $error;

}
        