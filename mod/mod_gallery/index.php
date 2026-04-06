<?php
/* AKM Software
 * @2010
 * Gallery Module
 * mod/mod_gallery/index.php
 */
class mod_gallery_mod extends indexMain
{
    public $mlang;
    public $mod_gallery_model;
    public $mod_info;
    public $r;

    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Load Language File
        $this->lang = Load::lang('mod_gallery');
        // Send Language File to Template
        $this->tema->lang($this->lang);

        if (Req::is_Post('sid')) {
            Kayit::ekle('session_sid', Req::Post_Safe('sid'));
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
        if (!is_array($record) || empty($record)) {
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

        // Check if module exists
        $this->mod_info = Load::model('mod_gallery_mod');
        if ($this->mod_info->exists_mod_name($mod) === FALSE) {
            return FALSE;
        }

        $this->mod_info->getResultOne()->sync();
        
        $this->mlang = Load::lang($mod);
        
        // Check for permission in the target module
        if ($this->user->hasPermission($mod, 'gallery', $this->mlang[$mod]['role_errors']['gallery'], 1) === False) {
            return False;
        }

        $target_model = Load::model($mod);
        if ($target_model->exists_id($vid) === FALSE) {
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
        $this->tema->veri('site', $this->site);

        if ($mod && $vid) {
            // Specific Gallery view (Images of a record)
            if ($this->_check_mod($mod, $vid) === FALSE) {
                Helper::yonlendir($this->site['sadres'] . 'mod_gallery/list');
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
            
            $this->tema->veri('pictures', $this->mod_gallery_model->toArray());
            $this->tema->veri('mod_name', $mod);
            $this->tema->veri('vid', $vid);
            $this->tema->veri('record_label', $record_label);
            $this->tema->tpl('mod_gallery/list_images');
        } else {
            // General Gallery List view (Galleries from mod_gallery_mod)
            if ($this->user->hasPermission('mod_gallery', 'list', $this->lang['mod_gallery']['role_errors']['list'], 1) === False) {
                return;
            }
            $this->tema->tpl('mod_gallery/list');
        }
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

        $model = Load::model('mod_gallery_mod');
        $search = trim((string) Req::Post_Safe('query'));

        $model->where(['mod_name !' => ''])->order('id', 'DESC')->gR();
        $items = $model->toArray();

        // Get image counts for each gallery
        $db = new Database();
        foreach ($items as &$item) {
            $res = $db->query("SELECT COUNT(*) as total FROM mod_gallery WHERE mod_name = '{$item['mod_name']}' AND vid = '{$item['mod_key']}'")->fetch(PDO::FETCH_ASSOC);
            $item['image_count'] = $res['total'];
        }
        unset($item);

        $total = count($items);
        if ($search !== '') {
            $items = array_values(array_filter($items, function ($item) use ($search) {
                $haystacks = [
                    $item['name'] ?? '',
                    $item['mod_name'] ?? '',
                    $item['mod_key'] ?? '',
                    $item['mod_value'] ?? '',
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
    public function addegis()
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
    public function sil()
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
            '/' . $mod . '/original/' => ['width' => NULL, 'height' => NULL, 'option' => 'original'],
            '/' . $mod . '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width']
        ];

        if ($image->_delete_files($option) && $this->r->delete()) {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_gallery']['admin']['image_deleted']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $image->error ?: $this->lang['mod_gallery']['errors']['delete_failed']]);
        }
        exit;
    }

    /**
     * Upload an image
     */
    public function up()
    {
        header('Content-Type: application/json; charset=utf-8');
        $isim = Req::Post_Safe('isim') ?: 'image';

        if (Req::is_Files($isim) == False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_gallery']['hata']['get_source_failed']]);
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
        $image->name = $isim;
        $option = [
            '/' . $mod . '/original/' => ['width' => NULL, 'height' => NULL, 'option' => 'original'],
            '/' . $mod . '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width']
        ];

        if ($image->_resize($option)) {
            $resim_link = $image->url;
            
            $this->r = Load::model('mod_gallery');
            $this->r->name = "#"; // Initial name can be changed later
            $this->r->url = $resim_link;
            $this->r->mod_name = $mod;
            $this->r->vid = $vid;
            $this->r->user_id = $this->session->getir('id');

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
                '/' . $mod . '/original/' => ['width' => NULL, 'height' => NULL, 'option' => 'original'],
                '/' . $mod . '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width']
            ];
            $image->_delete_files($option);
        }
        $this->r->delete();
        return TRUE;
    }
}
