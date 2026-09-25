<?php
/* AKM Software
 * @2010
 * Gallery Module
 * mod/mod_gallery/index.php
 */
class mod_gallery_mod extends indexMain
{
    public $moduleLang;
    public $mod_gallery_model;
    public $mod_info;
    public $r;

    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->theme = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Load Language File
        $this->lang = Load::lang('mod_gallery');
        // Send Language File to Template
        $this->theme->lang($this->lang);

        if (Req::is_Post('sid')) {
            Registry::add('session_sid', Req::Post_Safe('sid'));
        }
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    public function index()
    {
        $this->list();
    }

    private function _resolve_record_label($record, $configured_value = null)
    {
        if (!is_array($record) || $record === []) {
            return false;
        }

        $candidates = [];
        if ($configured_value !== null && $configured_value !== '') {
            $candidates[] = $configured_value;
        }

        $fallback_fields = ['name', 'title', 'headline', 'subject'];
        foreach ($fallback_fields as $field) {
            if (!in_array($field, $candidates, true)) {
                $candidates[] = $field;
            }
        }

        foreach ($candidates as $field) {
            if (!array_key_exists($field, $record)) {
                continue;
            }

            $value = trim((string) $record[$field]);
            if ($value === '') {
                continue;
            }

            if ($field === 'id' || substr($field, -3) === '_id') {
                continue;
            }

            return $value;
        }

        return false;
    }

