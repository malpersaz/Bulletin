<?php
class mod_settings_affiliation_model extends ORM
{
    public $_table = "mod_settings_affiliation";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $name;
    public $url;
    public $logo;
    public $display_order;
    public $is_active;
    public $user_id;
    public $created_at;
    public $updated_at;
}
