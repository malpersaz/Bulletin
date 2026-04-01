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
            // Bakalım Video varmı : $video olarak gönderilecek
            if (isset($news->video) && $news->video != '') {
                $this->_mod_video($news->video);
            }
            // Bakalım Resim Varmı : $res olarak gönderilecek
            if (isset($news->gallery_id) && $news->gallery_id != 0) {
                $this->_gallery_resimgetir('mod_news', $news->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_news', $news->id);
            }
            // Bakalım Dosya Varmı : $file olarak gönderilecek
            if (isset($news->file_id) && $news->file_id != 0) {
                $this->_mod_attachment('mod_news', $news->file_id);
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

    public function newses($category = NULL, $sayfa = 1)
    {
        $this->_categories();

        $page_limit = 80; // Sayfa Limit
        $pager_limit = 4; // Sayfalama Limiti
        $offset = ($sayfa - 1) * $page_limit;

        if (($sayfa == "") or ($sayfa == NULL)) {
            $sayfa = 1;
        }

        $sayfa = intval($sayfa); // Geçerli Sayfa


        $news = Load::model('mod_news.newses');
        $category_model = Load::model('mod_news_category');

        if ($category == NULL or $category == 'all') {
            $news->where_status_and_lang('y', $this->site['lang'])->all();
            $total_item = $news->getSize();
            $news->where_status_and_lang('y', $this->site['lang']);
            $news->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($news->getSize() != 0) {
                $this->tema->ctx_set('newses', $news->toArray());
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
            $news->getLimit($page_limit, $offset); // Belirtilen Limit Kadar Çekilen Veriden Alalım
            if ($news->getSize() !== 0) {
                $this->tema->ctx_set('newses', $news->toArray());
            }
            //print_r($news);
            // Sync Edelim
            $category_model->where_seo($category);
            $category_model->getResultOne();
            $category_model->sync();

            $categoryname = $category_model->name;
            $category_seo = $category_model->seo;
        }

        // Sayfalama
        $p = Load::lib('Pager');
        $p->total_item = $total_item;
        $p->page_limit = $page_limit;
        $p->pager_limit = $pager_limit;
        $p->page = $sayfa;


        if ($p->check() === FALSE) {
            return $this->_404();
        }

        if ($p->Result() !== FALSE) {
            $this->tema->ctx_set('sayfalama', $p->Result());
        }

        if ($sayfa != '1') {
            $d['link'] = $this->site['sadres'] . 'newses/' . $category . '/' . $sayfa;
        } else {
            $d['link'] = $this->site['sadres'] . 'newses/' . $category;
        }

        if ($sayfa <= 1) {
            $d['title'] = sprintf($this->lang['index']['mod_news']['1'], $categoryname);
        } else {
            $d['title'] = sprintf($this->lang['index']['mod_news']['5'], $categoryname, $sayfa);
        }

        $d['description'] = $this->lang['index']['mod_news']['2'];
        $d['keywords'] = $this->lang['index']['mod_news']['3'];

        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('categoryname', $categoryname);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('sayfa', $sayfa);
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
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                unset($item);
            }
            return $items;
        }

        return [];
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
