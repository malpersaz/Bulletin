<?php
class mod_menu_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function menu()
    {
        $mod_content_nested = Load::model('mod_menu');
        $mod_content_nested->where_lang($this->site['lang']);
        $mod_content_nested->order('order', 'ASC')->all();
        return Helper::nested($mod_content_nested->toArray(), 'parent_id', 'id');
    }
}
