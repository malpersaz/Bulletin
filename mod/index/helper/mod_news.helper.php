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
        $content_model = Load::model('mod_news.contents');
        
        if (is_array($categories)) {
            foreach ($categories as &$kat) {
                $content_model->flush();
                $kat['content_count'] = $content_model->where_category_seo_and_status_and_lang($kat['seo'], 'y', $this->site['lang'])->getCount();
            }
        }
        
        $this->tema->ctx_set('categories', $categories);
    }
    public function content($seo)
    {
        $this->_categories();
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $content = Load::model('mod_news.contents');
        $content->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $content->limit(1);
        $content->gRo();
        if ($content->getSize() !== 0) {
            // Senkronize Edelim
            $content->sync();
            // Permission Kontrol
            if ($this->_permission_kontrol($content->acl_id)) {
                return;
            }
            // Bakalım Video varmı : $video olarak gönderilecek
            if (isset($content->video) && $content->video != '') {
                $this->_mod_video($content->video);
            }
            // Bakalım Resim Varmı : $res olarak gönderilecek
            if (isset($content->gallery_id) && $content->gallery_id != 0) {
                $this->_gallery_resimgetir('mod_news', $content->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_news', $content->id);
            }
            // Bakalım Dosya Varmı : $file olarak gönderilecek
            if (isset($content->file_id) && $content->file_id != 0) {
                $this->_mod_attachment('mod_news', $content->file_id);
            } else {
                $this->_mod_attachment('mod_news', $content->id);
            }
            // Sonucu Temaya Gönder
            $this->tema->ctx_set('d', $content->toArray());
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_news/news');
        $this->_index();
        unset($content);
    }

    public function contents($category = NULL, $sayfa = 1)
    {
        $this->_categories();

        $page_limit = 80; // Sayfa Limit
        $pager_limit = 4; // Sayfalama Limiti
        $offset = ($sayfa - 1) * $page_limit;

        if (($sayfa == "") or ($sayfa == NULL)) {
            $sayfa = 1;
        }

        $sayfa = intval($sayfa); // Geçerli Sayfa


        $content = Load::model('mod_news.contents');
        $category_model = Load::model('mod_news_category');

        if ($category == NULL or $category == 'all') {
            $content->where_status_and_lang('y', $this->site['lang'])->all();
            $total_item = $content->getSize();
            $content->where_status_and_lang('y', $this->site['lang']);
            $content->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($content->getSize() != 0) {
                $this->tema->ctx_set('contents', $content->toArray());
            }
            $category_seo = 'all';
            $categoryname = $this->lang['index']['mod_news']['2'];
        } elseif ($category_model->exists_seo($category) === False) {
            return $this->_404();
        } else {
            $content->where_category_seo_and_status_and_lang($category, 'y', $this->site['lang']);
            $content->order('created_at', 'DESC')->gR();
            $total_item = $content->getSize();
            //$content->where_category_seo($category)->limit($page_limit, $offset)->gR();
            $content->getLimit($page_limit, $offset); // Belirtilen Limit Kadar Çekilen Veriden Alalım
            if ($content->getSize() !== 0) {
                $this->tema->ctx_set('contents', $content->toArray());
            }
            //print_r($content);
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

    public function _latest_content($limit, $select)
    {
        $m = Load::model('mod_news')->select($select)->where_lang_and_status($this->site['lang'], 'y')->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $m->toArray();
        }
    }

    public function arsiv()
    {
        $content = Load::model('mod_news');
        $sql = "SELECT YEAR(MAX(created_at))  AS max_yil,YEAR(MIN(created_at))  AS min_yil FROM `mod_news` WHERE 1";
        $content->fetch_array($sql);
        $content->getResultOne();
        $this->tema->ctx_set('arsiv_yil', $content->toArray());
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

            $content->select('seo,name,created_at,summary');
            if ($yil !== NULL && $ay !== NULL) {
                $content->where(
                    "YEAR(created_at)='" . $yil . "' AND
                 MONTH(created_at) ='" . $ay . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } elseif ($yil !== NULL) {
                $content->where(
                    "YEAR(created_at)='" . $yil . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } elseif ($ay !== NULL) {
                $content->where(
                    "MONTH(created_at) ='" . $ay . "' AND
                lang='" . $this->site['lang'] . "' AND
                status = 'y'" . $category_where
                );
            } else {
                $content->where("status = 'y' AND lang='" . $this->site['lang'] . "'" . $category_where);
            }

            $content->gR();
            if ($content->getSize() !== 0) {
                $this->tema->ctx_set('contents', $content->toArray());
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