    /**
     * Checks if the module and record exist and if the user has permissions.
     */
    public function _check_mod($mod, $vid)
    {
        if ($mod === NULL || $vid === NULL) {
            return FALSE;
        }

        // Check if module exists in bridge table, auto-register if it's a valid module
        $this->mod_info = Load::model('mod_gallery_mod');
        if ($this->mod_info->exists_mod_name($mod) === FALSE) {
            try {
                $rawName = str_replace('mod_', '', $mod);
                $displayName = ucfirst($rawName) . ' Galleries';
                $safeName = addslashes($displayName);
                $safeMod = addslashes($mod);
                $this->db->query("INSERT INTO `mod_gallery_mod` (`name`, `mod_name`, `mod_key`, `mod_value`, `user_id`) VALUES ('{$safeName}', '{$safeMod}', 'id', 'name', 1)");
                $this->mod_info->flush();
            } catch (Exception $e) {
                // Table might not exist yet
            }
        }

        if ($this->mod_info->exists_mod_name($mod) === FALSE) {
            return FALSE;
        }

        $this->mod_info->getResultOne()->sync();

        $this->moduleLang = Load::lang($mod);

        // Check for permission in the target module
        $roleError = $this->moduleLang[$mod]['role_errors']['gallery'] ?? ($this->lang['mod_gallery']['errors']['access_denied'] ?? 'Permission denied');
        $hasPermission = (
            Role::hasPermission($mod, 'gallery') ||
            Role::hasPermission($mod, 'edit') ||
            Role::hasPermission('mod_gallery', 'list') ||
            Role::hasPermission('mod_gallery', 'edit') ||
            $this->user->hasPermission($mod, 'gallery', $roleError, 1)
        );

        if (!$hasPermission) {
            return FALSE;
        }

        $target_model = Load::model($mod);
        if (!$target_model || $target_model->exists_id($vid) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Main Gallery List or Image List for a specific record
     */
    public function list($mod = NULL, $vid = NULL)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_gallery']['admin']['title'];
        $this->theme->set('site', $this->site);

        if ($mod && $vid) {
            // Specific Gallery view (Images of a record)
            if ($this->_check_mod($mod, $vid) === FALSE) {
                if (Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                    if (ob_get_level() > 0) {
                        ob_clean();
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => $this->lang['mod_gallery']['errors']['record'] ?? 'Record not found or access denied.'
                    ]);
                    exit;
                }
                Helper::redirect($this->site['saddress'] . 'mod_gallery/list');
                return;
            }

            $this->mod_gallery_model = Load::model('mod_gallery');
            $this->mod_gallery_model->where_vid_and_mod_name($vid, $mod)->gR();

            $record_label = (string) $vid;
            $target_model = Load::model($mod);
            $target_model->where_id($vid)->limit(1)->gRo();

            if ($target_model->getSize() > 0) {
                $target_model->sync();
                $record = $target_model->toArray();
                $resolved_label = $this->_resolve_record_label($record, $this->mod_info->mod_value);
                if ($resolved_label !== false) {
                    $record_label = $resolved_label;
                }
            }

            $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
            $current_url = 'mod_gallery/list/' . $mod . '/' . $vid;
            if ($referer !== '' && strpos($referer, $this->site['saddress']) === 0 && strpos($referer, $current_url) === false) {
                $back_url = $referer;
            } else {
                $back_url = $this->site['saddress'] . $mod . '/list';
            }

            $this->theme->set('pictures', $this->mod_gallery_model->toArray());
            $this->theme->set('mod_name', $mod);
            $this->theme->set('vid', $vid);
            $this->theme->set('record_label', $record_label);
            $this->theme->set('back_url', $back_url);
            $this->theme->set('is_ajax', Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1));
            $this->theme->tpl('mod_gallery/list_images');
        } else {
            // General Gallery List view (Galleries from mod_gallery_mod)
            if ($this->user->hasPermission('mod_gallery', 'list', $this->lang['mod_gallery']['role_errors']['list'], 1) === False) {
                return;
            }
            $overview = $this->getGalleriesOverview();
            $this->theme->set('galleries', $overview['items']);
            $this->theme->set('total_galleries', $overview['total_galleries']);
            $this->theme->set('total_images', $overview['total_images']);
            $this->theme->set('gallery_modules', $overview['modules']);
            $this->theme->tpl('mod_gallery/list');
        }
    }

    /**
     * Retrieves aggregated galleries overview for both server-side and AJAX responses
     */
    private function getGalleriesOverview(): array
    {
        $db = new Database();

        $galleries = $db->query("SELECT mod_name, vid, COUNT(*) FROM mod_gallery GROUP BY mod_name, vid ORDER BY id DESC")->fetchAll(PDO::FETCH_NUM);

        $mod_infos = [];
        $mod_info_model = Load::model('mod_gallery_mod');
        $mod_info_model->where("mod_name != ''")->gR();
        foreach ($mod_info_model->toArray() as $m_info) {
            $mod_infos[$m_info['mod_name']] = $m_info;
        }

        $items = [];
        $totalImages = 0;
        $moduleStats = [];

        foreach ($galleries as $gal) {
            $mod = $gal[0];
            $vid = $gal[1];
            $imgCount = (int) $gal[2];
            $totalImages += $imgCount;

            $moduleLabel = isset($mod_infos[$mod]['name']) ? $mod_infos[$mod]['name'] : $mod;
            if (!isset($moduleStats[$mod])) {
                $moduleStats[$mod] = [
                    'mod_name' => $mod,
                    'label' => $moduleLabel,
                    'count' => 0
                ];
            }
            $moduleStats[$mod]['count']++;

            $record_label = $mod . ' / ' . $vid;
            $target_model = Load::model($mod);
            if ($target_model && $target_model->exists_id($vid)) {
                $target_model->where_id($vid)->limit(1)->gRo();
                if ($target_model->getSize() > 0) {
                    $target_model->sync();
                    $record = $target_model->toArray();
                    $configuredField = isset($mod_infos[$mod]['mod_value']) ? $mod_infos[$mod]['mod_value'] : null;
                    $resolved_label = $this->_resolve_record_label($record, $configuredField);
                    if ($resolved_label !== false) {
                        $record_label = $resolved_label;
                    }
                }
            }

            // Retrieve last 3 images for the preview stack
            $stmt = $db->query("SELECT url FROM mod_gallery WHERE mod_name = '{$mod}' AND vid = '{$vid}' ORDER BY id DESC LIMIT 3");
            $previews = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

            $previewUrls = [];
            foreach ($previews as $pUrl) {
                $previewUrls[] = $this->site['iaddress'] . 'mod_gallery/' . $mod . '/thumb/' . $pUrl;
            }

            $items[] = [
                'id' => $vid,
                'name' => $record_label,
                'mod_name' => $mod,
                'mod_label' => $moduleLabel,
                'mod_key' => $vid,
                'image_count' => $imgCount,
                'preview_images' => $previews,
                'preview_urls' => $previewUrls,
                'url' => $this->site['saddress'] . 'mod_gallery/list/' . $mod . '/' . $vid,
            ];
        }

        return [
            'items' => $items,
            'total_galleries' => count($items),
            'total_images' => $totalImages,
            'modules' => array_values($moduleStats),
        ];
    }

    /**
     * AJAX Data source for Gallery List
     */
    public function ajax_list()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->user->hasPermission('mod_gallery', 'list', $this->lang['mod_gallery']['role_errors']['list'], 1) === False) {
            echo json_encode(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]);
            exit;
        }

        $overview = $this->getGalleriesOverview();
        $items = $overview['items'];
        $total = count($items);
        $search = trim((string) Req::Post_Safe('query'));
        if ($search !== '') {
            $items = array_values(array_filter($items, function ($item) use ($search) {
                $haystacks = [
                    $item['name'] ?? '',
                    $item['mod_name'] ?? '',
                    $item['mod_label'] ?? '',
                    $item['mod_key'] ?? '',
                ];

                foreach ($haystacks as $haystack) {
                    if (stripos((string) $haystack, $search) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        echo json_encode([
            'draw' => intval(Req::Post_Safe('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => count($items),
            'data' => $items
        ]);
        exit;
    }

    /**
     * Rename an image
     */
    public function rename()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval(Req::Post_Safe('id'));
        $name = Req::Post_Safe('name');

        $this->r = Load::model('mod_gallery');
        $this->r->where_id($id)->gRo();

        if ($this->r->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['errors']['record']]);
            exit;
        }

        $this->r->sync();
        $mod = $this->r->mod_name;
        $vid = $this->r->vid;

        if ($this->user->hasPermission($mod, 'gallery', $this->lang['mod_gallery']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['role_errors']['edit']]);
            exit;
        }

        $this->r->name = $name;
        if ($this->r->update('id')) {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_gallery']['admin']['image_edited']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['errors']['rename_failed']]);
        }
        exit;
    }

    /**
     * Delete an image
     */
    public function delete()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval(Req::Post_Safe('id'));
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');

        if ($this->_check_mod($mod, $vid) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['ajax']['error']]);
            exit;
        }

        $this->r = Load::model('mod_gallery');
        $this->r->where_id_and_vid_and_mod_name($id, $vid, $mod)->limit(1)->gRo();

        if ($this->r->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['errors']['record']]);
            exit;
        }

        $this->r->sync();

        $image = Load::mod('image');
        $image->mod = 'mod_gallery';
        $image->url = $this->r->url;
        $option = [
            '/' . $mod . '/original/' => ['width' => 1920, 'height' => NULL, 'option' => 'width'],
            '/' . $mod . '/thumb' => ['width' => 500, 'height' => NULL, 'option' => 'width']
        ];

        if ($image->_delete_files($option) && $this->r->delete()) {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_gallery']['admin']['image_deleted']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $image->error ?: $this->lang['mod_gallery']['errors']['delete_failed']]);
        }
        exit;
    }

    /**
     * Delete all images in a gallery (bulk remove)
     */
    public function delete_all()
    {
        header('Content-Type: application/json; charset=utf-8');
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');

        if ($this->_check_mod($mod, $vid) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['ajax']['error']]);
            exit;
        }

        if ($this->_remove($mod, $vid)) {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_gallery']['admin']['image_deleted']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['errors']['delete_failed']]);
        }
        exit;
    }

    /**
     * Upload an image
     */
    public function upload()
    {
        header('Content-Type: application/json; charset=utf-8');
        $fieldName = Req::Post_Safe('isim') ?: (Req::Post_Safe('field_name') ?: 'image');

        if (Req::is_Files($fieldName) == False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['errors']['upload_failed'] ?? 'Upload failed']);
            exit;
        }

        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');

        if ($this->_check_mod($mod, $vid) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['ajax']['error']]);
            exit;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_gallery';
        $image->name = $fieldName;
        $option = [
            '/' . $mod . '/original/' => ['width' => 1920, 'height' => NULL, 'option' => 'width'],
            '/' . $mod . '/thumb' => ['width' => 500, 'height' => NULL, 'option' => 'width']
        ];

        if ($image->_resize($option)) {
            $imageUrl = $image->url;

            $default_name = '#';
            $target_model = Load::model($mod);
            if ($target_model && $target_model->exists_id($vid)) {
                $target_model->where_id($vid)->limit(1)->gRo();
                if ($target_model->getSize() > 0) {
                    $target_model->sync();
                    $record = $target_model->toArray();
                    $configured_val = isset($this->mod_info->mod_value) ? $this->mod_info->mod_value : null;
                    $resolved = $this->_resolve_record_label($record, $configured_val);
                    if ($resolved !== false && trim((string) $resolved) !== '') {
                        $default_name = trim((string) $resolved);
                    }
                }
            }

            if ($default_name === '#' && isset($_FILES[$fieldName]['name'])) {
                $original_filename = pathinfo($_FILES[$fieldName]['name'], PATHINFO_FILENAME);
                if (trim($original_filename) !== '') {
                    $default_name = trim($original_filename);
                }
            }

            $this->r = Load::model('mod_gallery');
            $this->r->name = $default_name;
            $this->r->url = $imageUrl;
            $this->r->mod_name = $mod;
            $this->r->vid = $vid;
            $this->r->user_id = $this->session->get('id');

            if ($this->r->save()) {
                echo json_encode([
                    'status' => 'success',
                    'url' => 'static/mod_gallery/' . $mod . '/thumb/' . $resim_link,
                    'original' => $resim_link
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['ajax']['error']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $image->error]);
        }
        exit;
    }

    /**
     * Remove all images associated with a record or module (Internal)
     */
    public function _remove($mod, $vid = NULL)
    {
        $this->r = Load::model('mod_gallery');
        if ($vid == NULL) {
            $this->r->where_mod_name($mod)->gR();
        } else {
            $this->r->where_vid_and_mod_name($vid, $mod)->gR();
        }

        if ($this->r->getSize() === 0) {
            return TRUE;
        }

        $items = $this->r->toArray();
        foreach ($items as $item) {
            $image = Load::mod('image');
            $image->mod = 'mod_gallery';
            $image->url = $item['url'];
            $option = [
                '/' . $mod . '/original/' => ['width' => 1920, 'height' => NULL, 'option' => 'width'],
                '/' . $mod . '/thumb' => ['width' => 500, 'height' => NULL, 'option' => 'width']
            ];
            $image->_delete_files($option);
        }
        $this->r->delete();
        return TRUE;
    }
}
