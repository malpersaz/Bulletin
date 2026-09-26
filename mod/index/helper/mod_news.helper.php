<?php
class mod_news_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _categories()
    {
        if (!$this->isFeatureReady('news.front')) {
            $this->tema->ctx_set('categories', []);
            return;
        }

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
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $this->_categories();
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $news = Load::model('mod_news.newses');
        $news->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $news->limit(1);
        $news->gRo();
        if ($news->getSize() !== 0) {
            $news->sync();
            if ($this->_permission_kontrol($news->acl_id)) {
                return;
            }
            $this->_update_view_count('mod_news', $news->id);

            if (isset($news->video) && $news->video != '') {
                $this->_mod_video($news->video);
            }
            if (isset($news->gallery_id) && $news->gallery_id != 0) {
                $this->_exact_gallery($news->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_news', $news->id);
            }
            if (isset($news->file_id) && $news->file_id != 0) {
                $this->_exact_attachment($news->file_id);
            } else {
                $this->_mod_attachment('mod_news', $news->id);
            }
            $cleanText = function ($str) {
                return html_entity_decode(html_entity_decode((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            };

            $news_data = $news->toArray();
            if (isset($news_data['name'])) {
                $news_data['name'] = $cleanText($news_data['name']);
            }
            if (isset($news_data['summary'])) {
                $news_data['summary'] = $cleanText($news_data['summary']);
            }
            $this->tema->ctx_set('d', $news_data);

            $news_24h_model = Load::model('mod_news');
            $news_24h_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id')
                ->where_lang_and_status($this->site['lang'], 'y');
            if (!empty($news->id)) {
                $news_24h_model->addWhere("id != '" . (int)$news->id . "'");
            }
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $news_24h_model->addWhere("created_at >= '" . $yesterday . "'");
            $news_24h_model->limit(8)->order('created_at', 'DESC');
            $news_24h_model->gR();
            $news_24h = [];
            if ($news_24h_model->getSize() !== 0) {
                $items = $news_24h_model->toArray();
                if (is_array($items)) {
                    foreach ($items as &$item) {
                        $item['name'] = $cleanText($item['name']);
                        if (isset($item['summary'])) {
                            $item['summary'] = $cleanText($item['summary']);
                        }
                        $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                        $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                        $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                        $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                    }
                    unset($item);
                    $news_24h = $items;
                }
            }
            $this->tema->ctx_set('news_24h', $news_24h);
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_news/news');
        $this->_index();
        unset($news);
    }

    public function newses($category = NULL, $page = 1)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $this->_categories();

        $page_limit = 80;
        $pager_limit = 4;
        $offset = ($page - 1) * $page_limit;

        if (($page == "") or ($page == NULL)) {
            $page = 1;
        }

        $page = intval($page);


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
                    $item['name'] = html_entity_decode(html_entity_decode((string)$item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (isset($item['summary'])) {
                        $item['summary'] = html_entity_decode(html_entity_decode((string)$item['summary'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
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
            $news->getLimit($page_limit, $offset);
            if ($news->getSize() !== 0) {
                $news_items = $news->toArray();
                foreach ($news_items as &$item) {
                    $item['name'] = html_entity_decode(html_entity_decode((string)$item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (isset($item['summary'])) {
                        $item['summary'] = html_entity_decode(html_entity_decode((string)$item['summary'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                    $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                    $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                $this->tema->ctx_set('newses', $news_items);
            }
            $category_model->where_seo($category);
            $category_model->getResultOne();
            $category_model->sync();
            $this->tema->ctx_set('category_record', $category_model->toArray());

            $categoryname = $category_model->name;
            $category_seo = $category_model->seo;
        }

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
            $d['link'] = $this->site['saddress'] . 'newses/' . $category . '/' . $page;
        } else {
            $d['link'] = $this->site['saddress'] . 'newses/' . $category;
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
        if (!$this->isFeatureReady('news.latest')) {
            return [];
        }

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
                    $item['name'] = html_entity_decode(html_entity_decode((string)$item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (isset($item['summary'])) {
                        $item['summary'] = html_entity_decode(html_entity_decode((string)$item['summary'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
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

    public function _homepage_categories($limit = 4, $select = 'image,name,seo,summary,created_at')
    {
        if (!$this->isFeatureReady('news.homepage_categories')) {
            return [];
        }

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
        if ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $db->fetch_array("PRAGMA table_info('mod_news')");
            $result = $db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === 'slider') {
                        return true;
                    }
                }
            }
            return false;
        }

        $db->fetch_array("SHOW COLUMNS FROM `mod_news` LIKE 'slider'");
        $result = $db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    private function formatRelativeTime($datetime)
    {
        return Helper::time_ago($datetime, $this->lang['index'] ?? null);
    }

    public function arsiv()
    {
        if (!$this->guardFeatureOr404('news.archive')) {
            return;
        }

        $news = Load::model('mod_news');
        $sql = "SELECT YEAR(MAX(created_at)) max_yil, YEAR(MIN(created_at)) min_yil FROM `mod_news` WHERE 1";
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
