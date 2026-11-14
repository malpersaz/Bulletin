<?php

abstract class indexBase
{
    use PublicHelperTrait;

    public $url;
    public $tema;
    public $theme;
    public $view;
    public $site;
    public $lang;
    public $session;
    public $db;
    public $user;
    public $val;
    public $mod;
    public $model;
    public $secenek;
    public $sec;
    public $options;
    public $option;

    public function __get($name)
    {
        if ($name === 'theme' || $name === 'view') {
            return $this->tema;
        }
        if ($name === 'tema') {
            return $this->theme ?? $this->view;
        }
        if ($name === 'options' || $name === 'option') {
            return $this->secenek ?? $this->sec;
        }
        if ($name === 'secenek' || $name === 'sec') {
            return $this->options ?? $this->option;
        }
        return null;
    }

    private function hasConfiguredDatabase()
    {
        $database = Registry::get('database');
        $dbConfig = Registry::get('db_config');

        if (!is_string($database) || $database === '') {
            return false;
        }

        if (!is_array($dbConfig) || !isset($dbConfig[$database]) || !is_array($dbConfig[$database])) {
            return false;
        }

        return isset($dbConfig[$database]['adapter'], $dbConfig[$database]['conf']) && is_array($dbConfig[$database]['conf']);
    }

    public function __construct()
    {
        $this->url = Load::lib('Url');
        $this->tema = Load::lib('TwigTemplate');
        $this->theme = $this->tema;
        $this->view = $this->tema;
        $this->site = Registry::get('site_config');
        $this->_get_site_settings();
        $this->tema->setGlobal('site', $this->site);
        $this->lang = Load::lang('index');
        $this->tema->ctx_set('tarih', Helper::format_date(time()));
        $this->tema->lang($this->lang);
        $this->session = Load::lib('Session');
    }

    public function _index()
    {
        $this->_site();
    }

    public function _getAcl($acl_id)
    {
        Cache::$ttl = 86400;
        if (Cache::exists("permission-acl-" . $acl_id)) {
            return Cache::get("permission-acl-" . $acl_id);
        }
        $acl = Load::model('mod_acl');
        $acl->where_id($acl_id)->gRo();
        if ($acl->getSize() === 0) {
            return FALSE;
        }
        $acl->sync();
        $unserializedAcl = @unserialize($acl->acl, ['allowed_classes' => false]);
        Cache::set($unserializedAcl, "permission-acl-" . $acl_id);
        return $unserializedAcl;
    }


    public function _fetch_gallery_images($mod, $vid)
    {
        $gallery = Load::model('mod_gallery');
        $gallery->where_mod_name_and_vid($mod, $vid);
        $gallery->select('id,url,name,mod_name')->order('id', 'DESC');
        $gallery->gR();

        if ($gallery->getSize() !== 0) {
            $gallery_array = $gallery->toArray();
            $this->tema->ctx_set('gallery', $gallery_array);
            $this->tema->ctx_set('images', $gallery_array);
            $this->tema->ctx_set('res', $gallery_array);
        }
        unset($gallery);
    }

    public function _gallery_get_images($mod, $vid)
    {
        return $this->_fetch_gallery_images($mod, $vid);
    }

    public function _gallery_resimgetir($mod, $vid)
    {
        return $this->_fetch_gallery_images($mod, $vid);
    }

