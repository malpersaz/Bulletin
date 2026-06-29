<?php
/* AKM Software.
 * @2015
 * mod_publication Module Page - Modernized version extending BaseModuleController
 * mod/mod_publication/index.php
 */
class mod_publication_mod extends BaseModuleController
{
    public $mod_publication_model;

    protected $has_gallery = true;
    protected $has_files = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_publication_category' => 'select_nested_category_id_result',
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 1200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ]
        ]
    ];

    protected $special_fields = [
        'summary' => ['type' => 'html'],
        'content' => ['type' => 'html']
    ];

    protected $menu_url_pattern = '{lang}/publication/{seo}';

    public function __construct()
    {
        parent::__construct('mod_publication');
        $this->ensure_gallery_module_mapping();
    }

    private function ensure_gallery_module_mapping()
    {
        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name('mod_publication') === TRUE) {
            return;
        }

        $gallery_mod->name = 'Publication Galleries';
        $gallery_mod->mod_name = 'mod_publication';
        $gallery_mod->mod_key = 'id';
        $gallery_mod->mod_value = 'name';
        $gallery_mod->user_id = intval($this->session->get('id')) > 0 ? intval($this->session->get('id')) : 1;
        $gallery_mod->save(TRUE);
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_publication_category = Load::model('mod_publication_category');
        $mod_publication_category->select('id, name, lang');
        $mod_publication_category->addWhere("root != 'y'");
        $mod_publication_category->order('order', 'ASC')->all();
        $data['categories'] = $mod_publication_category->toArray();

        return $data;
    }

    protected function beforeSave($model)
    {
        $model->created_at = Req::Post_Safe('created_at') ? Req::Post_Safe('created_at') : date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');

        // Handle serialization of video references
        $videos = explode(',', Req::Post_Safe('video'));
        $data = [];
        foreach ($videos as $video_key) {
            if ($video_key !== '') {
                $data[] = $video_key;
            }
        }
        if (!empty($data)) {
            $data = array_unique($data);
            $model->video = serialize($data);
        } else {
            $model->video = '';
        }

        $model->user_id = $this->session->get('id');

        return $model;
    }

    protected function beforeUpdate($model, $id)
    {
        $model->updated_at = date('Y-m-d H:i:s');

        // Handle serialization of video references
        $videos = explode(',', Req::Post_Safe('video'));
        $data = [];
        foreach ($videos as $video_key) {
            if ($video_key !== '') {
                $data[] = $video_key;
            }
        }
        if (!empty($data)) {
            $data = array_unique($data);
            $model->video = serialize($data);
        } else {
            $model->video = '';
        }

        return $model;
    }

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'image|image',
            'lang|badge',
            'status|badge',
            'created_at|datetime',
            'category_id|custom',
            'user_id|custom'
        ];
    }

    public function datatable_json()
    {
        // Check list permission
        if ($this->user->hasPermission('mod_publication', 'list', $this->lang['mod_publication']['role_errors']['list'], 1) === False) {
            return False;
        }

        $i = Load::model('mod_publication');
        $i->select('id, name, image, lang, status, created_at, category_id, user_id, slider');
        
        $limit = 10;
        $offset = 0;
        $order = 'id';
        $sort = 'desc';

        $columns = ['id', 'name', 'image', 'lang', 'status', 'created_at', 'category_id', 'user_id', 'slider'];

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($columns[$o[0]['column'] - 1])) {
                $order = $columns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);

        $wheres = [];

        if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
            foreach (Req::Post_Safe('arama') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
            foreach (Req::Post_Safe('arama_radio') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }

        if (Req::is_Post('arama_created_at') && count(Req::Post_Safe('arama_created_at')) > 0) {
            foreach (Req::Post_Safe('arama_created_at') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    if (isset($v['from']) && isset($v['to']) && $this->_valid_date($v['from']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " BETWEEN '" . $v['from'] . "' AND '" . $v['to'] . "'";
                    } elseif (isset($v['from']) && $this->_valid_date($v['from'])) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (isset($v['to']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }

        if ((Roll::hasPermission('mod_publication', 'moderate_edit') !== TRUE) && (Roll::hasPermission('mod_publication', 'moderate_delete') !== TRUE)) {
            $wheres[] = "user_id='" . $this->session->get('id') . "'";
        }

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $i->addWhere($wheres);
        }

        if (Req::is_Post('length') && intval(Req::Post_Safe('length')) > 0) {
            $limit = intval(Req::Post_Safe('length'));
        }

        if (Req::is_Post('start') && intval(Req::Post_Safe('start')) >= 0) {
            $offset = intval(Req::Post_Safe('start'));
        }

        $getCount = $i->getCount();
        $i->limit($limit, $offset);
        $i->gR();

        $data = [];

        $status_list = [
            'y' => ['success', $this->lang['mod_publication']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_publication']['admin']['radio']['publish_no']],
        ];

        // Collect user IDs for names
        $user_ids = [];
        $records = $i->toArray();
        if (is_array($records)) {
            foreach ($records as $item) {
                if (isset($item['user_id']) && $item['user_id'] > 0) {
                    $user_ids[] = $item['user_id'];
                }
            }
        }

        $user_map = [];
        if (!empty($user_ids)) {
            $user_ids = array_unique($user_ids);
            $user_model = Load::model('mod_user.user');
            $user_model->select('id, name, last_name');
            $user_model->addWhere("id IN (" . implode(',', $user_ids) . ")");
            $user_model->gR();
            if ($user_model->getSize() > 0) {
                foreach ($user_model->toArray() as $u) {
                    $user_map[$u['id']] = $u['name'] . ' ' . $u['last_name'];
                }
            }
        }

        if ($getCount > 0 && is_array($records)) {
            foreach ($records as $content) {
                $last = "";
                if (Roll::hasPermission('mod_publication', 'edit')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['edit'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_publication', 'delete')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['delete'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_publication', 'gallery')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['gallery'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_publication', 'file')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['file'] . '</span> </button>';
                }
                
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_publication_table_ajax.akm.processQuickUnpublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_publication_table_ajax.akm.processQuickPublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
                }

                $author_name = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : 'Admin';

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div style="cursor:pointer;" onclick="mod_publication_table_ajax.akm.processImageUpload(' . $content['id'] . '); return false;" title="' . $this->lang['mod_publication']['upload_change_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['iaddress'] . 'mod_publication/thumb/' . $content['image'] . '" width="50" height="50" style="object-fit:cover;">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['add_image'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'],
                    $content['status'],
                    $author_name,
                    isset($content['slider']) && $content['slider'] === 'y'
                ];
            }
        }

        $write = [
            "draw" => intval(Req::Post_Safe('draw')),
            "recordsTotal" => $getCount,
            "recordsFiltered" => $getCount,
            "data" => $data
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($write);
        exit;
    }

    public function toggle_hero_slider()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->_hasSliderColumn('mod_publication')) {
            echo json_encode(['status' => 'error', 'message' => 'Slider column is not installed yet.']);
            return;
        }

        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record']]);
            return;
        }

        $publication_model = Load::model('mod_publication');
        $publication_model->where_id($id)->gRo();
        if ($publication_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record']]);
            return;
        }

        $record = $publication_model->toArray();
        if ($this->user->checkPermission('mod_publication', 'moderate_edit', $record['user_id'], $this->lang['mod_publication']['role_errors']['moderate_edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['moderate_edit']]);
            return;
        }

        $new_slider_state = (isset($record['slider']) && $record['slider'] === 'y') ? 'n' : 'y';
        $publication_model->id = $id;
        $publication_model->slider = $new_slider_state;

        if ($publication_model->update('id')) {
            echo json_encode([
                'status' => 'success',
                'in_hero_slider' => $new_slider_state === 'y',
                'message' => $new_slider_state === 'y' ? ($this->lang['mod_publication']['hero_slider_added'] ?? 'Sent to slider.') : ($this->lang['mod_publication']['hero_slider_removed'] ?? 'Removed from slider.')
            ]);
            return;
        }

        echo json_encode([
            'status' => 'error',
            'message' => $this->lang['mod_publication']['hero_slider_error'] ?? 'Slider update failed.'
        ]);
    }

    private function _hasSliderColumn($table)
    {
        if ($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('" . addslashes($table) . "')");
            $result = $this->db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === 'slider') {
                        return true;
                    }
                }
            }
            return false;
        }

        $this->db->fetch_array("SHOW COLUMNS FROM `" . $table . "` LIKE 'slider'");
        $result = $this->db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }
}
