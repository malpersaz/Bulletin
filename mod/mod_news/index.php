<?php
/* AKM Software.
 * @2015
 * mod_news Module Page - Modernized version extending BaseModuleController
 * mod/mod_news/index.php
 */
class mod_news_mod extends BaseModuleController
{
    public $mod_news_model;

    protected $has_gallery = true;
    protected $has_files = true;

    protected $menu_url_pattern = '{lang}/news/{seo}';

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_news_category' => 'select_nested_category_id_result',
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

    public function __construct()
    {
        parent::__construct('mod_news');
        $this->ensure_gallery_module_mapping();
    }

    private function ensure_gallery_module_mapping()
    {
        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name('mod_news') === TRUE) {
            return;
        }

        $gallery_mod->name = 'News Galleries';
        $gallery_mod->mod_name = 'mod_news';
        $gallery_mod->mod_key = 'id';
        $gallery_mod->mod_value = 'name';
        $gallery_mod->user_id = intval($this->session->get('id')) > 0 ? intval($this->session->get('id')) : 1;
        $gallery_mod->save(TRUE);
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_news_category = Load::model('mod_news_category');
        $mod_news_category->select('id, name, lang');
        $mod_news_category->addWhere("parent_id != '0'");
        $mod_news_category->order('order', 'ASC')->all();
        $data['categories'] = $mod_news_category->toArray();

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
        if ($this->user->hasPermission('mod_news', 'list', $this->lang['mod_news']['role_errors']['list'], 1) === False) {
            return False;
        }

        $i = Load::model('mod_news');
        $i->select('id, name, image, lang, status, created_at, category_id, user_id, slider');
        
        $limit = 10;
        $offset = 0;
        $order = 'id';
        $sort = 'desc';

