<?php

class search_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function search($a = NULL)
    {

        $url = Load::lib('Url');
        $url->parse($a);

        $kelime = NULL;

        if ($url->exists_get('kelime')) {
            $kelime = $url->get_clear('kelime');
        }

        if ($kelime === NULL) {
            return $this->_404();
        }

        if (mb_strlen($kelime, "UTF-8") < 3) {
            return $this->_404();
        }


        $like_keyword = "%" . $kelime . "%";

        // Cache Kütüphanesi
        Load::libfile('Cache');
        // Cache Süresi
        Cache::$sure = 60 * 60;


        # mod_content arama
        if (Cache::exists_cache($kelime . "-arama-contents")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('contents', $cached_data);
            }
        } else {
            $content = Load::model('index.contents');
            $content->select('summary,name,seo');
            $content->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $content->gR();
            if ($content->getSize() !== 0) {
                $this->tema->veri('contents', $content->toArray());
            }
            Cache::set($content->toArray(), $kelime . "-arama-contents");
        }


        # mod_announcements arama
        if (Cache::exists_cache($kelime . "-arama-mod_announcements")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('announcements', $cached_data);
            }
        } else {
            $announcement = Load::model('mod_announcements');
            $announcement->select('name,id');
            $announcement->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $announcement->gR();
            if ($announcement->getSize() !== 0) {
                $this->tema->veri('announcements', $announcement->toArray());
            }
            Cache::set($announcement->toArray(), $kelime . "-arama-mod_announcements");
        }




        # mod_page arama
        if (Cache::exists_cache($kelime . "-arama-mod_page")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('sayfalar', $cached_data);
            }
        } else {
            $sayfa = Load::model('mod_page');
            $sayfa->select('name,seo');
            $sayfa->where_lang_and_status_and_root_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', 'n', $like_keyword, $like_keyword, $like_keyword);
            $sayfa->gR();
            if ($sayfa->getSize() !== 0) {
                $this->tema->veri('sayfalar', $sayfa->toArray());
            }
            Cache::set($sayfa->toArray(), $kelime . "-arama-mod_page");
        }


        # mod_video_embed arama
        if (Cache::exists_cache($kelime . "-arama-mod_video_embed")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('videos', $cached_data);
            }
        } else {
            $vid = Load::model('mod_video_embed');
            $vid->select('name,id');
            $vid->where_lang_and_status_and_og_like_content_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword);
            $vid->gR();
            if ($vid->getSize() !== 0) {
                $this->tema->veri('videos', $vid->toArray());
            }
            Cache::set($vid->toArray(), $kelime . "-arama-mod_video_embed");
        }
        $this->tema->veri('kelime', $kelime);
        $this->tema->tpl('index/search');
        $this->_index();
    }
}