<?php

class mod_acl_model extends ORM
{
    public $_table = "mod_acl";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $name;
    public $acl;
    public $user_id;
}