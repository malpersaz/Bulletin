<?php

require_once(__DIR__ . '/../index/indexBase.php');


class management_mod extends indexBase
{

    function __construct()
    {
                parent::__construct();

        $this->tema = Load::lib('AdminTemplate');
        //$this->site = Kayit::getir('site_config');
        $this->lang = Load::lang('management');
        $this->tema->lang($this->lang);
        $this->db = Load::lib('Database');
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
        //print_r($_SESSION);
    }


    /***************
    ADMİN PANEL
     ***************/
    public function _index()
    {
        $this->_site();
        //$this->_lang();
        $this->tema->lang(Load::lang('mod_user'));
        
        // Load mod_content language for FAB/Wizard support across all admin pages
        $this->tema->lang(Load::lang('mod_content'));
        
        // Load mod_video_embed language for FAB/Wizard support across all admin pages
        $this->tema->lang(Load::lang('mod_video_embed'));
        
        // Load languages for wizard (from database)
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo ASC");
        $this->tema->setGlobal('lang_lang', $this->db->getResult()->toArray());
        
        // Load Content categories for wizard
        $mod_category = Load::model('mod_category');
        $mod_category->select('id, name, lang');
        $mod_category->addWhere("parent_id != '0'");
        $mod_category->all();
        $this->tema->setGlobal('categories', $mod_category->toArray());

        // Load Video Embed categories for wizard
        $mod_video_category = Load::model('mod_video_embed_category');
        $mod_video_category->select('id, name, lang');
        $mod_video_category->addWhere("parent_id != '0'");
        $mod_video_category->all();
        $this->tema->setGlobal('video_embed_categories', $mod_video_category->toArray());
        
        // $this->tema->tpl('management/index');
    }
    public function _site()
    {
        $this->site['title'] = $this->lang['management']['1'];
        $this->tema->setGlobal('site', $this->site);
    }


    public function _lang()
    {
        $this->db->fetch_array("SELECT name,seo FROM `lang` LIMIT 0,3");
        $this->tema->setGlobal('_lang_list', $this->db->result);
    }
}
