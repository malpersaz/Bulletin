<?php

class mod_user_pm_model extends ORM
{
    public $_table = "mod_user_pm";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $user_id;
    public $reader_id;
    public $status;
    public $subject;
    public $message;
    public $created_at;

}
