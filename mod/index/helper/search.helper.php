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


        # mod_news arama
        if (Cache::exists_cache($kelime . "-arama-mod_news")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('news', $cached_data);
            }
        } else {
            $news = Load::model('mod_news');
            $news->select('summary,name,seo');
            $news->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $news->gR();
            if ($news->getSize() !== 0) {
                $this->tema->veri('news', $news->toArray());
            }
            Cache::set($news->toArray(), $kelime . "-arama-mod_news");
        }


        # mod_publication arama
        if (Cache::exists_cache($kelime . "-arama-mod_publication")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('publications', $cached_data);
            }
        } else {
            $publication = Load::model('mod_publication');
            $publication->select('summary,name,seo');
            $publication->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $publication->gR();
            if ($publication->getSize() !== 0) {
                $this->tema->veri('publications', $publication->toArray());
            }
            Cache::set($publication->toArray(), $kelime . "-arama-mod_publication");
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