<?php

class mod_video_embed_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _categories()
    {
        if (!$this->isFeatureReady('video_embed.front')) {
            $this->tema->ctx_set('categories', []);
            return;
        }

        $mod_category_nested = Load::model('mod_video_embed_category');
        $mod_category_nested->where_lang_and_root($this->site['lang'], 'n');
        $mod_category_nested->order('order', 'ASC')->gR();
        $this->tema->ctx_set('categories', $this->normalizeCategoryRows($mod_category_nested->toArray()));
    }

    public function video($id)
    {
        if (!$this->guardFeatureOr404('video_embed.front')) {
            return;
        }

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
            if ($this->_permission_kontrol($video->acl_id)) {
                return;
            }

            $this->_update_view_count('mod_video_embed', $video->id);

            $this->tema->ctx_set('d', $this->normalizeVideoRow($video->toArray()));
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
            $localVideo = Load::model('mod_video');
            $localVideo->where_id_and_status_and_lang($id, 'y', $this->site['lang']);
            $localVideo->limit(1);
            $localVideo->gRo();

            if ($localVideo->getSize() !== 0) {
                $localVideo->sync();
                if ($this->_permission_kontrol($localVideo->acl_id)) {
                    return;
                }

                $this->_update_view_count('mod_video', $localVideo->id);

                $video_path = $this->site['iaddress'] . 'mod_video/flv/' . $localVideo->flv;
                
                $poster_attr = '';
                if (!empty($localVideo->image)) {
                    $poster_path = $this->site['iaddress'] . 'mod_video/image/' . $localVideo->image;
                    $poster_attr = ' poster="' . $poster_path . '"';
                }

                // Build modern, clean HTML5 video player instead of standard YouTube iframe embed
                $embed_code = '<video class="w-100 rounded shadow" controls preload="metadata"' . $poster_attr . ' style="max-height: 500px; background: #000;">';
                $embed_code .= '<source src="' . $video_path . '" type="video/mp4" />';
                $embed_code .= '</video>';

                $d = [
                    'id' => $localVideo->id,
                    'name' => html_entity_decode($localVideo->name, ENT_QUOTES, 'UTF-8'),
                    'embed' => $embed_code,
                    'content' => isset($localVideo->description) ? html_entity_decode($localVideo->description, ENT_QUOTES, 'UTF-8') : '',
                    'deck' => 'n',
                    'summary' => '',
                    'categoryname' => $this->lang['index']['mod_video_embed']['2'] ?? 'Vîdyo',
                    'category_seo' => 'all',
                    'created_at' => $localVideo->created_at,
                    'is_local' => true,
                    'image' => $localVideo->image
                ];

                $this->tema->ctx_set('d', $d);
                $this->tema->ctx_set('gallery', []);
                $this->tema->ctx_set('attachment', []);
            } else {
                return $this->_404();
            }
        }
        $this->tema->tpl('index/mod_video_embed/video');
        $this->_index();
        unset($video);
    }

    public function videos($category = NULL, $page = 1)
    {
        if (!$this->guardFeatureOr404('video_embed.front')) {
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
                unset($v);
                $this->tema->ctx_set('videos', $this->normalizeVideoRows($videos));
            }
            $category_seo = 'all';
            $categoryname = $this->lang['index']['mod_video_embed']['2'];
        } elseif ($category_model->exists_seo($category) === False) {
            return $this->_404();
        } else {
            $video->where_category_seo_and_status_and_lang($category, 'y', $this->site['lang']);

            $video->order('created_at', 'DESC')->gR();
            $total_item = $video->getSize();
            $video->getLimit($page_limit, $offset);
            if ($video->getSize() !== 0) {
                $this->tema->ctx_set('videos', $this->normalizeVideoRows($video->toArray()));
            }
            $category_model->where_seo($category);
            $category_model->getResultOne();
            $category_model->sync();

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
            $d['link'] = $this->site['saddress'] . 'category/' . $category . '/' . $page;
        } else {
            $d['link'] = $this->site['saddress'] . 'category/' . $category;
        }

        if ($page <= 1) {
            $d['title'] = sprintf($this->lang['index']['mod_video_embed']['1'], $categoryname);
        } else {
            $d['title'] = sprintf($this->lang['index']['mod_video_embed']['5'], $categoryname, $page);
        }

        $d['description'] = $this->lang['index']['mod_video_embed']['2'];
        $d['keywords'] = $this->lang['index']['mod_video_embed']['3'];

        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('categoryname', $categoryname);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_video_embed/videos');
        $this->_index();
    }

    public function _latest_videos($limit, $select)
    {
        if (!$this->isFeatureReady('video_embed.latest')) {
            return [];
        }

        $m = Load::model('mod_video_embed')->select($select)->where_lang_and_status($this->site['lang'], 'y')->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $this->normalizeVideoRows($m->toArray());
        }
        unset($m);
        return [];
    }

    private function normalizeVideoRows(array $rows)
    {
        foreach ($rows as &$row) {
            $row = $this->normalizeVideoRow($row);
        }
        unset($row);

        return $rows;
    }

    private function normalizeVideoRow(array $row)
    {
        foreach (['name', 'title', 'keywords', 'description', 'categoryname', 'category_name'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = html_entity_decode($row[$field], ENT_QUOTES, 'UTF-8');
            }
        }

        return $row;
    }

    private function normalizeCategoryRows(array $rows)
    {
        foreach ($rows as &$row) {
            if (isset($row['name']) && is_string($row['name'])) {
                $row['name'] = html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8');
            }
        }
        unset($row);

        return $rows;
    }
}
