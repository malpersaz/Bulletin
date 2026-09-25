<?php

class mod_faq_model extends ORM
{
    public $_table = "mod_faq";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $mod_name;
    public $vid;
    public $question;
    public $answer;
    public $sort_order;
    public $status;
    public $user_id;
    public $created_at;
    public $updated_at;
}
