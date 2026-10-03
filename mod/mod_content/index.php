<?php
/* AKM Software.
 * @2015
 * mod_content Module Page - Modernized version extending BaseModuleController
 * mod/mod_content/index.php
 */
class mod_content_mod extends BaseModuleController
{
    public $mod_content_model;

    protected $has_gallery = true;
    protected $has_files = true;
    protected $has_faq = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_content_category' => 'select_nested_category_id_result',
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $need_content_refs = [
        'mod_gallery_mod' => ['mod_content_gallery_id'],
        'mod_attachment' => ['mod_content_file_id']
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 1280,
                    'height' => 720,
                    'option' => 'cover',
                    'quality' => 80,
                ],
                '/thumb' => [
                    'width' => 480,
                    'height' => 270,
                    'option' => 'cover',
                    'quality' => 75,
                ],
            ]
        ]
    ];

    protected $special_fields = [
        'summary' => ['type' => 'html'],
        'content' => ['type' => 'html']
    ];

    protected $menu_url_pattern = '{lang}/i/{seo}';

    public function __construct()
    {
        parent::__construct('mod_content');

        $table = 'mod_content';
        $column = 'slider';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();
        $exists = false;

        if (strpos($adapter, 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
            $rows = $this->db->toArray();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row['name']) && $row['name'] === $column) {
                        $exists = true;
                        break;
                    }
                }
            }
        } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
            $this->db->fetch_array($sql);
            $exists = ($this->db->getSize() !== 0);
        } else {
            $this->db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
            $exists = ($this->db->getSize() !== 0);
        }

        if (!$exists) {
            if (strpos($adapter, 'PDO_SQLITE') !== false) {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN slider TEXT CHECK(slider IN ('y', 'n')) NOT NULL DEFAULT 'n'";
            } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN slider VARCHAR(1) NOT NULL DEFAULT 'n'";
            } else {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `slider` ENUM('y', 'n') NOT NULL DEFAULT 'n'";
            }
            $this->db->query($sql);
        }
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_category = Load::model('mod_content_category');
        $mod_category->select('id, name, lang');
        $mod_category->addWhere("root != 'y'");
        $mod_category->order('order', 'ASC')->all();
        $data['categories'] = $mod_category->toArray();

        return $data;
    }

    protected function beforeSave($model)
    {
        $model->created_at = $this->normalizeDateTime(Req::Post_Safe('created_at'));
        $model->updated_at = date('Y-m-d H:i:s');

        // Handle serialization of video references
        $model->video = $this->serializeVideos(Req::Post_Safe('video'));

        $model->user_id = $this->session->get('id');

        return $model;
    }

    protected function beforeUpdate($model, $id)
    {
        $model->updated_at = date('Y-m-d H:i:s');
        if (isset($model->created_at)) {
            $model->created_at = $this->normalizeDateTime($model->created_at);
        }

        // Handle serialization of video references
        $model->video = $this->serializeVideos(Req::Post_Safe('video'));

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
        if ($this->user->hasPermission('mod_content', 'list', $this->lang['mod_content']['role_errors']['list'], 1) === False) {
            return False;
        }

        $i = Load::model('mod_content');
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

        if (Role::hasPermission('mod_content', 'moderate_edit') !== TRUE && Role::hasPermission('mod_content', 'moderate_delete') !== TRUE) {
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
            'y' => ['success', $this->lang['mod_content']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_content']['admin']['radio']['publish_no']],
        ];

        // Fetch user mapping
        $user_ids = [];
        if (is_array($records)) {
            foreach ($records as $item) {
                if (isset($item['user_id']) && $item['user_id'] > 0) {
                    $user_ids[] = $item['user_id'];
                }
            }
        }

        $user_map = [];
        if ($user_ids !== []) {
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
                if (Role::hasPermission('mod_content', 'edit')) {
                    $last .= '<button data-action="edit" data-id="' . $content['id'] . '" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['edit'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_content', 'delete')) {
                    $last .= '<button data-action="delete" data-id="' . $content['id'] . '" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['delete'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_content', 'gallery')) {
                    $last .= '<button data-action="gallery" data-id="' . $content['id'] . '" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['gallery'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_content', 'file')) {
                    $last .= '<button data-action="file" data-id="' . $content['id'] . '" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['file'] . '</span> </button>';
                }
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" data-action="unpublish" data-id="' . $content['id'] . '" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" data-action="publish" data-id="' . $content['id'] . '" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
                }

                $author_name = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : 'Admin';

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div class="table-img-trigger" data-action="upload-image" data-id="' . $content['id'] . '" title="' . $this->lang['mod_content']['upload_change_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['iaddress'] . 'mod_content/thumb/' . $content['image'] . '" width="50" height="50" class="table-thumb-cover">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . (isset($this->lang['mod_content']['add_image']) ? $this->lang['mod_content']['add_image'] : 'Add Image') . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'],
                    $content['status'],
                    $author_name,
                    isset($content['slider']) && $content['slider'] === 'y' ? 1 : 0
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        $modelClass = Load::modelfile('mod_content');
        $totalModel = new $modelClass();
        if (
            (Role::hasPermission('mod_content', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_content', 'moderate_delete') !== TRUE)
        ) {
            $totalModel->addWhere("user_id='" . $this->session->get('id') . "'");
        }
        $totalCount = $totalModel->getCount();

        $draftModel = new $modelClass();
        $draftModel->addWhere("status='n'");
        if (
            (Role::hasPermission('mod_content', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_content', 'moderate_delete') !== TRUE)
        ) {
            $draftModel->addWhere("user_id='" . $this->session->get('id') . "'");
        }
        $draftCount = $draftModel->getCount();
        $publishedCount = max(0, $totalCount - $draftCount);

        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalCount,
            'totalContent' => $totalCount,
            'publishedContent' => $publishedCount,
            'draftContent' => $draftCount,
        ]);
    }

    public function all_video()
    {
        if (Role::hasPermission('mod_content', 'add') === FALSE && Role::hasPermission('mod_content', 'edit') === FALSE) {
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
                    'name' => $item['name'] . ' (' . $this->lang['mod_content']['local'] . ')'
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
                    'name' => $item['name'] . ' (' . $this->lang['mod_content']['embed'] . ')'
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function all_video_get($str = NULL)
    {
        if (Role::hasPermission('mod_content', 'add') === FALSE && Role::hasPermission('mod_content', 'edit') === FALSE) {
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
            if (count($parts) === 2) {
                if ($parts[0] == 'video') {
                    $video_ids[] = $parts[1];
                } elseif ($parts[0] == 'vid') {
                    $vid_ids[] = $parts[1];
                }
            }
        }

        $results = [];

        if ($video_ids !== []) {
            $video_model = Load::model('mod_video');
            $video_model->select('id, name')->where_id($video_ids)->gR();
            if ($video_model->getSize() > 0) {
                foreach ($video_model->toArray() as $item) {
                    $results[] = [
                        'id'   => 'video:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_content']['local'] . ')'
                    ];
                }
            }
        }

        if ($vid_ids !== []) {
            $vid_model = Load::model('mod_video_embed');
            $vid_model->select('id, name')->where_id($vid_ids)->gR();
            if ($vid_model->getSize() > 0) {
                foreach ($vid_model->toArray() as $item) {
                    $results[] = [
                        'id'   => 'vid:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_content']['embed'] . ')'
                    ];
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function ajax_get_files()
    {
        if (Role::hasPermission('mod_content', 'edit') === FALSE) {
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
        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function ajax_get_current_media()
    {
        $id = intval(Req::Post_Safe('id'));
        if ($id === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found']]);
            return;
        }

        $content_model = Load::model('mod_content');
        $content_model->select('id, file_id, gallery_id, video')->where_id($id)->gRo();
        if ($content_model->getSize() === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found']]);
            return;
        }
        $content_model->sync();

        $video = [];
        if (!empty($content_model->video)) {
            $unserialized = @unserialize($content_model->video);
            $video = is_array($unserialized) ? $unserialized : [];
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'file_id'    => intval($content_model->file_id ?? 0),
            'gallery_id' => intval($content_model->gallery_id ?? 0),
            'video'      => $video
        ]);
    }

    public function ajax_update_relation()
    {
        if ($this->user->hasPermission('mod_content', 'edit', $this->lang['mod_content']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        $type = Req::Post_Safe('type');
        $value = Req::Post('value');

        if ($id === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found']]);
            return;
        }

        $content_model = Load::model('mod_content');
        $content_model->where_id($id)->gRo();
        if ($content_model->getSize() === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found']]);
            return;
        }
        $content_model->sync();

        if ($type === 'video') {
            $video_data = $content_model->video;
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
            $content_model->video = $existed !== [] ? serialize(array_values(array_unique($existed))) : '';
        } elseif ($type === 'gallery' || $type === 'files') {
            $val_int = intval($value);
            if ($type === 'gallery') {
                $content_model->gallery_id = $val_int;
            } else {
                $content_model->file_id = $val_int;
            }
        } else {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['unknown_type']]);
            return;
        }

        $content_model->id = $id;
        if ($content_model->update('id')) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_content']['ajax']['relation_updated']]);
        } else {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['database_error']]);
        }
    }

    public function approve()
    {
        if ($this->user->hasPermission('mod_content', 'approval', $this->lang['mod_content']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if (is_array($query)) {
            foreach ($query as $s) {
                $m = Load::model('mod_content');
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
        if ($this->user->hasPermission('mod_content', 'approval', $this->lang['mod_content']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if (is_array($query)) {
            foreach ($query as $s) {
                $m = Load::model('mod_content');
                $m->id = $s;
                $m->status = 'n';
                $m->update('id');
            }
            echo 'Success';
            return;
        }
        echo 'ERROR';
    }

    public function toggle_hero_slider()
    {
        if ($this->user->hasPermission('mod_content', 'edit', $this->lang['mod_content']['role_errors']['edit'] ?? 'Access denied', 1) === False) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['edit'] ?? 'Access denied']);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id === 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found'] ?? 'Record not found']);
            return;
        }

        $this->mod_content_model = Load::model('mod_content');
        $this->mod_content_model->where_id($id)->gRo();
        if ($this->mod_content_model->getSize() === 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_content']['ajax']['record_not_found'] ?? 'Record not found']);
            return;
        }
        $this->mod_content_model->sync();

        $new_slider = (($this->mod_content_model->slider ?? 'n') === 'y') ? 'n' : 'y';

        $update_model = Load::model('mod_content');
        $update_model->id = $id;
        $update_model->slider = $new_slider;
        $update_model->updated_at = date('Y-m-d H:i:s');

        if ($update_model->update('id')) {
            $this->jsonResponse([
                'status' => 'success',
                'slider' => $new_slider,
                'in_hero_slider' => $new_slider === 'y',
                'message' => $new_slider === 'y'
                    ? ($this->lang['mod_content']['hero_slider_added'] ?? 'Added to slider')
                    : ($this->lang['mod_content']['hero_slider_removed'] ?? 'Removed from slider')
            ]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_content']['hero_slider_error'] ?? 'Slider update failed']);
        }
    }
}
