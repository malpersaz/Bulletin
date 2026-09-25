<?php

/**
 * User Role Model
 */
class mod_user_role_model extends ORM
{
    public $_table = "mod_user_role";
    public $_pk = "role_id";
    public $_ai = "role_id";
    public $role_id;
    public $mod_id;
    public $role_name;
}
