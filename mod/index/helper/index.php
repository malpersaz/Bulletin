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
    public function page($seo = null)
    {
        return $this->_loadHelper("index.mod_page", "page", [$seo]);
    }

    // Mod Vid
    public function videos($page = 1)
    {
        return $this->_loadHelper("index.mod_video_embed", "videos", [$page]);
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

    public function news($seo = null)
    {
        return $this->_loadHelper("index.mod_news", "news", [$seo]);
    }

    public function newses($category = null, $page = 1)
    {
        return $this->_loadHelper("index.mod_news", "newses", [$category, $page]);
    }

    public function news_archive()
    {
        return $this->_loadHelper("index.mod_news", "archive");
    }

    public function publication($seo = null)
    {
        return $this->_loadHelper("index.mod_publication", "publication", [$seo]);
    }

    public function publications($category = null, $page = 1)
    {
        return $this->_loadHelper("index.mod_publication", "publications", [$category, $page]);
    }

    public function publication_archive()
    {
        return $this->_loadHelper("index.mod_publication", "archive");
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

    public function _latest_news($limit, $select)
    {
        return $this->_loadHelper("index.mod_news", "_latest_content", [$limit, $select]);
    }

    public function _latest_publications($limit, $select)
    {
        return $this->_loadHelper("index.mod_publication", "_latest_publications", [$limit, $select]);
    }

    public function _latest_authors($limit)
    {
        return $this->_loadHelper("index.mod_publication", "_latest_authors", [$limit]);
    }

    public function authors($limit = 10)
    {
        return $this->_loadHelper("index.mod_publication", "authors", [$limit]);
    }

    public function author($username = null, $page = 1)
    {
        return $this->_loadHelper("index.mod_publication", "author", [$username, $page]);
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

    public function _hero_slider($limit)
    {
        return $this->_loadHelper("index.mod_hero_slider", "hero_slider", [$limit]);
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
