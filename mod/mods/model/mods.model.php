<?php
class mods_model extends ORM
{
    public $_table = "mods";
    public $_pk = "mod_id";
    public $_ai = "mod_id";
    public $mod_id;
    public $mod_name;
    public $status;
    public $schema_ready;
    public $installed_at;
    public $updated_at;

}