        $colomns = ['id', 'name', 'image', 'lang', 'status', 'created_at', 'category_id', 'user_id', 'slider'];

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])) {
                $order = $colomns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);

        $wheres = [];
        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }

        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
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

        if (Roll::hasPermission('mod_news', 'moderate_edit') !== TRUE && Roll::hasPermission('mod_news', 'moderate_delete') !== TRUE) {
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
        $records = $i->toArray();

        $status_list = [
            'y' => ['success', $this->lang['mod_news']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_news']['admin']['radio']['publish_no']],
        ];

        // Fetch user mapping
        $user_ids = [];
        $record_ids = [];
        if (is_array($records)) {
            foreach ($records as $item) {
                if (isset($item['id']) && $item['id'] > 0) {
                    $record_ids[] = $item['id'];
                }
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

        $has_slider_column = $this->_hasSliderColumn('mod_news');
        $hero_slider_map = [];
        if ($has_slider_column && !empty($record_ids)) {
            $slider_lookup = Load::model('mod_news');
            $slider_lookup->select('id, slider');
            $slider_lookup->addWhere("id IN (" . implode(',', array_map('intval', array_unique($record_ids))) . ")");
            $slider_lookup->gR();
            $slider_rows = $slider_lookup->toArray();
            if (is_array($slider_rows)) {
                foreach ($slider_rows as $slider_row) {
                    $hero_slider_map[(int) $slider_row['id']] = isset($slider_row['slider']) && $slider_row['slider'] === 'y';
                }
            }
        }

        if ($getCount > 0 && is_array($records)) {
            foreach ($records as $content) {
                $last = "";
                if (Roll::hasPermission('mod_news', 'edit')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['edit'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_news', 'delete')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['delete'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_news', 'gallery')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['gallery'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_news', 'file')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['file'] . '</span> </button>';
                }
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_news_table_ajax.akm.processQuickUnpublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_news_table_ajax.akm.processQuickPublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
                }

                $author_name = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : 'Admin';

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div style="cursor:pointer;" onclick="mod_news_table_ajax.akm.processImageUpload(' . $content['id'] . '); return false;" title="' . $this->lang['mod_news']['upload_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['iaddress'] . 'mod_news/thumb/' . $content['image'] . '" width="50" height="50" style="object-fit:cover;">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_news']['add_image'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'],
                    $content['status'],
                    $author_name,
                    isset($hero_slider_map[(int) $content['id']]) && $hero_slider_map[(int) $content['id']] === true ? 1 : 0
                ];
            }
        }

        if (ob_get_level() > 0) ob_clean();
        $totalModel = Load::model('mod_news');
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalModel->getCount(),
        ]);
    }

    private function _valid_date($data = '')
    {
        return true;
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

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_news', 'edit', $this->lang['mod_news']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_news_model = Load::model('mod_news');
        $this->mod_news_model->where_id($id)->gRo();
        if ($this->mod_news_model->getSize() === 0) {
            echo $this->lang['mod_news']['errors']['record'];
            return FALSE;
        }

        $this->mod_news_model->sync();
        if ($this->user->checkPermission('mod_news', 'moderate_edit', $this->mod_news_model->user_id, $this->lang['mod_news']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_news']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_news_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_news';
            $image->url = $this->mod_news_model->image;
            $option = [
                '/' => ['width' => 1200, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width'],
            ];
            try {
                if ($image->_delete_files($option)) {
                    $this->mod_news_model->image = '';
                    $this->mod_news_model->update('id');
                    echo 'Success';
                } else {
                    echo $image->error;
                    return FALSE;
                }
            } catch (Throwable $e) {
                echo $e->getMessage();
                return FALSE;
            }
        } else {
            echo 'Success';
        }
    }

    public function all_video()
    {
        if (Roll::hasPermission('mod_news', 'add') === FALSE && Roll::hasPermission('mod_news', 'edit') === FALSE) {
            exit();
        }

        $results = [];
        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;

        $video_model = Load::model('mod_video');
        $video_model->select('id, name');
        if ($q_param) {
            $video_model->where_like_name('%' . $q_param . '%');
        }
        $video_model->order('created_at', 'DESC')->gR();

        if ($video_model->getSize() > 0) {
            foreach ($video_model->toArray() as $item) {
                $results[] = [
                    'id'   => 'video:' . $item['id'],
                    'name' => $item['name'] . ' (' . $this->lang['mod_news']['local'] . ')'
                ];
            }
        }

        $vid_model = Load::model('mod_video_embed');
        $vid_model->select('id, name');
        if ($q_param) {
            $vid_model->where_like_name('%' . $q_param . '%');
        }
        $vid_model->order('created_at', 'DESC')->gR();

        if ($vid_model->getSize() > 0) {
            foreach ($vid_model->toArray() as $item) {
                $results[] = [
                    'id'   => 'vid:' . $item['id'],
                    'name' => $item['name'] . ' (' . $this->lang['mod_news']['embed'] . ')'
                ];
            }
        }

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function all_video_get($str = NULL)
    {
        if (Roll::hasPermission('mod_news', 'add') === FALSE && Roll::hasPermission('mod_news', 'edit') === FALSE) {
            exit();
        }
        if ($str === NULL) {
            exit;
        }

        $str = base64_decode($str);
        $data = @unserialize($str);

        if (is_array($data) === FALSE) {
            exit;
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
            $video_model->select('id, name')->where_id($video_ids)->gR();
            if ($video_model->getSize() > 0) {
                foreach ($video_model->toArray() as $item) {
                    $results[] = [
                        'id'   => 'video:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_news']['local'] . ')'
                    ];
                }
            }
        }

        if (!empty($vid_ids)) {
            $vid_model = Load::model('mod_video_embed');
            $vid_model->select('id, name')->where_id($vid_ids)->gR();
            if ($vid_model->getSize() > 0) {
                foreach ($vid_model->toArray() as $item) {
                    $results[] = [
                        'id'   => 'vid:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_news']['embed'] . ')'
                    ];
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function ajax_get_galleries()
    {
        if (Roll::hasPermission('mod_news', 'edit') === FALSE) {
            exit();
        }

        $this->ensure_gallery_module_mapping();

        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;
        $model = Load::model('mod_gallery_mod');
        $model->select('id, name');
        $model->where("mod_name != ''");
        if ($q_param) {
            $model->addWhere("name LIKE '%" . $q_param . "%'");
        }
        $model->order('id', 'DESC')->gR();

        $results = [];
        if ($model->getSize() > 0) {
            foreach ($model->toArray() as $item) {
                $results[] = [
                    'id'   => $item['id'],
                    'name' => $item['name']
                ];
            }
        }
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function ajax_get_files()
    {
        if (Roll::hasPermission('mod_news', 'edit') === FALSE) {
            exit();
        }
        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;
        $model = Load::model('mod_attachment');
        $model->select('id, original_name as name');
        if ($q_param) {
            $model->addWhere("original_name LIKE '%" . $q_param . "%'");
        }
        $model->order('id', 'DESC')->gR();

        $results = [];
        if ($model->getSize() > 0) {
            foreach ($model->toArray() as $item) {
                $results[] = [
                    'id'   => $item['id'],
                    'name' => $item['name']
                ];
            }
        }
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function ajax_get_current_media()
    {
        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['record_not_found']]);
            return;
        }

        $news_model = Load::model('mod_news');
        $news_model->select('id, file_id, gallery_id, video')->where_id($id)->gRo();
        if ($news_model->getSize() === 0) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['record_not_found']]);
            return;
        }
        $news_model->sync();

        $video = [];
        if (!empty($news_model->video)) {
            $unserialized = @unserialize($news_model->video);
            $video = is_array($unserialized) ? $unserialized : [];
        }

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'file_id'    => intval($news_model->file_id ?? 0),
            'gallery_id' => intval($news_model->gallery_id ?? 0),
            'video'      => $video
        ]);
    }

    public function ajax_update_relation()
    {
        if ($this->user->hasPermission('mod_news', 'edit', $this->lang['mod_news']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        $type = Req::Post_Safe('type');
        $value = Req::Post('value');

        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['record_not_found']]);
            return;
        }

        $news_model = Load::model('mod_news');
        $news_model->where_id($id)->gRo();
        if ($news_model->getSize() === 0) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['record_not_found']]);
            return;
        }
        $news_model->sync();

        if ($type === 'video') {
            $video_data = $news_model->video;
            $existed = (is_string($video_data) && !empty($video_data)) ? @unserialize($video_data) : [];
            if (!is_array($existed)) {
                $existed = [];
            }

            $value = trim((string) $value);
            if ($value === "0" || $value === "") {
                $existed = [];
            } else {
                if (in_array($value, $existed, true)) {
                    $existed = array_values(array_diff($existed, [$value]));
                } else {
                    $existed[] = $value;
                }
            }
            $news_model->video = !empty($existed) ? serialize(array_values(array_unique($existed))) : '';
        } elseif ($type === 'gallery' || $type === 'files') {
            $val_int = intval($value);
            if ($type === 'gallery') {
                $news_model->gallery_id = $val_int;
            } else {
                $news_model->file_id = $val_int;
            }
        } else {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['unknown_type']]);
            return;
        }

        $news_model->id = $id;
        if ($news_model->update('id')) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_news']['ajax']['relation_updated']]);
        } else {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['ajax']['database_error']]);
        }
    }

    public function ajax_check_title()
    {
        if (Roll::hasPermission('mod_news', 'add') === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['add']]);
            return;
        }

        $title = trim(Req::Post('title'));
        if (!$title) {
            echo json_encode(['status' => 'error', 'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['name'])]);
            return;
        }

        $this->mod_news_model = Load::model('mod_news');
        $this->mod_news_model->where_name($title)->gRo();
        
        if ($this->mod_news_model->getSize() > 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['validator']['title_exists']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_news']['validator']['title_unique']]);
        }
    }

    public function ajax_image_upload()
    {
        if ($this->user->hasPermission('mod_news', 'edit', $this->lang['mod_news']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['errors']['record']]);
            return;
        }

        $this->mod_news_model = Load::model('mod_news');
        $this->mod_news_model->where_id($id)->gRo();
        if ($this->mod_news_model->getSize() === 0) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['errors']['record']]);
            return;
        }
        $this->mod_news_model->sync();

        if ($this->user->checkPermission('mod_news', 'moderate_edit', $this->mod_news_model->user_id, $this->lang['mod_news']['role_errors']['moderate_edit']) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['moderate_edit']]);
            return;
        }

        if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
            $image = Load::mod('image');
            $image->mod = 'mod_news';
            $image->name = 'image';
            $option = [
                '/' => ['width' => 1200, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width'],
            ];

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    if ($this->mod_news_model->image != '') {
                        $image->mod = 'mod_news';
                        $image->url = $this->mod_news_model->image;
                        $image->_delete_files($option);
                    }

                    $update_model = Load::model('mod_news');
                    $update_model->id = $id;
                    $update_model->updated_at = date('Y-m-d H:i:s');
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['iaddress'] . 'mod_news/thumb/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['message']['error']['edit']]);
                    }
                } else {
                    if (ob_get_level() > 0) ob_clean();
                    echo json_encode(['status' => 'error', 'message' => $image->error]);
                }
            } catch (Throwable $e) {
                if (ob_get_level() > 0) ob_clean();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function toggle_hero_slider()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->_hasSliderColumn('mod_news')) {
            echo json_encode(['status' => 'error', 'message' => 'Slider column is not installed yet.']);
            return;
        }

        if ($this->user->hasPermission('mod_news', 'edit', $this->lang['mod_news']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['errors']['record']]);
            return;
        }

        $news_model = Load::model('mod_news');
        $news_model->where_id($id)->gRo();
        if ($news_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['errors']['record']]);
            return;
        }

        $record = $news_model->toArray();
        if ($this->user->checkPermission('mod_news', 'moderate_edit', $record['user_id'], $this->lang['mod_news']['role_errors']['moderate_edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['moderate_edit']]);
            return;
        }

        $new_slider_state = (isset($record['slider']) && $record['slider'] === 'y') ? 'n' : 'y';
        $news_model->id = $id;
        $news_model->slider = $new_slider_state;

        if ($news_model->update('id')) {
            echo json_encode([
                'status' => 'success',
                'slider' => $new_slider_state,
                'message' => $new_slider_state === 'y' ? ($this->lang['mod_news']['hero_slider_added'] ?? 'Sent to slider.') : ($this->lang['mod_news']['hero_slider_removed'] ?? 'Removed from slider.')
            ]);
            return;
        }

        echo json_encode([
            'status' => 'error',
            'message' => $this->lang['mod_news']['hero_slider_error'] ?? 'Slider update failed.'
        ]);
    }

    public function approve()
    {
        if ($this->user->hasPermission('mod_news', 'approval', $this->lang['mod_news']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if (is_array($query)) {
            foreach ($query as $s) {
                $m = Load::model('mod_news');
                $m->id = $s;
                $m->status = 'y';
                $m->update('id');
            }
            echo 'Success';
            return;
        }
        echo 'ERROR';
    }

    public function unapprove()
    {
        if ($this->user->hasPermission('mod_news', 'approval', $this->lang['mod_news']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if (is_array($query)) {
            foreach ($query as $s) {
                $m = Load::model('mod_news');
                $m->id = $s;
                $m->status = 'n';
                $m->update('id');
            }
            echo 'Success';
            return;
        }
        echo 'ERROR';
    }
}
