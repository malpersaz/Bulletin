<?php

/* AKM Software
 * @ 2010
 * index Modül Sayfası
 * mod/index/index.php
 */

require_once(__DIR__ . '/indexBase.php');

class index_mod extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param mixed $helper
     * @param mixed $method
     * @param mixed $args
     * 
     * @return [type]
     */
    private function _loadHelper($helper, $method, $args = [])
    {
        $h = Load::helper($helper);
        return call_user_func_array([$h, $method], $args);
    }

    // Index
    public function index()
    {
        $this->_index();
    }



    // Mod Page
    public function sayfa($seo = null)
    {
        return $this->_loadHelper("index.mod_page", "sayfa", [$seo]);
    }

    // Mod Vid
    public function videos($sayfa = 1)
    {
        return $this->_loadHelper("index.mod_video_embed", "videos", [$sayfa]);
    }

    public function video($id = null)
    {
        return $this->_loadHelper("index.mod_video_embed", "video", [$id]);
    }

    // Mod Pictures
    public function gallery($category = null)
    {
        return $this->_loadHelper("index.mod_pictures", "gallery", [$category]);
    }



    // Mod Content
    public function content($seo = null)
    {
        return $this->_loadHelper("index.mod_content", "content", [$seo]);
    }

    public function contents($category = null, $sayfa = 1)
    {
        return $this->_loadHelper("index.mod_content", "contents", [$category, $sayfa]);
    }

    public function arsiv()
    {
        return $this->_loadHelper("index.mod_content", "arsiv");
    }

    public function news($seo = null)
    {
        return $this->_loadHelper("index.mod_news", "content", [$seo]);
    }

    public function newses($category = null, $sayfa = 1)
    {
        return $this->_loadHelper("index.mod_news", "contents", [$category, $sayfa]);
    }

    public function news_archive()
    {
        return $this->_loadHelper("index.mod_news", "arsiv");
    }

    // Mod Announcements
    public function announcement($id = null)
    {
        return $this->_loadHelper("index.mod_announcements", "announcement", [$id]);
    }

    public function announcements()
    {
        return $this->_loadHelper("index.mod_announcements", "announcements");
    }

    // Latest Content
    public function _latest_content($limit, $select)
    {
        return $this->_loadHelper("index.mod_content", "_latest_content", [$limit, $select]);
    }

    public function _latest_news($limit, $select)
    {
        return $this->_loadHelper("index.mod_news", "_latest_content", [$limit, $select]);
    }

    // Latest Announcements
    public function _latest_announcements($limit, $select)
    {
        return $this->_loadHelper("index.mod_announcements", "_latest_announcements", [$limit, $select]);
    }

    // Latest Videos
    public function _latest_videos($limit, $select)
    {
        return $this->_loadHelper("index.mod_video_embed", "_latest_videos", [$limit, $select]);
    }

    // Latest Pictures
    public function _latest_pictures($limit, $select)
    {
        return $this->_loadHelper("index.mod_pictures", "_latest_pictures", [$limit, $select]);
    }

    // Latest Slider
    public function _slider($limit)
    {
        return $this->_loadHelper("index.mod_slider", "enson_slider", [$limit]);
    }

    // Menu Management
    public function _menu()
    {
        return $this->_loadHelper("index.mod_menu", "menu");
    }



    // Active Survey
    public function _anket()
    {
        return $this->_loadHelper("index.mod_survey", "_anket");
    }

    // Survey Vote
    public function oyver()
    {
        return $this->_loadHelper("index.mod_survey", "oyver");
    }

    // Search
    public function ara($kelime = null)
    {
        return $this->_loadHelper("index.ara", "ara", [$kelime]);
    }

    // Information Collection
    public function gercek_kisi()
    {
        return $this->_loadHelper("index.bilgiedinme", "gercek_kisi");
    }

    public function tuzel_kisi()
    {
        return $this->_loadHelper("index.bilgiedinme", "tuzel_kisi");
    }
}
