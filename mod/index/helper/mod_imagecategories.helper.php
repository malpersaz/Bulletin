<?php

class mod_imagecategories_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }


    public function _latest_pictures($limit, $select)
    {

        $m = Load::model('mod_imagecategories')->select($select)->where_lang_and_parent_id($this->site['lang'], 2)->limit($limit)->order('order', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return  $m->toArray();
            // $this->tema->ctx_set('latest_pictures', $m->toArray());
        }
        unset($m);
        return false;
    }

    public function gallery($category = NULL)
    {

        $r = Load::model('mod_imagecategories');

        $d = NULL;
        if ($category !== NULL) {
            if ($r->exists_seo_and_root($category, 'n') === FALSE) {

                return $this->_404();
            }
            $r->getResultOne();
            $r->sync();

            $this->tema->ctx_set('keto', $r->id);
            $d = $r->toArray();
            /* if ($this->_permission_kontrol($d ['acl'])) {
                return;
            }*/

            $this->_gallery_pictures($r->id);
        }

        if ($d === NULL) {
            $d['title'] = $this->lang['index']['mod_pictures']['1'];
            $d['description'] = $d['title'];
            $d['keywords'] = $d['title'];
        }

        $mod_resim_nested = new mod_imagecategories_model();
        //$mod_resim_nested->where_lang($this->site['lang']);
        $mod_resim_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_resim_nested->toArray(), 'parent_id', 'id');
        $this->tema->ctx_set('rkat', $select_nested_menu_result);

        $this->tema->tpl('index/mod_imagecategories/gallery');
        $this->tema->ctx_set('d', $d);
        $this->_index();
    }

    // Gallery Pictures
    public function _gallery_pictures($id, $limit = NULL)
    {
        $r = Load::model('mod_gallery');
        $r->where_mod_name_and_vid('mod_imagecategories', $id);
        if ($limit != NULL) {
            $r->limit($limit);
        }
        $r->order('id', 'DESC');
        $r->gR();
        if ($r->getSize() !== 0) {
            $this->tema->ctx_set('res', $r->toArray());
        }
        unset($r);
    }
}
