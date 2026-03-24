<?php

class inbox_model extends ORM {
    public $_table = "mod_user_pm_inbox";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $status;
    public $subject;
    public $message;
    public $created_at;
    public $reader_id;
    public $user_id;
}
