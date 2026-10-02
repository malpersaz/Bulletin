<?php

abstract class indexBase
{
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
        Cache::set(unserialize($acl->acl), "permission-acl-" . $acl_id);
        return unserialize($acl->acl);
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

    public function _mod_attachment($mod, $vid)
    {
        $file = Load::model('mod_attachment');
        $file->where_mod_name_and_vid($mod, $vid);
        $file->select('id,url,original_name,filename')->order('id', 'DESC');
        $file->gR();

        if ($file->getSize() !== 0) {
            $file_array = $file->toArray();
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
            $file_array = [$file->toArray()];
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

    public function _mod_video($v)
    {
        if (empty($v) || !is_string($v)) {
            return;
        }
        $data = @unserialize($v);
        if ($data === false && $v !== 'b:0;') {
            error_log("Amed Framework Warning: Failed to unserialize video data: " . $v);
            return;
        }
        if (!is_array($data) || empty($data)) {
            return;
        }

        $video_ids = [];
        $vid_ids = [];

        foreach($data as $prefixed_id) {
            $parts = explode(':', $prefixed_id, 2);
            if (count($parts) == 2) {
                if ($parts[0] == 'video') {
                    $video_ids[] = $parts[1];
                } elseif ($parts[0] == 'vid') {
                    $vid_ids[] = $parts[1];
                }
            }
        }

        $results = [];

        if (!empty($video_ids)) {
            $video_model = Load::model('mod_video');
            $video_model->where_id($video_ids)->gR();
            if ($video_model->getSize() > 0) {
                $results = array_merge($results, $video_model->toArray());
            }
        }

        if (!empty($vid_ids)) {
            $vid_model = Load::model('mod_video_embed');
            $vid_model->select('id, name, embed, image');
            $vid_model->where_id($vid_ids)->gR();
            if ($vid_model->getSize() > 0) {
                $results = array_merge($results, $vid_model->toArray());
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
        $list->addWhere(['id' => $id], ' OR');
        $list->addWhere(['parent_id' => $id], ' OR');

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {


            $myArray = [];
            foreach ($list->toArray() as $ar) {
                $myArray[$ar[$order]] = $ar;
            }
            $this->tema->ctx_set('list', $myArray);
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
        $list->addWhere(['parent_id' => $id], ' OR');

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {


            $myArray = [];
            foreach ($list->toArray() as $ar) {
                $myArray[$ar[$order]] = $ar;
            }
            $this->tema->ctx_set('parent_id_list', $myArray);
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

            $activeTemplate = !empty($this->site['app_template']) ? basename(Req::Clear($this->site['app_template'])) : 'amida';
            $fullTemplatePath = template_dir . $activeTemplate;
            if (!file_exists($fullTemplatePath) || !is_dir($fullTemplatePath)) {
                $activeTemplate = 'amida';
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
        $news->order('created_at', 'DESC');
        $news->limit($limit);
        $news->gR();
        
        if ($news->getSize() !== 0) {
            $items = $news->toArray();
            foreach ($items as &$item) {
                $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
            }
            return $items;
        }
        return [];
    }

    public function _related_content($category_id, $limit = 5)
    {
        $content = Load::model('mod_content.contents');
        $content->where_category_id_and_status($category_id, 'y');
        $content->select('name,seo,summary,image');
        $content->order('created_at', 'DESC');
        $content->limit($limit);
        $content->gR();
        if ($content->getSize() !== 0) {
            $this->tema->ctx_set('contents', $content->toArray());
            $content->getResultOne();
            $this->tema->ctx_set('son_icerik', $content->toArray());
        }
        unset($content);
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
                $db->query("UPDATE `$table` SET `view_count` = `view_count` + 1 WHERE `id` = " . intval($id));
                return true;
            }
        }
        
        return false;
    }
}
