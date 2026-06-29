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
        Load::libfile('Cache');
        Cache::$sure = 60 * 60;


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

        if (Cache::exists_cache($kelime . "-arama-mod_product")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('products', $cached_data);
            }
        } else {
            $db = Load::lib('Database');
            $products = $db->fetch_array("
                SELECT name, seo, summary
                FROM mod_product
                WHERE lang = '" . $this->site['lang'] . "'
                  AND status = 'y'
                  AND (
                    content LIKE '" . addslashes($like_keyword) . "'
                    OR summary LIKE '" . addslashes($like_keyword) . "'
                    OR name LIKE '" . addslashes($like_keyword) . "'
                  )
                ORDER BY created_at DESC, id DESC
            ")->getResult()->toArray();
            if (!empty($products)) {
                $this->tema->veri('products', $products);
            }
            Cache::set($products, $kelime . "-arama-mod_product");
        }

        if (Cache::exists_cache($kelime . "-arama-mod_client")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('clients', $cached_data);
            }
        } else {
            $db = Load::lib('Database');
            $clients = $db->fetch_array("
                SELECT name, seo, description
                FROM mod_client
                WHERE lang = '" . $this->site['lang'] . "'
                  AND status = 'y'
                  AND (
                    content LIKE '" . addslashes($like_keyword) . "'
                    OR description LIKE '" . addslashes($like_keyword) . "'
                    OR name LIKE '" . addslashes($like_keyword) . "'
                  )
                ORDER BY created_at DESC, id DESC
            ")->getResult()->toArray();
            if (!empty($clients)) {
                $this->tema->veri('clients', $clients);
            }
            Cache::set($clients, $kelime . "-arama-mod_client");
        }

        if (Cache::exists_cache($kelime . "-arama-mod_service")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('services', $cached_data);
            }
        } else {
            $db = Load::lib('Database');
            $services = $db->fetch_array("
                SELECT name, seo, summary
                FROM mod_service
                WHERE lang = '" . $this->site['lang'] . "'
                  AND status = 'y'
                  AND (
                    content LIKE '" . addslashes($like_keyword) . "'
                    OR summary LIKE '" . addslashes($like_keyword) . "'
                    OR name LIKE '" . addslashes($like_keyword) . "'
                  )
                ORDER BY created_at DESC, id DESC
            ")->getResult()->toArray();
            if (!empty($services)) {
                $this->tema->veri('services', $services);
            }
            Cache::set($services, $kelime . "-arama-mod_service");
        }

        if (Cache::exists_cache($kelime . "-arama-mod_reference")) {
            $cached_data = Cache::get_cache();
            if ($cached_data !== FALSE) {
                $this->tema->veri('references', $cached_data);
            }
        } else {
            $db = Load::lib('Database');
            $references = $db->fetch_array("
                SELECT name, seo, description
                FROM mod_reference
                WHERE lang = '" . $this->site['lang'] . "'
                  AND status = 'y'
                  AND (
                    content LIKE '" . addslashes($like_keyword) . "'
                    OR description LIKE '" . addslashes($like_keyword) . "'
                    OR name LIKE '" . addslashes($like_keyword) . "'
                  )
                ORDER BY created_at DESC, id DESC
            ")->getResult()->toArray();
            if (!empty($references)) {
                $this->tema->veri('references', $references);
            }
            Cache::set($references, $kelime . "-arama-mod_reference");
        }

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
        $this->tema->veri('product_lang', Load::lang('mod_product'));
        $this->tema->veri('client_lang', Load::lang('mod_client'));
        $this->tema->veri('service_lang', Load::lang('mod_service'));
        $this->tema->veri('reference_lang', Load::lang('mod_reference'));
        $this->tema->veri('kelime', $kelime);
        $this->tema->tpl('index/search');
        $this->_index();
    }
}
