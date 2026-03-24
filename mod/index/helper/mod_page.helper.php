<?php

class mod_page_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function page($seo)
    {
        $seo = Req::Url_Clear($seo);
        $sayfa = Load::model('mod_page');
        $sayfa->where_seo_and_status_and_root_and_lang($seo, 'y', 'n', $this->site['lang']);
        $sayfa->limit(1);
        $sayfa->gRo();
        if ($sayfa->getSize() !== 0) {
            $sayfa->sync();
            $this->tema->ctx_set('d', $sayfa->toArray());
            // Permission Kontrol
            if ($this->_permission_kontrol($sayfa->acl_id)) {
                return;
            }

            $this->_parent_id_sayfalar($sayfa->id);

            if ($sayfa->category_id !== 0) {
                $this->_related_content($sayfa->category_id);
            }

            if (isset($sayfa->gallery_id) && $sayfa->gallery_id != 0) {
                $this->_gallery_resimgetir('mod_page', $sayfa->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_page', $sayfa->id);
            }

            if (isset($sayfa->file_id) && $sayfa->file_id != 0) {
                $this->_mod_attachment('mod_page', $sayfa->file_id);
            } else {
                $this->_mod_attachment('mod_page', $sayfa->id);
            }


            //print_r($sayfa);
            $this->_mod_ust_list('mod_page', $sayfa->parent_id, ['id', 'name', 'seo', 'order', 'lang'], 'order');
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_page/page');
        $this->_index();
    }
}
