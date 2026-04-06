<?php
/* AKM Software.
 * @2015
 * mod_publication Module Page
 * mod/mod_publication/index.php
 */
class mod_publication_mod extends indexMain
{
    public $mod_publication_model;
    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Site settings are provided by the parent controller.
        // Load Language File
        $this->lang = Load::lang('mod_publication');
        // Send Language File to Template
        $this->tema->lang($this->lang);
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    // Add Page
    public function add()
    {
        // Check Add Permission
        if ($this->user->hasPermission('mod_publication', 'add', $this->lang['mod_publication']['role_errors']['add'], 1) === False) {
            // Return JSON for AJAX requests.
            if ($this->_isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['add']]);
                exit;
            }
            return False;
        }
        
        // Handle AJAX requests separately.
        if (Req::is_Post('add')) {
            try {
                $result = $this->_add();
                // Stop after the JSON response is sent for AJAX requests.
                if ($this->_isAjaxRequest()) {
                    exit; // _add() allready sends JSON and exits for AJAX
                }
            } catch (Exception $e) {
                // Log the exception for diagnostics.
                $logFile = root_dir . 'logs/ajax_errors.log';
                $logMessage = '[' . date('Y-m-d H:i:s') . '] AJAX Error in mod_publication/add: ' . $e->getMessage() . "\n";
                $logMessage .= '  File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
                $logMessage .= '  Trace: ' . $e->getTraceAsString() . "\n";
                $logMessage .= '  POST Data: ' . print_r($_POST, true) . "\n";
                $logMessage .= "---\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
                
                if ($this->_isAjaxRequest()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error: ' . $e->getMessage(),
                        'debug' => [
                            'file' => basename($e->getFile()),
                            'line' => $e->getLine()
                        ]
                    ]);
                    exit;
                }
                throw $e;
            }
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_publication']['add'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('wizard_module', 'mod_publication');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_publication_category_nested = Load::model('mod_publication_category');
        $mod_publication_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_publication_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_publication` ORDER BY id DESC");
        $this->tema->veri('mod_publication_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_publication` ORDER BY id DESC");
        $this->tema->veri('mod_publication_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
        $mod_publication_nested = Load::model('mod_menu');
        $mod_publication_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_publication_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);

        // Load wizard category options as a flat list.
        $mod_publication_category = Load::model('mod_publication_category');
        $mod_publication_category->select('id, name, lang');
        $mod_publication_category->addWhere("root = 'n'");
        $mod_publication_category->order('`order`', 'ASC');
        $mod_publication_category->all();
        $categories = $mod_publication_category->toArray();
        $this->tema->veri('categories', $categories);
        $this->tema->tpl('mod_publication/add');
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_publication_model = Load::model('mod_publication');
        $id = Req::Url_Clear($id);
        if ($this->mod_publication_model->exists_id($id) === False) {
            $this->_fatal_error($this->lang['mod_publication']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_publication/edit');
            return;
        }

        $d = $this->mod_publication_model->getResultOne()->toArray();

        // Enforce moderation permission for records owned by another user.
        if ($this->user->checkPermission('mod_publication', 'moderate_edit', $d['user_id'], $this->lang['mod_publication']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_publication']['edit'];
        $this->tema->veri('wizard_module', 'mod_publication');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_publication_category_nested = Load::model('mod_publication_category');
        $mod_publication_category_nested->flush();
        $mod_publication_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_publication_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_publication` ORDER BY id DESC");
        $this->tema->veri('mod_publication_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_publication` ORDER BY id DESC");
        $this->tema->veri('mod_publication_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());

        // Load wizard category options as a flat list.
        $mod_publication_category = Load::model('mod_publication_category');
        $mod_publication_category->select('id, name, lang');
        $mod_publication_category->addWhere("root = 'n'");
        $mod_publication_category->order('`order`', 'ASC');
        $mod_publication_category->all();
        $categories = $mod_publication_category->toArray();
        $this->tema->veri('categories', $categories);
        $this->tema->tpl('mod_publication/edit');
    }

    // List Page
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_publication', 'list', $this->lang['mod_publication']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_publication']['list'];
        
        // Load dashboard counters.
        $mod_publication = Load::model('mod_publication');
        $mod_publication->flush();
        $total_publication = $mod_publication->getCount();
        
        $mod_publication->flush();
        $mod_publication->where(['status' => 'n']);
        $draft_publication = $mod_publication->getCount();

        // Load category data for the admin list.
        $mod_publication_category = Load::model('mod_publication_category');
        $mod_publication_category->select('id, name, lang');
        $mod_publication_category->addWhere("root = 'n'");
        $mod_publication_category->order('`order`', 'ASC');
        $mod_publication_category->all();
        $categories = $mod_publication_category->toArray();

        $this->tema->veri('total_publication', $total_publication);
        $this->tema->veri('draft_publication', $draft_publication);
        $this->tema->veri('categories', $categories);
        $this->tema->veri('site', $this->site);

        $this->tema->tpl('mod_publication/list');
    }

    public function image_remove_image()
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_publication_model = Load::model('mod_publication');
        $this->mod_publication_model->where_id($id)->gRo();
        if ($this->mod_publication_model->getSize() === 0) {
            echo $this->lang['mod_publication']['errors']['record'];
            return FALSE;
        }

        $this->mod_publication_model->sync();
        // Enforce moderation permission for records owned by another user.
        if ($this->user->checkPermission('mod_publication', 'moderate_edit', $this->mod_publication_model->user_id, $this->lang['mod_publication']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_publication']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_publication_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_publication';
            $image->url = $this->mod_publication_model->image;
            $option = [
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
            ];
            try {
                if ($image->_delete_files($option)) {
                    $this->mod_publication_model->image = '';
                    $this->mod_publication_model->update('id');
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

    public function remove_image()
    {
        return $this->image_remove_image();
    }

    public function videos()
    {
        if (Roll::hasPermission('mod_publication', 'add') === FALSE && Roll::hasPermission('mod_publication', 'edit') === FALSE) {
            exit();
        }
        // Reverted
        exit();
    }

    public function video($str = NULL)
    {
        if (Roll::hasPermission('mod_publication', 'add') === FALSE && Roll::hasPermission('mod_publication', 'edit') === FALSE) {
            exit();
        }
        // Reverted
        exit();
    }



    public function ajax_get_galleries()
    {
        if (Roll::hasPermission('mod_publication', 'edit') === FALSE) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit();
        }

        $q = Req::is_Post('q') ? Req::Post_Safe('q') : null;
        $model = Load::model('mod_gallery_mod');
        $model->select('id, name');
        $model->where("mod_name != ''");
        if ($q) {
            $model->addWhere("name LIKE '%" . $q . "%'");
        }
        $model->order('id', 'DESC')->gR();

        $data = [];
        if ($model->getSize() > 0) {
            foreach ($model->toArray() as $item) {
                $data[] = [
                    'id' => $item['id'],
                    'text' => $item['name']
                ];
            }
        }

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    public function ajax_get_files()
    {
        if (Roll::hasPermission('mod_publication', 'edit') === FALSE) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit();
        }

        $q = Req::is_Post('q') ? Req::Post_Safe('q') : null;
        $model = Load::model('mod_attachment');
        $model->select('id, original_name as name');
        if ($q) {
            $model->addWhere("original_name LIKE '%" . $q . "%'");
        }
        $model->order('id', 'DESC')->gR();

        $data = [];
        if ($model->getSize() > 0) {
            foreach ($model->toArray() as $item) {
                $data[] = [
                    'id' => $item['id'],
                    'text' => $item['name']
                ];
            }
        }

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    public function ajax_get_current_media()
    {
        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['file_id' => 0]);
            return;
        }

        $db = new Database();
        $res = $db->query("SELECT file_id, gallery_id FROM mod_publication WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'file_id' => intval($res['file_id'] ?? 0),
            'gallery_id' => intval($res['gallery_id'] ?? 0)
        ]);
    }

    public function ajax_update_relation()
    {
        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        $type = Req::Post_Safe('type');
        $value = Req::Post_Safe('value');

        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record']]);
            return;
        }

        $db = new Database();
        
        if ($type === 'files') {
            $val_int = intval($value);
            $sql = "UPDATE mod_publication SET `file_id` = '$val_int' WHERE id = '$id'";
        } else if ($type === 'gallery') {
            $val_int = intval($value);
            $sql = "UPDATE mod_publication SET `gallery_id` = '$val_int' WHERE id = '$id'";
        } else {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Unknown type']);
            return;
        }

        if ($db->query($sql)) {
            if (ob_get_level() > 0) ob_clean();
            
            // Determine the specific message based on type and value (0 = removed, >0 = added)
            $msg_key = 'relation_updated';
            if ($type === 'files') {
                $msg_key = (intval($value) === 0) ? 'file_removed' : 'file_added';
            } else if ($type === 'gallery') {
                $msg_key = (intval($value) === 0) ? 'gallery_removed' : 'gallery_added';
            }
            
            $msg = $this->lang['mod_publication']['ajax'][$msg_key] ?? $this->lang['mod_publication']['ajax']['relation_updated'];
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    }

    // Save a new record.
    private function _add()
    {
        $isAjax = $this->_isAjaxRequest();
        
        $this->val = Load::validator('mod_publication.add');
        if ($this->val->check()) {
            // Return validation errors.
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->hatalar),
                    'errors' => $this->val->hatalar
                ]);
                exit;
            }
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_publication_model = Load::model('mod_publication');

            $this->mod_publication_model->lang = Req::Post_Safe('lang');
            $this->mod_publication_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_publication_model->category_id = Req::Post_Safe('category_id');
            $this->mod_publication_model->status = Roll::hasPermission('mod_publication', 'change_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_publication_model->created_at = Req::Post_Safe('created_at') ? Req::Post_Safe('created_at') : date('Y-m-d H:i:s');
            $this->mod_publication_model->updated_at = date('Y-m-d H:i:s');
            $this->mod_publication_model->deck = Req::Post_Safe('deck');

            $this->mod_publication_model->summary = Req::Post('summary');
            $this->mod_publication_model->summary = Helper::clear_html($this->mod_publication_model->summary);

            $this->mod_publication_model->content = Req::Post('content');
            $this->mod_publication_model->content = Helper::clear_html($this->mod_publication_model->content);

            $this->mod_publication_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_publication_model->file_id = Req::Post_Safe('file_id');
            $this->mod_publication_model->acl_id = Req::Post_Safe('acl_id');
            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_publication_model->video = serialize($data);
            } else {
                $this->mod_publication_model->video = '';
            }

            $this->mod_publication_model->user_id = $this->session->getir('id');

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_publication';
                $image->name = 'image';
                $option = [
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
                ];
                if ($image->_resize($option)) {
                    $this->mod_publication_model->image = $image->url;
                } else {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['status' => 'error', 'message' => $image->error]);
                        exit;
                    }
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_publication_model->image)) {
                    unset($this->mod_publication_model->image);
                }
            }

            if ($this->mod_publication_model->save(TRUE)) {
                // If requested to add to menu
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_publication_model->name;
                    $this->model->lang = $this->mod_publication_model->lang;
                    $this->model->url = $this->mod_publication_model->seo;
                    $this->model->status = $this->mod_publication_model->status;
                    $this->model->acl_id = $this->mod_publication_model->acl_id;
                    $this->model->url = $this->mod_publication_model->lang . '/publication/' . $this->model->url;
                    $this->model->save();
                }
                
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'success',
                        'message' => $this->lang['mod_publication']['message']['success']['add'],
                        'redirect' => $this->site['sadres'] . 'mod_publication/list'
                    ]);
                    exit;
                }
                
                $this->_success_message($this->lang['mod_publication']['message']['success']['add']);
                $this->_redirect_now($this->site['sadres'] . 'mod_publication/list');
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => $this->lang['mod_publication']['message']['error']['add'] ?? 'Add operation failed'
                    ]);
                    exit;
                }
                
                $this->_error_message($this->lang['mod_publication']['message']['success']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_publication.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_publication_model = Load::model('mod_publication');

            $this->mod_publication_model->id = $id;
            $this->mod_publication_model->lang = Req::Post_Safe('lang');
            $this->mod_publication_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_publication_model->category_id = Req::Post_Safe('category_id');
            $this->mod_publication_model->status = Roll::hasPermission('mod_publication', 'edit_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_publication_model->created_at = Req::Post_Safe('created_at');
            // $this->mod_publication_model->updated_at = date('Y-m-d H:i:s'); // Already in ajax object if saved correctly
            $this->mod_publication_model->deck = Req::Post_Safe('deck');

            $this->mod_publication_model->summary = Req::Post('summary');
            $this->mod_publication_model->summary = Helper::clear_html($this->mod_publication_model->summary);

            $this->mod_publication_model->content = Req::Post('content');
            $this->mod_publication_model->content = Helper::clear_html($this->mod_publication_model->content);

            $this->mod_publication_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_publication_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_publication_model->file_id = Req::Post_Safe('file_id');
            $this->mod_publication_model->acl_id = Req::Post_Safe('acl_id');
            
            // Update if this content was previously sent to menu
            $new_mod_publication = new mod_publication_model();
            $new_mod_publication->where_id($id)->gRo()->sync();
            $url = $new_mod_publication->lang . '/publication/' . $new_mod_publication->seo;

            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_publication_model->video = serialize($data);
            } else {
                $this->mod_publication_model->video = '';
            }

            if (isset($this->mod_publication_model->user_id)) {
                unset($this->mod_publication_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {


                $image = Load::mod('image');
                $image->mod = 'mod_publication';
                $image->name = 'image';
                $option = [
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
                ];
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Remove the previous image before storing the new one.
                    $old_data = Load::model('mod_publication');
                    $old_data->where_id($id)->gRo()->sync();
                    if ($old_data->image != '') {
                        $image->mod = 'mod_publication';
                        $image->url = $old_data->image;
                        $image->_delete_files($option);
                    }
                    $this->mod_publication_model->image = $new_image_url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_publication_model->image)) {
                    unset($this->mod_publication_model->image);
                }
            }

            $this->mod_publication_model->id = $id;
            if ($this->mod_publication_model->update('id')) {
                // Update if this content was previously sent to menu
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_publication_model->name;
                    $mod_menu_model->lang = $this->mod_publication_model->lang;
                    $mod_menu_model->url = $this->mod_publication_model->seo;
                    $mod_menu_model->status = $this->mod_publication_model->status;
                    $mod_menu_model->acl_id = $this->mod_publication_model->acl_id;
                    $mod_menu_model->url = $this->mod_publication_model->lang . '/publication/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }

                $this->_success_message($this->lang['mod_publication']['message']['success']['edit']);
                $this->_redirect_now($this->site['sadres'] . 'mod_publication/list');
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_publication']['message']['error']['edit']);
            }
        }
    }

    function delete()
    {
        $isAjax = $this->_isAjaxRequest();
        $nasil = $isAjax ? 3 : 1;

        // Check Delete Permission
        if ($this->user->hasPermission('mod_publication', 'delete', $this->lang['mod_publication']['role_errors']['delete'], $nasil) === False) {
            return False;
        }

        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post_Safe('delete'))) {
                if ($isAjax) {
                    echo 'Oke';
                } else {
                    $this->_success_message($this->lang['mod_publication']['context_menu']['delete_success']);
                    $this->_redirect_now($this->site['sadres'] . 'mod_publication/list');
                }
            } else {
                if ($isAjax) {
                    echo $this->lang['mod_publication']['filter']['delete_error'];
                } else {
                    $this->_error_message($this->lang['mod_publication']['filter']['delete_error']);
                }
            }
        } else {
            if ($isAjax) {
                echo json_encode(['error' => true, 'mesaj' => $this->lang['mod_publication']['errors']['record']]);
            } else {
                exit($this->lang['mod_publication']['errors']['record']);
            }
        }
    }

    public function _delete($delete)
    {
        $this->mod_publication_model = Load::model('mod_publication');
        $isAjax = $this->_isAjaxRequest();
        $nasil = $isAjax ? 3 : 1;

        if (!is_array($delete)) {
            $delete = [$delete];
        }

        foreach ($delete as $s) {
            $this->mod_publication_model->flush();
            $this->mod_publication_model->where_id($s)->gRo();
            if ($this->mod_publication_model->getSize() === 0) {
                continue;
            }
            $this->mod_publication_model->sync();
            // Check Moderate Permission if content belongs to another user
            if ($this->user->checkPermission('mod_publication', 'moderate_delete', $this->mod_publication_model->user_id, $this->lang['mod_publication']['role_errors']['moderate_delete'], $nasil) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_publication") === TRUE) {
                // Delete Gallery if exists for content
                Run::import('mod_gallery', '_remove', ['mod_publication', $this->mod_publication_model->id]);
            }
            // Check if module exists and File is being added
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_publication") === TRUE) {
                // Delete File if exists for content
                Run::import('mod_attachment', '_remove', ['mod_publication', $this->mod_publication_model->id]);
            }

            if ($this->mod_publication_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_publication';
                $image->name = 'image';
                $image->url = $this->mod_publication_model->image;
                $option = [
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
                ];
                if ($image->_delete_files($option) !== TRUE) {
                    echo $image->error;
                }
            }

            $this->mod_publication_model->delete();
        }
        return TRUE;
    }

    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }

    public function ajax_check_title()
    {
        // Check Permission
        if (Roll::hasPermission('mod_publication', 'add') === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['add']]);
            return;
        }

        $title = trim(Req::Post('title'));
        if (!$title) {
            echo json_encode(['status' => 'error', 'message' => sprintf($this->lang['mod_publication']['validator']['required'], $this->lang['mod_publication']['admin']['columns']['name'])]);
            return;
        }

        $this->mod_publication_model = Load::model('mod_publication');
        $this->mod_publication_model->where_name($title)->gRo();
        
        if ($this->mod_publication_model->getSize() > 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['validator']['title_exists']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_publication']['validator']['title_unique']]);
        }
    }

    public function ajax_image_upload()
    {
        // Check Permission
        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record']]);
            return;
        }

        $this->mod_publication_model = Load::model('mod_publication');
        $this->mod_publication_model->where_id($id)->gRo();
        if ($this->mod_publication_model->getSize() === 0) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record']]);
            return;
        }
        $this->mod_publication_model->sync();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_publication', 'moderate_edit', $this->mod_publication_model->user_id, $this->lang['mod_publication']['role_errors']['moderate_edit']) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['moderate_edit']]);
            return;
        }

        if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
            $image = Load::mod('image');
            $image->mod = 'mod_publication';
            $image->name = 'image';
            $option = [
                '/' => ['width' => 1200, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width'],
            ];

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Remove the previous image before saving the new one.
                    if ($this->mod_publication_model->image != '') {
                        $image->mod = 'mod_publication';
                        $image->url = $this->mod_publication_model->image;
                        $image->_delete_files($option);
                    }

                    // Instatantiate a fresh model for update to avoid any state issues
                    $update_model = Load::model('mod_publication');
                    $update_model->id = $id;
                    $update_model->updated_at = date('Y-m-d H:i:s');
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['radres'] . 'mod_publication/thumb/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['message']['error']['edit']]);
                    }
                } else {
                    if (ob_get_level() > 0) ob_clean();
                    echo json_encode(['status' => 'error', 'message' => $image->error]);
                }
            } catch (Throwable $e) {
                if (ob_get_level() > 0) ob_clean();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_publication']['message']['error']['add']]);
        }
    }
    public function datatable_json()
    {
        $i = Load::model('mod_publication');

        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'lang';
        $colomns[] = 'status';
        $colomns[] = 'created_at';
        $colomns[] = 'category_id'; // Added for frontend mapping
        $colomns[] = 'user_id';     // Added for author mapping
        $i->select($colomns);
        $limit = 10;
        $offset = 0;
        $order = $colomns[0];;
        $sort = 'desc';

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (
                isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])
            ) {
                $order = $colomns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);

        $wheres = [];

        if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
            foreach (Req::Post_Safe('arama') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
            foreach (Req::Post_Safe('arama_radio') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }
        if (Req::is_Post('arama_created_at') && count(Req::Post_Safe('arama_created_at')) > 0) {
            foreach (Req::Post_Safe('arama_created_at') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    if (isset($v['from']) && isset($v['to']) && $this->_valid_date($v['from']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " BETWEEN '" . $v['from'] . "' AND '" . $v['to'] . "'";
                    } elseif (
                        isset($v['from']) && $this->_valid_date($v['from'])
                    ) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (
                        isset($v['to']) && $this->_valid_date($v['to'])
                    ) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }
        if (
            (Roll::hasPermission('mod_publication', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_publication', 'moderate_delete') !== TRUE)
        ) {
            $wheres[] = "user_id='" . $this->session->getir('id') . "'";
        }

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $i->addWhere($wheres);
        }

        // Debug: Log what we receive
        error_log("datatable_json - POST length: " . (isset($_POST['length']) ? $_POST['length'] : 'NOT SET'));
        error_log("datatable_json - POST start: " . (isset($_POST['start']) ? $_POST['start'] : 'NOT SET'));
        error_log("datatable_json - is_Post length: " . (Req::is_Post('length') ? 'TRUE' : 'FALSE'));
        
        if (Req::is_Post('length') && intval(Req::Post_Safe('length')) > 0) {
            $limit = intval(Req::Post_Safe('length'));
        }
        error_log("datatable_json - Final LIMIT: " . $limit);

        if (Req::is_Post('start') && intval(Req::Post_Safe('start')) >= 0) {
            $offset = intval(Req::Post_Safe('start'));
        }
        error_log("datatable_json - Final OFFSET: " . $offset);
        $getCount = $i->getCount();
        $i->limit($limit, $offset);

        $i->gR();
        //print_r($i);exit;
        $data = [];

        $status_list = [
            'y' => ['success', $this->lang['mod_publication']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_publication']['admin']['radio']['publish_no']],
        ];

        $deck_list = [
            'y' => ['success', $this->lang['mod_publication']['admin']['radio']['deck_yes']],
            'n' => ['danger', $this->lang['mod_publication']['admin']['radio']['deck_no']],
        ];

        // Duplicate loop removed.
        // The correct loop is below, after user ID collection.
        
        // Collect user IDs
        $user_ids = [];
        $record_ids = [];
        
        $records = $i->toArray();
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
        
        // Fetch User Names
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

        $has_slider_column = $this->_hasSliderColumn('mod_publication');
        $hero_slider_map = [];
        if ($has_slider_column && !empty($record_ids)) {
            $slider_lookup = Load::model('mod_publication');
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
                if (Roll::hasPermission('mod_publication', 'edit')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['edit'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_publication',  'delete')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button'][ 'delete'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_publication', 'gallery')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['gallery'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_publication', 'file')) {
                    $last .= '<button onclick="mod_publication_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['button']['file'] . '</span> </button>';
                }
                
                // Publish/Unpublish Button
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_publication_table_ajax.akm.processQuickUnpublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_publication_table_ajax.akm.processQuickPublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
                }

                //$author_name = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : 'Admin';
                // Debugging: Force a visible string to check frontend
                $real_author = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : 'Admin';
                $author_name = $real_author;
                //$author_name = 'Admin'; // Fallback debug

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div style="cursor:pointer;" onclick="mod_publication_table_ajax.akm.processImageUpload(' . $content['id'] . '); return false;" title="' . $this->lang['mod_publication']['upload_change_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['radres'] . 'mod_publication/thumb/' . $content['image'] . '" width="50" height="50" style="object-fit:cover;">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_publication']['resim_add'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'], // 7: Category ID
                    $content['status'],       // 8: Status (y/n)
                    $author_name,            // 9: Author Name
                    isset($hero_slider_map[(int) $content['id']]) ? 1 : 0
                ];
            }
        }
        // Clean any output buffer to ensure valid JSON
        if (ob_get_level() > 0) ob_clean();
        
        $totalModel = Load::model('mod_publication');
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalModel->getCount(),
        ]);
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
        $this->db->fetch_array("SHOW COLUMNS FROM `" . $table . "` LIKE 'slider'");
        $result = $this->db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    // Approve Page
    public function publish()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_publication', 'approval', $this->lang['mod_publication']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_publish($query) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
        }
    }

    private function _publish($query = NULL)
    {
        // Load mod_publication Model
        $this->mod_publication_model = Load::model('mod_publication');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_publication_model->id = $s;
                $this->mod_publication_model->status = 'y';
                if ($this->mod_publication_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Unpublish Page
    public function unpublish()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_publication', 'approval', $this->lang['mod_publication']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unpublish($query) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
        }
    }

    private function _unpublish($query = NULL)
    {
        // Load mod_publication Model
        $this->mod_publication_model = Load::model('mod_publication');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_publication_model->id = $s;
                $this->mod_publication_model->status = 'n';
                if ($this->mod_publication_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Success Message
    private function _success_message($mesaj)
    {
        $this->tema->veri('success', True);
        $this->tema->veri('message', $mesaj);
    }

    // Error Message
    private function _error_message($mesaj)
    {
        $this->tema->veri('error', True);
        $this->tema->veri('errors', [$mesaj]);
    }

    // Fatal Error Message
    private function _fatal_error($mesaj)
    {
        $this->tema->veri('fatal_error', TRUE);
        $this->tema->veri('message', $mesaj);
    }

    // Check if request is AJAX (for wizard and other AJAX calls)
    private function _isAjaxRequest()
    {
    // Detect AJAX requests from headers or explicit form flags.
        if (Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1') {
            return true;
        }
        
        // Fallback: Header kontrol bike
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

    private function _redirect_now($url)
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" /></noscript>';
        }
        exit();
    }
}
