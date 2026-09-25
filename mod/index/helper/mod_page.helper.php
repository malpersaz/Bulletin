<?php

class mod_page_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function _verify_image_asset($image)
    {
        if (empty($image)) {
            return null;
        }
        $fullPath = root_dir . ltrim($image, '/');
        return (file_exists($fullPath) && is_file($fullPath)) ? $image : null;
    }

    public function page($seo)
    {
        $seo = Req::Url_Clear($seo);
        $page = Load::model('mod_page');
        $page->where_seo_and_status_and_root_and_lang($seo, 'y', 'n', $this->site['lang']);
        $page->limit(1);
        $page->gRo();
        if ($page->getSize() !== 0) {
            $page->sync();
            $this->tema->ctx_set('d', $page->toArray());
            // Access permission check
            if ($this->_permission_check($page->acl_id)) {
                return;
            }

            // Increment view count (IP based)
            $this->_update_view_count('mod_page', $page->id);

            $this->_parent_id_pages($page->id);

            if ($page->category_id !== 0) {
                $this->_related_content($page->category_id);
            }

            if (isset($page->gallery_id) && $page->gallery_id != 0) {
                $this->_gallery_get_images('mod_page', $page->gallery_id);
            } else {
                $this->_gallery_get_images('mod_page', $page->id);
            }

            if (isset($page->file_id) && $page->file_id != 0) {
                $this->_mod_attachment('mod_page', $page->file_id);
            } else {
                $this->_mod_attachment('mod_page', $page->id);
            }

            $this->_mod_ust_list('mod_page', $page->parent_id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_page/page');
        $this->_index();
    }
}
