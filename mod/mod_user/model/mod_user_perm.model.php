<?php
class mod_user_perm_model extends ORM
{
    public $_table = "mod_user_perm";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $group_id;
    public $roll_id;
    public $mod_id;

}