<?php
class mod_settings_model extends ORM
{
    public $_table = "mod_settings";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $title;
    public $description;
    public $owner_name;
    public $phone;
    public $whatsapp;
    public $email;
    public $office_address;
    public $work_address;
    public $map_embed_code;
    public $google_search_console;
    public $google_analytics;
    public $meta_tags;
    public $show_logo;
    public $logo_path;
    public $favicon_path;
    public $favicon_96;
    public $apple_touch_icon;
    public $site_webmanifest;
    public $manifest_192;
    public $manifest_512;
    public $established_year;
    public $user_id;
    public $site_icon;
    public $logo_type;
    public $logo_symbol;
}
