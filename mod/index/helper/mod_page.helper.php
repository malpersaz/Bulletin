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
                $this->_exact_gallery($page->gallery_id);
            } else {
                $this->_fetch_gallery_images('mod_page', $page->id);
            }

            if (isset($page->file_id) && $page->file_id != 0) {
                $this->_mod_attachment('mod_page', $page->file_id);
            } else {
                $this->_mod_attachment('mod_page', $page->id);
            }

            $this->_mod_ust_list('mod_page', $page->parent_id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
        } else {
            // Check alternate slug variations (underscore/space)
            $altSeo = str_replace(['_', '-'], ' ', $seo);
            if ($altSeo !== $seo) {
                $page->flush();
                $page->where_seo_and_status_and_root_and_lang($altSeo, 'y', 'n', $this->site['lang'])->limit(1)->gRo();
            }
            if ($page->getSize() === 0) {
                $underscoreSeo = str_replace(' ', '_', $seo);
                if ($underscoreSeo !== $seo) {
                    $page->flush();
                    $page->where_seo_and_status_and_root_and_lang($underscoreSeo, 'y', 'n', $this->site['lang'])->limit(1)->gRo();
                }
            }

            if ($page->getSize() !== 0) {
                $page->sync();
                $this->tema->ctx_set('d', $page->toArray());
                if ($this->_permission_check($page->acl_id)) {
                    return;
                }
                $this->_update_view_count('mod_page', $page->id);
                $this->_parent_id_pages($page->id);
                if ($page->category_id !== 0) {
                    $this->_related_content($page->category_id);
                }
                $this->_mod_ust_list('mod_page', $page->parent_id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
            } else {
                // Check fallback for statutory legal & cookie policy pages
                $legalSlugs = [
                    'Legal_Information', 'Agahiyên_Qanûnî', 'Agahdariya_Zagônî',
                    'زانیارییە_یاساییەکان', 'المعلومات_القانونية', 'Rechtliche_Hinweise',
                    'Informations_Légales', 'Juridisk_Information', 'Yasal_Bilgiler', 'Melumatê_Huqûqî'
                ];
                $cookieSlugs = [
                    'Cookie_Policy', 'Polîtîkaya_Çerezan', 'سیاسەتی_کووکییەکان',
                    'سياسة_ملفات_تعريف_الارتباط', 'Cookie_Richtlinie', 'Cookie-Richtlinie',
                    'Politique_de_Cookies', 'Cookiepolicy', 'Cerez_Politikasi',
                    'Çerez_Politikası', 'Siyasetê_Çerezan'
                ];

                $lang = $this->site['lang'];

                if (in_array($seo, $legalSlugs, true)) {
                    $pageData = [
                        'id' => 0,
                        'name' => isset($this->lang['index']['legal_info']) ? $this->lang['index']['legal_info'] : 'Legal Information',
                        'title' => isset($this->lang['index']['legal_info']) ? $this->lang['index']['legal_info'] : 'Legal Information',
                        'seo' => isset($this->lang['index']['legal_info_slug']) ? $this->lang['index']['legal_info_slug'] : 'Legal_Information',
                        'summary' => isset($this->lang['index']['legal_info_pending']) ? $this->lang['index']['legal_info_pending'] : '',
                        'description' => isset($this->lang['index']['legal_info_pending']) ? $this->lang['index']['legal_info_pending'] : '',
                        'content' => '',
                        'lang' => $lang,
                        'parent_id' => 0,
                        'status' => 'y',
                        'root' => 'n',
                        'image' => null,
                        'acl_id' => 0,
                        'category_id' => 0,
                        'gallery_id' => 0,
                        'file_id' => 0,
                    ];
                    $this->tema->ctx_set('d', $pageData);
                    $this->tema->ctx_set('detail', $pageData);
                    $this->tema->tpl('index/mod_page/page');
                    $this->_index();
                    return;
                }

                if (in_array($seo, $cookieSlugs, true)) {
                    $pageData = [
                        'id' => 0,
                        'name' => isset($this->lang['index']['cookie_policy']) ? $this->lang['index']['cookie_policy'] : 'Cookie Policy',
                        'title' => isset($this->lang['index']['cookie_policy']) ? $this->lang['index']['cookie_policy'] : 'Cookie Policy',
                        'seo' => isset($this->lang['index']['cookie_policy_slug']) ? $this->lang['index']['cookie_policy_slug'] : 'Cookie_Policy',
                        'summary' => isset($this->lang['index']['cookie_policy_subtitle']) ? $this->lang['index']['cookie_policy_subtitle'] : '',
                        'description' => isset($this->lang['index']['cookie_policy_subtitle']) ? $this->lang['index']['cookie_policy_subtitle'] : '',
                        'content' => '',
                        'lang' => $lang,
                        'parent_id' => 0,
                        'status' => 'y',
                        'root' => 'n',
                        'image' => null,
                        'acl_id' => 0,
                        'category_id' => 0,
                        'gallery_id' => 0,
                        'file_id' => 0,
                    ];
                    $this->tema->ctx_set('d', $pageData);
                    $this->tema->ctx_set('detail', $pageData);
                    $this->tema->tpl('index/mod_page/page');
                    $this->_index();
                    return;
                }

                return $this->_404();
            }
        }
        $this->tema->tpl('index/mod_page/page');
        $this->_index();
    }
}
