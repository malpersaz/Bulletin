<?php

class mod_video_embed_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _categories()
    {
        $mod_category_nested = Load::model('mod_video_embed_category');
        $mod_category_nested->where_lang_and_root($this->site['lang'], 'n');
        $mod_category_nested->order('order', 'ASC')->gR();
        //$select_nested_category_id_result=Helper::nested($mod_category_nested->toArray(),'parent_id','id');
        $this->tema->ctx_set('categories', $mod_category_nested->toArray());
    }

    public function video($id)
    {

        if (intval($id) === 0) {
            return $this->_404();
        }


        $this->_categories();
        $video = Load::model('mod_video_embed.videos');
        $video->where_id_and_status_and_lang($id, 'y', $this->site['lang']);
        $video->limit(1);
        $video->gRo();

        if ($video->getSize() !== 0) {
            $video->sync();
            // Permission Kontrol
            if ($this->_permission_kontrol($video->acl_id)) {
                return;
            }

            // Hejmara xwendinê zêde bike (IP Bazlı)
            $this->_update_view_count('mod_video_embed', $video->id);

            $category =
                $this->tema->ctx_set('d', $video->toArray());
            if (isset($video->gallery_id) && $video->gallery_id != 0) {
                $this->_gallery_resimgetir('mod_video_embed', $video->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_video_embed', $video->id);
            }

            if (isset($video->file_id) && $video->file_id != 0) {
                $this->_mod_attachment('mod_video_embed', $video->file_id);
            } else {
                $this->_mod_attachment('mod_video_embed', $video->id);
            }
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_video_embed/video');
        $this->_index();
        unset($video);
    }

    public function videos($category = NULL, $sayfa = 1)
    {
        $this->_categories();
        $page_limit = 80; // Sayfa Limit
        $pager_limit = 4; // Sayfalama Limiti
        $offset = ($sayfa - 1) * $page_limit;

        if (($sayfa == "") or ($sayfa == NULL)) {
            $sayfa = 1;
        }

        $sayfa = intval($sayfa); // Geçerli Sayfa


        $video = Load::model('mod_video_embed.videos');
        $category_model = Load::model('mod_video_embed_category');

        if ($category == NULL or $category == 'all') {
            $video->where_status_and_lang('y', $this->site['lang'])->all();
            $total_item = $video->getSize();
            $video->where_status_and_lang('y', $this->site['lang']);
            $video->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($video->getSize() != 0) {
                $videos = $video->toArray();
                foreach ($videos as &$v) {
                    if (isset($v['embed'])) {
                        preg_match('/<iframe.*?src="([^"]+)"/', $v['embed'], $matches);
                        $v['youtube_url'] = $matches[1] ?? '';
                    }
                }
                $this->tema->ctx_set('videos', $videos);
            }
            $category_seo = 'all';
            $categoryname = $this->lang['index']['mod_video_embed']['2'];
        } elseif ($category_model->exists_seo($category) === False) {
            return $this->_404();
        } else {
            $video->where_category_seo_and_status_and_lang($category, 'y', $this->site['lang']);

            $video->order('created_at', 'DESC')->gR();
            $total_item = $video->getSize();
            //$video->where_category_seo($category)->limit($page_limit, $offset)->gR();
            $video->getLimit($page_limit, $offset); // Belirtilen Limit Kadar Çekilen Veriden Alalım
            if ($video->getSize() !== 0) {
                $this->tema->ctx_set('videos', $video->toArray());
            }
            //print_r($video);
            // Sync Edelim
            $category->getResultOne();
            $category->sync();

            $categoryname = $category->name;
            $category_seo = $category->seo;
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
            $d['link'] = $this->site['sadres'] . 'category/' . $category . '/' . $sayfa;
        } else {
            $d['link'] = $this->site['sadres'] . 'category/' . $category;
        }

        if ($sayfa <= 1) {
            $d['title'] = sprintf($this->lang['index']['mod_video_embed']['1'], $categoryname);
        } else {
            $d['title'] = sprintf($this->lang['index']['mod_video_embed']['5'], $categoryname, $sayfa);
        }

        $d['description'] = $this->lang['index']['mod_video_embed']['2'];
        $d['keywords'] = $this->lang['index']['mod_video_embed']['3'];

        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('categoryname', $categoryname);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('sayfa', $sayfa);
        $this->tema->tpl('index/mod_video_embed/videos');
        $this->_index();
    }

    public function _latest_videos($limit, $select)
    {
        $m = Load::model('mod_video_embed')->select($select)->where_lang_and_status($this->site['lang'], 'y')->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $m->toArray();
            // $this->tema->ctx_set('latest_videos', $m->toArray());
        }
        unset($m);
        return false;
    }
}
