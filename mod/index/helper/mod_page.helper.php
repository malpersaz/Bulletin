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
        $rawSeo = trim((string)$seo);
        $decodedSeo = html_entity_decode(rawurldecode($rawSeo), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $seo = trim($decodedSeo);

        $page = Load::model('mod_page');
        $page->where_seo_and_status_and_root_and_lang($seo, 'y', 'n', $this->site['lang']);
        $page->limit(1);
        $page->gRo();
        if ($page->getSize() !== 0) {
            $page->sync();

            // Strict canonical slug comparison to prevent non-canonical collation / casing URLs
            if (PHP_SAPI !== 'cli' && !headers_sent() && (string)$page->seo !== $seo) {
                $targetUrl = rtrim((string) $this->site['saddress'], '/') . '/page/' . $page->seo;
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . Helper::sitemap_url($targetUrl));
                exit;
            }

            $pageData = $this->enrichRowWithSocialPosts('mod_page', (int) $page->id, $page->toArray());
            $this->tema->ctx_set('d', $pageData);
            $this->tema->ctx_set('detail', $pageData);
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

            $pageId = (int) $page->id;
            $parentId = (int) $page->parent_id;
            $this->_mod_faq('mod_page', $pageId);
            $this->_mod_ust_list('mod_page', $parentId, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
            $this->_mod_parent_id_list('mod_page', $pageId, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
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

                // 301 redirect to canonical slug
                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    $targetUrl = rtrim((string) $this->site['saddress'], '/') . '/page/' . $page->seo;
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . Helper::sitemap_url($targetUrl));
                    exit;
                }

                $pageData = $this->enrichRowWithSocialPosts('mod_page', (int) $page->id, $page->toArray());
                $this->tema->ctx_set('d', $pageData);
                $this->tema->ctx_set('detail', $pageData);
                if ($this->_permission_check($page->acl_id)) {
                    return;
                }
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

                $pageId = (int) $page->id;
                $parentId = (int) $page->parent_id;
                $this->_mod_faq('mod_page', $pageId);
                $this->_mod_ust_list('mod_page', $parentId, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
                $this->_mod_parent_id_list('mod_page', $pageId, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
            } else {
                // Inbound fallback: match requested ASCII slug against published pages
                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    $requestedKey = Helper::ascii_slug_key($seo);
                    if ($requestedKey !== '') {
                        $db = Load::lib('Database');
                        $safeLang = addslashes((string)$this->site['lang']);
                        $db->fetch_array("SELECT id, seo FROM `mod_page` WHERE `lang` = '{$safeLang}' AND `status` = 'y' AND `root` = 'n'");
                        $candidateRows = $db->toArray();
                        $matchedSeo = null;
                        $matchCount = 0;
                        if (is_array($candidateRows)) {
                            foreach ($candidateRows as $candidateRow) {
                                if (Helper::ascii_slug_key((string)$candidateRow['seo']) === $requestedKey) {
                                    $matchedSeo = (string)$candidateRow['seo'];
                                    $matchCount++;
                                }
                            }
                        }

                        if ($matchCount === 1 && $matchedSeo !== null) {
                            $targetUrl = rtrim((string) $this->site['saddress'], '/') . '/page/' . $matchedSeo;
                            header("HTTP/1.1 301 Moved Permanently");
                            header("Location: " . Helper::sitemap_url($targetUrl));
                            exit;
                        }
                    }
                }

                return $this->_404();
            }
        }
        $this->tema->tpl('index/mod_page/page');
        $this->_index();
    }
}
