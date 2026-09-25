<?php
class mod_settings_cookies_model extends ORM
{
    public $_table = "mod_settings_cookies";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $cookie_name;
    public $category;
    public $provider;
    public $purpose;
    public $duration;
    public $display_order;
    public $is_active;
    public $user_id;
}
