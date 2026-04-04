<?php
class mod_news_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _categories()
    {
        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->where_lang_and_root($this->site['lang'], 'n');
        $mod_news_category_nested->order('order', 'ASC')->gR();
        
        $categories = $mod_news_category_nested->toArray();
        $news_model = Load::model('mod_news.newses');
        
        if (is_array($categories)) {
            foreach ($categories as &$kat) {
                $news_model->flush();
                $kat['news_count'] = $news_model->where_category_seo_and_status_and_lang($kat['seo'], 'y', $this->site['lang'])->getCount();
            }
        }
        
        $this->tema->ctx_set('categories', $categories);
    }
    public function news($seo)
    {
        $this->_categories();
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $news = Load::model('mod_news.newses');
        $news->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $news->limit(1);
        $news->gRo();
        if ($news->getSize() !== 0) {
            // Senkronize Edelim
            $news->sync();
            // Permission Kontrol
            if ($this->_permission_kontrol($news->acl_id)) {
                return;
            }
            // Hejmara xwendinê zêde bike (IP Bazlı)
            $this->_update_view_count('mod_news', $news->id);
            
            // Bakalım Video varmı : $video olarak gönderilecek
            if (isset($news->video) && $news->video != '') {
                $this->_mod_video($news->video);
            }
            // Bakalım Resim Varmı : $res olarak gönderilecek
            if (isset($news->gallery_id) && $news->gallery_id != 0) {
                $this->_exact_gallery($news->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_news', $news->id);
            }
            // Bakalım Dosya Varmı : $file olarak gönderilecek
            if (isset($news->file_id) && $news->file_id != 0) {
                $this->_exact_attachment($news->file_id);
            } else {
                $this->_mod_attachment('mod_news', $news->id);
            }
            // Sonucu Temaya Gönder
            $this->tema->ctx_set('d', $news->toArray());
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_news/news');
        $this->_index();
        unset($news);
    }

    public function newses($category = NULL, $page = 1)
    {
        $this->_categories();

        $page_limit = 80; // Page Limit
        $pager_limit = 4; // Pagination Limit
        $offset = ($page - 1) * $page_limit;

        if (($page == "") or ($page == NULL)) {
            $page = 1;
        }

        $page = intval($page); // Current Page


        $news = Load::model('mod_news.newses');
        $category_model = Load::model('mod_news_category');

        if ($category == NULL or $category == 'all') {
            $news->where_status_and_lang('y', $this->site['lang'])->all();
            $total_item = $news->getSize();
            $news->where_status_and_lang('y', $this->site['lang']);
            $news->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($news->getSize() != 0) {
                $news_items = $news->toArray();
                foreach ($news_items as &$item) {
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                    
                    // Published ago logic
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                $this->tema->ctx_set('newses', $news_items);
            }
            $category_seo = 'all';
            $categoryname = $this->lang['index']['mod_news']['2'];
        } elseif ($category_model->exists_seo($category) === False) {
            return $this->_404();
        } else {
            $news->where_category_seo_and_status_and_lang($category, 'y', $this->site['lang']);
            $news->order('created_at', 'DESC')->gR();
            $total_item = $news->getSize();
            //$news->where_category_seo($category)->limit($page_limit, $offset)->gR();
            $news->getLimit($page_limit, $offset); // Fetch from results based on limit
            if ($news->getSize() !== 0) {
                $news_items = $news->toArray();
                foreach ($news_items as &$item) {
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                $this->tema->ctx_set('newses', $news_items);
            }
            //print_r($news);
            // Sync Edelim
            $category_model->where_seo($category);
            $category_model->getResultOne();
            $category_model->sync();

            $categoryname = $category_model->name;
            $category_seo = $category_model->seo;
        }

        // Pagination
        $p = Load::lib('Pager');
        $p->total_item = $total_item;
        $p->page_limit = $page_limit;
        $p->pager_limit = $pager_limit;
        $p->page = $page;


        if ($p->check() === FALSE) {
            return $this->_404();
        }

        if ($p->Result() !== FALSE) {
            $this->tema->ctx_set('pagination', $p->Result());
        }

        if ($page != '1') {
            $d['link'] = $this->site['sadres'] . 'newses/' . $category . '/' . $page;
        } else {
            $d['link'] = $this->site['sadres'] . 'newses/' . $category;
        }

        if ($page <= 1) {
            $d['title'] = sprintf($this->lang['index']['mod_news']['1'], $categoryname);
        } else {
            $d['title'] = sprintf($this->lang['index']['mod_news']['5'], $categoryname, $page);
        }

        $d['description'] = $this->lang['index']['mod_news']['2'];
        $d['keywords'] = $this->lang['index']['mod_news']['3'];

        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('categoryname', $categoryname);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_news/newses');

        $this->_index();
    }

    public function _latest_news($limit, $select)
    {
        $m = Load::model('mod_news')->select($select)->where_lang_and_status($this->site['lang'], 'y');
        if ($this->_hasSliderColumn()) {
            $m->addWhere("slider='n'");
        }
        $m->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            $items = $m->toArray();
            if (is_array($items)) {
                foreach ($items as &$item) {
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                unset($item);
            }
            return $items;
        }

        return [];
    }

    /**
     * Fetch news categories pinned to the homepage and their news.
     * @param int $limit Limit of news items per category
     * @param string $select Fields to select from news table
     * @return array
     */
    public function _homepage_categories($limit = 4, $select = 'image,name,seo,summary,created_at')
    {
        $cat_model = Load::model('mod_news_category');
        $cat_model->where_lang_and_show_on_homepage_and_root($this->site['lang'], 'y', 'n');
        $cat_model->order('order', 'ASC')->gR();

        if ($cat_model->getSize() === 0) {
            return [];
        }

        $categories_raw = $cat_model->toArray();
        $news_model = Load::model('mod_news.newses');
        
        $categories = [];
        $seen_ids = [];

        foreach ($categories_raw as $cat) {
            // Check if we have already processed this specific category ID
            if (in_array($cat['id'], $seen_ids)) {
                continue;
            }
            $seen_ids[] = $cat['id'];
            
            $news_model->flush();
            $news_model->where_category_seo_and_status_and_lang($cat['seo'], 'y', $this->site['lang']);
            $news_model->select($select)->order('created_at', 'DESC')->limit($limit);
            $news_model->gR();

            $items = $news_model->toArray();
            if (is_array($items)) {
                foreach ($items as &$item) {
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                unset($item);
            }
            $cat['news'] = $items;
            $categories[] = $cat;
        }

        return $categories;
    }

    private function _hasSliderColumn()
    {
        $db = Load::lib('Database');
        $db->fetch_array("SHOW COLUMNS FROM `mod_news` LIKE 'slider'");
        $result = $db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    private function formatRelativeTime($datetime)
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            $published_at = new DateTime($datetime);
            $now = new DateTime('now');
            $diff = $published_at->diff($now);
        } catch (Exception $e) {
            return '';
        }

        if ($diff->y > 0) {
            return $diff->y . ' ' . ($diff->y === 1 ? 'year ago' : 'years ago');
        }
        if ($diff->m > 0) {
            return $diff->m . ' ' . ($diff->m === 1 ? 'month ago' : 'months ago');
        }
        if ($diff->d > 0) {
            return $diff->d . ' ' . ($diff->d === 1 ? 'day ago' : 'days ago');
        }
        if ($diff->h > 0) {
            return $diff->h . ' ' . ($diff->h === 1 ? 'hour ago' : 'hours ago');
        }
        if ($diff->i > 0) {
            return $diff->i . ' ' . ($diff->i === 1 ? 'minute ago' : 'minutes ago');
        }

        return 'Just now';
    }

    public function arsiv()
    {
        $news = Load::model('mod_news');
        $sql = "SELECT YEAR(MAX(created_at))  AS max_yil,YEAR(MIN(created_at))  AS min_yil FROM `mod_news` WHERE 1";
        $news->fetch_array($sql);
        $news->getResultOne();
        $this->tema->ctx_set('arsiv_yil', $news->toArray());
        $category = Load::model('mod_news_category');
        $category->where_lang_and_root($this->site['lang'], 'n');
        $category->order('parent_id, order', 'DESC');
        $category->gR();
        if ($category->getSize() !== 0) {
            $this->tema->ctx_set('archive_categories', $category->toArray());
        }


        $d['name'] = $this->lang['index']['mod_news']['6'];
        $d['title'] = $this->lang['index']['mod_news']['6'];

        $d['keywords'] = $this->lang['index']['mod_news']['6'];

        $d['description'] = $this->lang['index']['mod_news']['6'];
        $this->tema->ctx_set('d', $d);
        $this->tema->tpl('index/mod_news/arsiv');


        if (Req::is_Post('category_id') and Req::is_Post('yil') and Req::is_Post('ay')) {

            $yil = Req::Post_Safe('yil');
            $ay = Req::Post_Safe('ay');

            $category_id = Req::Post_Safe('category_id');

            if ($yil === "*") {
                $yil = NULL;
            }

            if ($ay === "*") {
                $ay = NULL;
            }


            $category_where = $category_id === "*" ? "" : " AND category_id='" . $category_id . "'";

            $this->tema->ctx_set('category_id', $category_id);

            $news->select('seo,name,created_at,summary');
            if ($yil !== NULL && $ay !== NULL) {
                $news->where(
                    "YEAR(created_at)='" . $yil . "' AND
                 MONTH(created_at) ='" . $ay . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } elseif ($yil !== NULL) {
                $news->where(
                    "YEAR(created_at)='" . $yil . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } elseif ($ay !== NULL) {
                $news->where(
                    "MONTH(created_at) ='" . $ay . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } else {
                $news->where("status = 'y' AND lang='" . $this->site['lang'] . "'" . $category_where);
            }

            $news->gR();
            if ($news->getSize() !== 0) {
                $this->tema->ctx_set('newses', $news->toArray());
            } else {
                $this->tema->ctx_set('bulunamadi', True);
            }

            // Ay ve yılı temaya gönderelim
            if ($yil !== NULL) {
                $this->tema->ctx_set('yil', $yil);
            }
            if ($ay !== NULL) {
                $this->tema->ctx_set('ay', $ay);
            }
        }
        $this->tema->tpl('index/mod_news/arsiv');
        $this->_index();
    }
}