    private function _normalizeAttachmentRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }
            $row['raw_original_name'] = $row['original_name'] ?? '';
            $displayName = (!empty($row['filename'])) ? $row['filename'] : ($row['original_name'] ?? '');
            $row['display_name'] = $displayName;
            $row['original_name'] = $displayName;
        }
        unset($row);
        return $rows;
    }

    public function _mod_attachment($mod, $vid)
    {
        $file = Load::model('mod_attachment');
        $file->where_mod_name_and_vid($mod, $vid);
        $file->select('id,url,original_name,filename')->order('id', 'DESC');
        $file->gR();

        if ($file->getSize() !== 0) {
            $file_array = $this->_normalizeAttachmentRows($file->toArray());
            $this->tema->ctx_set('file', $file_array);
            $this->tema->ctx_set('files', $file_array);
        }
        unset($file);
    }

    public function _exact_attachment($id)
    {
        $file = Load::model('mod_attachment');
        $file->where_id($id);
        $file->select('id,url,original_name,filename')->gRo();
        
        if ($file->getSize() !== 0) {
            $file_array = $this->_normalizeAttachmentRows([$file->toArray()]);
            $this->tema->ctx_set('file', $file_array);
            $this->tema->ctx_set('files', $file_array);
        }
        unset($file);
    }

    public function _exact_gallery($id)
    {
        $this->_fetch_gallery_images('mod_gallery_mod', $id);
        $currentGallery = $this->tema->ctx_get('gallery');
        if (empty($currentGallery)) {
            $gallery = Load::model('mod_gallery');
            $gallery->where_id($id);
            $gallery->select('id,url,name,mod_name')->gRo();

            if ($gallery->getSize() !== 0) {
                $gallery_items = [$gallery->toArray()];
                $this->tema->ctx_set('gallery', $gallery_items);
                $this->tema->ctx_set('images', $gallery_items);
                $this->tema->ctx_set('res', $gallery_items);
            }
            unset($gallery);
        }
    }

    public function _mod_faq($mod, $vid)
    {
        try {
            $faq = Load::model('mod_faq');
            $faq->where_mod_name_and_vid_and_lang_and_status($mod, (int) $vid, $this->site['lang'], 'y');
            $faq->select('id,lang,mod_name,vid,question,answer,sort_order,status')->order('sort_order', 'ASC')->order('id', 'ASC');
            $faq->gR();

            $faq_lang = Load::lang('mod_faq');
            $this->tema->ctx_set('faq_lang', $faq_lang);

            if ($faq->getSize() !== 0) {
                $faq_array = $faq->toArray();
                $this->tema->ctx_set('faqs', $faq_array);
                $this->tema->ctx_set('faq', $faq_array);
                unset($faq);
                return $faq_array;
            }

            $this->tema->ctx_set('faqs', []);
            $this->tema->ctx_set('faq', []);
            unset($faq);
            return [];
        } catch (\Throwable $e) {
            error_log('Amed Framework Notice: FAQ query failed or table does not exist: ' . $e->getMessage());
            $this->tema->ctx_set('faqs', []);
            $this->tema->ctx_set('faq', []);
            return [];
        }
    }

    /**
     * Checks once per request whether the social post bridge tables exist.
     */
    private function hasSocialPostTables(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $db = Load::lib('Database');
            $adapter = strtoupper((string) $db->getAdapterType());
            foreach (['mod_socialmedia_post', 'mod_socialmedia_post_link'] as $tbl) {
                if (strpos($adapter, 'PDO_SQLITE') !== false) {
                    $db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . addslashes($tbl) . "' LIMIT 1");
                } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                    $db->fetch_array("SELECT tablename FROM pg_tables WHERE schemaname = current_schema() AND tablename='" . addslashes($tbl) . "' LIMIT 1");
                } else {
                    $db->fetch_array("SHOW TABLES LIKE '" . addslashes($tbl) . "'");
                }
                if ($db->getSize() === 0) {
                    $ready = false;
                    return false;
                }
            }
            $ready = true;
            return true;
        } catch (\Throwable $e) {
            $ready = false;
            return false;
        }
    }

    /**
     * Fetches published social posts bound to a host module record and exposes them to Twig.
     */
    public function _mod_socialmedia_post(string $modName, int $vid): array
    {
        $modName = preg_replace('/[^a-z0-9_]/i', '', trim($modName));
        if ($modName === '' || $vid <= 0 || !$this->hasSocialPostTables()) {
            $this->tema->ctx_set('social_posts', []);
            return [];
        }

        try {
            $db = Load::lib('Database');
            SocialPostProviderService::ensureUtf8mb4Connection($db);
            $aclCondition = method_exists($this, 'getAllowedAclCondition')
                ? $this->getAllowedAclCondition('p', 'acl_id')
                : 'p.acl_id = 0';
            $sql = "SELECT p.id, p.lang, p.provider, p.media_type, p.post_id, p.url, p.embed_html, p.name, p.handle, p.content, p.image, p.status, p.acl_id, l.sort_order"
                . " FROM mod_socialmedia_post p"
                . " INNER JOIN mod_socialmedia_post_link l ON l.socialmedia_post_id = p.id"
                . " WHERE l.mod_name = '" . addslashes($modName) . "'"
                . " AND l.vid = " . (int) $vid
                . " AND p.status = 'y'"
                . " AND " . $aclCondition
                . " ORDER BY l.sort_order ASC, l.id ASC";
            $db->fetch_array($sql);
            $rows = $db->getSize() > 0 ? $db->toArray() : [];
            if (!is_array($rows)) {
                $rows = [];
            }
            $this->tema->ctx_set('social_posts', $rows);
            return $rows;
        } catch (\Throwable $e) {
            error_log('Amed Framework Notice: Social post query failed: ' . $e->getMessage());
            $this->tema->ctx_set('social_posts', []);
            return [];
        }
    }

    /**
     * Ensures a 1200x630 JPEG companion image exists for social crawlers (WhatsApp/Meta/X)
     * and populates og_image_url, og_image_mime, og_image_width, and og_image_height on the detail row.
     */
    public function enrichRowWithOpenGraphImage(string $modName, array $row): array
    {
        $imageField = trim((string) ($row['image'] ?? ''));
        if ($imageField === '' || $imageField === '#' || !class_exists('ImageService')) {
            return $row;
        }

        $imageBaseUrl = (string) ($this->site['iaddress'] ?? '');
        $ogMeta = ImageService::ensureOpenGraphJpeg($modName, $imageField, $imageBaseUrl);
        if (is_array($ogMeta) && !empty($ogMeta['url'])) {
            $row['og_image_url'] = (string) $ogMeta['url'];
            $row['og_image_mime'] = (string) ($ogMeta['mime'] ?? 'image/jpeg');
            $row['og_image_width'] = (int) ($ogMeta['width'] ?? ImageService::OG_IMAGE_WIDTH);
            $row['og_image_height'] = (int) ($ogMeta['height'] ?? ImageService::OG_IMAGE_HEIGHT);
        }

        return $row;
    }

    /**
     * Populates rendered_content with inline social figures while stripping tokens from content.
     */
    public function enrichRowWithSocialPosts(string $modName, int $vid, array $row): array
    {
        $row = $this->enrichRowWithOpenGraphImage($modName, $row);
        $boundRows = $this->_mod_socialmedia_post($modName, $vid);
        $uiLabels = is_array($this->lang['index']['mod_socialmedia_post'] ?? null)
            ? $this->lang['index']['mod_socialmedia_post']
            : [];
        $rawContent = (string) ($row['content'] ?? '');
        $row['rendered_content'] = SocialPostProviderService::renderTokens($rawContent, $boundRows, $uiLabels, true);
        $row['content'] = SocialPostProviderService::stripTokens($rawContent);
        return $row;
    }

    public function _mod_video($v)
    {
        $this->tema->ctx_set('video', []);

        if (empty($v) || !is_string($v)) {
            return;
        }
        $data = @unserialize($v, ['allowed_classes' => false]);
        if ($data === false && $v !== 'b:0;') {
            error_log("Amed Framework Warning: Failed to unserialize video data: " . $v);
            return;
        }
        if (!is_array($data) || empty($data)) {
            return;
        }

        $orderedRefs = [];
        $video_ids = [];
        $vid_ids = [];

        foreach ($data as $prefixed_id) {
            if (!is_string($prefixed_id) && !is_numeric($prefixed_id)) {
                continue;
            }
            $parts = explode(':', (string) $prefixed_id, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $type = trim($parts[0]);
            $id = (int) trim($parts[1]);
            if ($id <= 0) {
                continue;
            }
            if ($type === 'video') {
                $orderedRefs[] = ['type' => 'video', 'id' => $id];
                $video_ids[$id] = $id;
            } elseif ($type === 'vid') {
                $orderedRefs[] = ['type' => 'vid', 'id' => $id];
                $vid_ids[$id] = $id;
            }
        }

        $video_ids = array_values($video_ids);
        $vid_ids = array_values($vid_ids);
        if (empty($video_ids) && empty($vid_ids)) {
            return;
        }

        $aclCondition = method_exists($this, 'getAllowedAclCondition')
            ? $this->getAllowedAclCondition('', 'acl_id')
            : 'acl_id = 0';

        $localById = [];
        if (!empty($video_ids)) {
            $video_model = Load::model('mod_video');
            $video_model->where_id($video_ids)
                ->addWhere("status = 'y'")
                ->addWhere($aclCondition)
                ->gR();
            if ($video_model->getSize() > 0) {
                foreach ($video_model->toArray() as $lRow) {
                    $localId = (int) ($lRow['id'] ?? 0);
                    if ($localId > 0 && in_array($localId, $video_ids, true)) {
                        $localById[$localId] = $lRow;
                    }
                }
            }
        }

        $embedById = [];
        if (!empty($vid_ids)) {
            $vid_model = Load::model('mod_video_embed');
            $vid_model->select('id, name, original, duration, created_at, embed, content, description, image, acl_id');
            $vid_model->where_id($vid_ids)
                ->addWhere("status = 'y'")
                ->addWhere($aclCondition)
                ->gR();
            if ($vid_model->getSize() > 0) {
                $siteLang = (string) ($this->site['lang'] ?? 'en');
                $baseImgUrl = (string) ($this->site['iaddress'] ?? '');
                foreach ($vid_model->toArray() as $vRow) {
                    $embedId = (int) ($vRow['id'] ?? 0);
                    if ($embedId <= 0 || !in_array($embedId, $vid_ids, true)) {
                        continue;
                    }
                    $enriched = VideoEmbedProviderService::enrichVideoRowForFrontend($vRow, $siteLang);
                    if (!empty($enriched['image'])) {
                        $imgName = basename((string) $enriched['image']);
                        $fullPosterFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . $imgName;
                        $thumbPosterFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $imgName;
                        if (is_file($fullPosterFile)) {
                            $enriched['hero_poster_url'] = $baseImgUrl . 'mod_video_embed/' . $imgName;
                        } elseif (is_file($thumbPosterFile)) {
                            $enriched['hero_poster_url'] = $baseImgUrl . 'mod_video_embed/thumb/' . $imgName;
                        }
                    }
                    $embedById[$embedId] = $enriched;
                }
            }
        }

        $results = [];
        $seenLocalIds = [];
        $seenEmbedKeys = [];
        foreach ($orderedRefs as $ref) {
            $refId = $ref['id'];
            if ($ref['type'] === 'video') {
                if (!isset($localById[$refId]) || isset($seenLocalIds[$refId])) {
                    continue;
                }
                $seenLocalIds[$refId] = true;
                $results[] = $localById[$refId];
            } else {
                if (!isset($embedById[$refId])) {
                    continue;
                }
                $enriched = $embedById[$refId];
                if (!empty($enriched['provider']) && !empty($enriched['provider_video_id'])) {
                    $dedupeKey = $enriched['provider'] . ':' . $enriched['provider_video_id'];
                } elseif (!empty($enriched['original'])) {
                    $dedupeKey = 'orig:' . trim((string) $enriched['original']);
                } elseif (!empty($enriched['embed'])) {
                    $dedupeKey = 'embed:' . trim((string) $enriched['embed']);
                } else {
                    $dedupeKey = 'id:' . (int) ($enriched['id'] ?? 0);
                }
                if (isset($seenEmbedKeys[$dedupeKey])) {
                    continue;
                }
                $seenEmbedKeys[$dedupeKey] = true;
                $results[] = $enriched;
            }
        }

        foreach ($results as &$row) {
            if (isset($row['name'])) {
                $row['name'] = html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8');
            }
        }
        unset($row);

        $this->tema->ctx_set('video', $results);
    }

    public function _mod_ust_list($mod, $id, $select, $order)
    {
        if ($id == 0) {
            return false;
        }
        $list = Load::model($mod);
        $list->flush();
        $safeId = (int) $id;
        $list->addWhere("(id = '{$safeId}' OR parent_id = '{$safeId}')");
        $list->addWhere("status = 'y'");

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {
            $this->tema->ctx_set('list', $list->toArray());
            $this->tema->ctx_set('list_root', $id);
        }
        unset($list);
    }

    public function _mod_parent_id_list($mod, $id, $select, $order)
    {
        if ($id == 0) {
            return false;
        }
        $list = Load::model($mod);
        $list->flush();
        $safeId = (int) $id;
        $list->addWhere("parent_id = '{$safeId}'");
        $list->addWhere("status = 'y'");

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {
            $this->tema->ctx_set('parent_id_list', $list->toArray());
            $this->tema->ctx_set('parent_id_list_root', $id);
        }
        unset($list);
    }

    public function _site()
    {
    }

    private function _get_site_settings()
    {
        $current_lang = $this->site['lang'] ?? 'tr';

        if (!$this->hasConfiguredDatabase()) {
            $this->site['social_links'] = [];
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($current_lang)->gRo();

        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $settings_data = $settings_model->getResultOne()->toArray();

            foreach ($settings_data as $key => $value) {
                $final_key = ($key === 'email') ? 'mail' : $key;
                $this->site[$final_key] = $value;
            }

            $activeTemplate = !empty($this->site['app_template']) ? basename(Req::Clear($this->site['app_template'])) : 'amed';
            $fullTemplatePath = template_dir . $activeTemplate;
            if (!file_exists($fullTemplatePath) || !is_dir($fullTemplatePath)) {
                $activeTemplate = 'amed';
            }
            $this->site['app_template'] = $activeTemplate;
            Registry::set('app_template', $activeTemplate);
        }

        Load::modelfile('mod_settings.mod_settings_social');
        $social_model = new mod_settings_social_model();
        $social_model->where_is_active('y')->order('display_order', 'ASC')->gR();
        $this->site['social_links'] = ($social_model->getSize() > 0) ? $social_model->toArray() : [];
    }

    protected function isFeatureReady($feature)
    {
        $runtime = Load::lib('ModuleRuntime');
        return $runtime->isReady($feature);
    }

    protected function getFeatureFailureReason($feature)
    {
        $runtime = Load::lib('ModuleRuntime');
        return $runtime->getFailureReason($feature);
    }

    protected function guardFeatureOr404($feature)
    {
        $runtime = Load::lib('ModuleRuntime');
        if (!$runtime->requireReady($feature, [
            'handler' => get_class($this),
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'lang' => isset($this->site['lang']) ? $this->site['lang'] : null,
        ])) {
            $this->_404();
            return false;
        }

        return true;
    }

    public function _parent_id_pages($id)
    {
        Load::modelfile('mod_page');
        $sub_page = new mod_page_model();
        $sub_page->where_parent_id_and_status_and_root_and_lang($id, 'y', 'n', $this->site['lang']);
        $sub_page->select('name,summary,seo');
        $sub_page->order('order', 'ASC');
        $sub_page->gR();
        if ($sub_page->getSize() !== 0) {
            $this->tema->ctx_set('sub_pages', $sub_page->toArray());
        }
        unset($sub_page);
    }

    public function _get_category_news($category_seo, $limit = 5)
    {
        $news = Load::model('mod_news.newses');
        $news->where_category_seo_and_status_and_lang($category_seo, 'y', $this->site['lang']);
        $news->addWhere($this->getPublishedDateCondition(''));
        $news->order('created_at', 'DESC');
        $news->limit($limit);
        $news->gR();
        
        if ($news->getSize() !== 0) {
            $items = $this->enrichTimestampRows($news->toArray());
            foreach ($items as &$item) {
                $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
            }
            return $items;
        }
        return [];
    }


    public function _related_images($id)
    {
        if ($id == 0) {
            return;
        }
        $r = Load::model('mod_imagecategories');
        $r->select('name,seo,description');
        $r->where_parent_id_and_lang_and_root($id, $this->site['lang'], 'n');
        $r->order('order', 'DESC');
        $r->gR();
        if ($r->getSize() !== 0) {
            $this->tema->ctx_set('pictures', $r->toArray());
        }
        unset($r);
    }


    public function _permission_check($acl_id)
    {
        if ($acl_id === 0) {
            return false;
        }
        $acl = $this->_getAcl($acl_id);

        if ($acl === false) {
            return false;
        }

        $this->session = Load::lib('Session');
        $group = $this->session->get('group_id') !== false ? $this->session->get('group_id') : 0;
        if (!in_array($group, $acl)) {
            $this->tema->tpl('index/register');
            $this->_index();

            return true;
        }

        return false;
    }

    public function _permission_kontrol($acl_id)
    {
        return $this->_permission_check($acl_id);
    }




    public function _404()
    {
        require_once(lib_dir . 'AmedError.class.php');
        $link = (is_object($this->url) && isset($this->url->link)) ? $this->url->link : ($_SERVER['REQUEST_URI'] ?? '');
        AmedError::error('not', Req::Clear($link));
    }

    protected function resolveClientIp()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->isPublicIp($remoteAddr)) {
            return $remoteAddr;
        }

        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            foreach (explode(',', $_SERVER[$header]) as $candidate) {
                $candidate = trim($candidate);

                if ($this->isPublicIp($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->normalizeIp($remoteAddr);
    }

    protected function isPublicIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    protected function normalizeIp($ip)
    {
        return filter_var(trim((string) $ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    public function _update_view_count($table, $id)
    {
        if (empty($table) || empty($id)) {
            return false;
        }

        $ip = $this->resolveClientIp();
        $today = date('Y-m-d');

        $db = Load::lib('Database');

        $db->fetch_array("SELECT id FROM `sys_view_logs` WHERE `table_name` = '$table' AND `item_id` = " . intval($id) . " AND `ip_address` = '$ip' AND `created_at` = '$today' LIMIT 1");

        if ($db->getSize() === 0) {
            $db->query("INSERT IGNORE INTO `sys_view_logs` (`table_name`, `item_id`, `ip_address`, `created_at`) 
                        VALUES ('$table', " . intval($id) . ", '$ip', '$today')");

            if ($db->affected_rows() > 0) {
                try {
                    $db->query("UPDATE `$table` SET `view_count` = `view_count` + 1 WHERE `id` = " . intval($id));
                    return true;
                } catch (Throwable $e) {
                    return false;
                }
            }
        }
        
        return false;
    }

    public function enrichTimestampFields(array $row): array
    {
        $rawCreatedAt = (string) ($row['created_at'] ?? '');
        $ts = !empty($rawCreatedAt) ? strtotime($rawCreatedAt) : false;

        if ($ts !== false && $ts > 0) {
            $diffSeconds = time() - $ts;
            if ($diffSeconds < 0 && $diffSeconds >= -300) {
                $diffSeconds = 0;
            }

            $isLast24h = ($diffSeconds >= 0 && $diffSeconds < 86400);
            $row['is_last_24h'] = $isLast24h;
            $row['published_ago'] = $isLast24h ? Helper::time_ago($rawCreatedAt, $this->lang['index'] ?? null) : '';

            $clockPart = date('H:i', $ts);
            $datePart = date('d.m.Y', $ts);
            $row['formatted_datetime'] = $datePart . ' ' . $clockPart;
            $row['display_time'] = $isLast24h ? $row['published_ago'] : $row['formatted_datetime'];
        } else {
            $row['is_last_24h'] = false;
            $row['published_ago'] = '';
            $row['formatted_datetime'] = '';
            $row['display_time'] = '';
        }

        $rawUpdatedAt = (string) ($row['updated_at'] ?? '');
        $row['is_updated_last_24h'] = false;
        if (!empty($rawUpdatedAt) && $rawUpdatedAt !== $rawCreatedAt) {
            $uts = strtotime($rawUpdatedAt);
            if ($uts !== false && $uts > 0 && ($ts === false || $uts > $ts)) {
                $updateClockPart = date('H:i', $uts);
                $updateDatePart = date('d.m.Y', $uts);
                $row['formatted_updated_datetime'] = $updateDatePart . ' ' . $updateClockPart;

                $updateDiffSeconds = time() - $uts;
                if ($updateDiffSeconds < 0 && $updateDiffSeconds >= -300) {
                    $updateDiffSeconds = 0;
                }
                $row['is_updated_last_24h'] = ($updateDiffSeconds >= 0 && $updateDiffSeconds < 86400);
            } else {
                $row['formatted_updated_datetime'] = '';
            }
        } else {
            $row['formatted_updated_datetime'] = '';
        }

        return $row;
    }

    public function enrichTimestampRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->enrichTimestampFields($row);
            }
        }
        unset($row);
        return $rows;
    }
}
