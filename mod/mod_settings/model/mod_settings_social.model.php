<?php
class mod_settings_social_model extends ORM
{
    public $_table = "mod_settings_social";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $platform;
    public $url;
    public $icon_class;
    public $display_order;
    public $is_active;
    public $user_id;
}
