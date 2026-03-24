<?php
class mod_attachment_mod_model extends ORM
{
    public $_table = "mod_attachment_mod";
    public $_pk = "id";
    public $_ai = "id";
        public $id;
        public $name;
        public $mod_name;
        public $mod_key;
        public $mod_value;
        public $user_id;
    }