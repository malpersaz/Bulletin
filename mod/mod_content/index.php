<?php
/* AKM Software.
 * @2015
 * mod_content Module Page
 * mod/mod_content/index.php
 */
class mod_content_mod extends indexMain
{
    public $mod_content_model;
    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Kayit::getir('site_config');
        // Load Language File
        $this->lang = Load::lang('mod_content');
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
        if ($this->user->hasPermission('mod_content', 'add', $this->lang['mod_content']['role_errors']['add'], 1) === False) {
            // Return JSON for AJAX requests.
            if ($this->_isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['add']]);
                exit;
            }
            return False;
        }
        
        // Handle AJAX exceptions.
        if (Req::is_Post('add')) {
            try {
                $result = $this->_add();
                // Stop after the JSON response is sent for AJAX requests.
                if ($this->_isAjaxRequest()) {
                    exit; // _add() allready sends JSON and exits for AJAX
                }
            } catch (Exception $e) {
                // Log the error
                $logFile = root_dir . 'logs/ajax_errors.log';
                $logMessage = '[' . date('Y-m-d H:i:s') . '] AJAX Error in mod_content/add: ' . $e->getMessage() . "\n";
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
                throw $e; // Non-AJAX request, let normal exception handling continue.
            }
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_content']['add'];
        $this->tema->tpl('mod_content/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_category_nested = Load::model('mod_category');
        $mod_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_content` ORDER BY id DESC");
        $this->tema->veri('mod_content_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_content` ORDER BY id DESC");
        $this->tema->veri('mod_content_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
        $mod_content_nested = Load::model('mod_menu');
        $mod_content_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_content_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_category = Load::model('mod_category');
        $mod_category->select('id, name, lang');
        $mod_category->addWhere("parent_id != '0'");
        $mod_category->all();
        $categories = $mod_category->toArray();
        $this->tema->veri('categories', $categories);
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_content', 'edit', $this->lang['mod_content']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_content_model = Load::model('mod_content');
        $id = Req::Url_Clear($id);
        if ($this->mod_content_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_content']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_content/edit');
            return;
        }

        $d = $this->mod_content_model->getResultOne()->toArray();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_content', 'moderate_edit', $d['user_id'], $this->lang['mod_content']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_content']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_content/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_category_nested = Load::model('mod_category');
        $mod_category_nested->flush();
        $mod_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_content` ORDER BY id DESC");
        $this->tema->veri('mod_content_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_content` ORDER BY id DESC");
        $this->tema->veri('mod_content_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_category = Load::model('mod_category');
        $mod_category->select('id, name, lang');
        $mod_category->addWhere("parent_id != '0'");
        $mod_category->all();
        $categories = $mod_category->toArray();
        $this->tema->veri('categories', $categories);
    }

    // List Page
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_content', 'list', $this->lang['mod_content']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_content']['list'];
        
        // Stats
        $mod_content = Load::model('mod_content');
        $mod_content->flush();
        $total_content = $mod_content->getCount();
        
        $mod_content->flush();
        $mod_content->where(['status' => 'n']);
        $draft_content = $mod_content->getCount();

        // Get All Categories for ID -> Name mapping in JS
        $mod_category = Load::model('mod_category');
        $mod_category->select('id, name, lang');
        $mod_category->addWhere("parent_id != '0'");
        $mod_category->all();
        $categories = $mod_category->toArray();

        $this->tema->veri('total_content', $total_content);
        $this->tema->veri('draft_content', $draft_content);
        $this->tema->veri('categories', $categories);
        $this->tema->veri('site', $this->site);

        $this->tema->tpl('mod_content/list');
    }

    public function image_remove_image()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_content', 'edit', $this->lang['mod_content']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_content_model = Load::model('mod_content');
        $this->mod_content_model->where_id($id)->gRo();
        if ($this->mod_content_model->getSize() === 0) {
            echo $this->lang['mod_content']['errors']['record'];
            return FALSE;
        }

        $this->mod_content_model->sync();
        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_content', 'moderate_edit', $this->mod_content_model->user_id, $this->lang['mod_content']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_content']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_content_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_content';
            $image->url = $this->mod_content_model->image;
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
                    $this->mod_content_model->image = '';
                    $this->mod_content_model->update('id');
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
        if (
            Roll::hasPermission('mod_content', 'add') === FALSE
            && Roll::hasPermission('mod_content', 'edit') === FALSE
        ) {
            exit();
        }

        $results = [];
        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;

        // Search in mod_video (local)
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
                    'name' => $item['name'] . ' (' . $this->lang['mod_content']['lokal'] . ')'
                ];
            }
        }

        // Search in mod_video_embed (embed)
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

        if (count($results) > 0) {
            echo json_encode($results);
        } else {
            echo '[]';
        }
    }

    public function all_video_get($str = NULL)
    {
        if (
            Roll::hasPermission('mod_content', 'add') === FALSE
            && Roll::hasPermission('mod_content', 'edit') === FALSE
        ) {
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
                        'name' => $item['name'] . ' (' . $this->lang['mod_content']['lokal'] . ')'
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
                        'name' => $item['name'] . ' (' . $this->lang['mod_content']['embed'] . ')'
                    ];
                }
            }
        }

        if (count($results) > 0) {
            echo json_encode($results);
        } else {
            echo '[]';
        }
    }

    // Add Function
    private function _add()
    {
        $isAjax = $this->_isAjaxRequest();
        
        $this->val = Load::validator('mod_content.add');
        if ($this->val->check()) {
            // Validation failed.
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
            $this->mod_content_model = Load::model('mod_content');

            $this->mod_content_model->lang = Req::Post_Safe('lang');
            $this->mod_content_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_content_model->category_id = Req::Post_Safe('category_id');
            $this->mod_content_model->status = Roll::hasPermission('mod_content', 'change_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_content_model->created_at = Req::Post_Safe('created_at') ? Req::Post_Safe('created_at') : date('Y-m-d H:i:s');
            $this->mod_content_model->updated_at = date('Y-m-d H:i:s');
            $this->mod_content_model->deck = Req::Post_Safe('deck');

            $this->mod_content_model->summary = Req::Post('summary');
            $this->mod_content_model->summary = Helper::clear_html($this->mod_content_model->summary);

            $this->mod_content_model->content = Req::Post('content');
            $this->mod_content_model->content = Helper::clear_html($this->mod_content_model->content);

            $this->mod_content_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_content_model->file_id = Req::Post_Safe('file_id');
            $this->mod_content_model->acl_id = Req::Post_Safe('acl_id');
            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_content_model->video = serialize($data);
            } else {
                $this->mod_content_model->video = '';
            }

            $this->mod_content_model->user_id = $this->session->getir('id');

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_content';
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
                    $this->mod_content_model->image = $image->url;
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
                if (isset($this->mod_content_model->image)) {
                    unset($this->mod_content_model->image);
                }
            }

            if ($this->mod_content_model->save(TRUE)) {
                // If requested to add to menu
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_content_model->name;
                    $this->model->lang = $this->mod_content_model->lang;
                    $this->model->url = $this->mod_content_model->seo;
                    $this->model->status = $this->mod_content_model->status;
                    $this->model->acl_id = $this->mod_content_model->acl_id;
                    $this->model->url = $this->mod_content_model->lang . '/i/' . $this->model->url;
                    $this->model->save();
                }
                
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'success',
                        'message' => $this->lang['mod_content']['message']['oke']['add'],
                        'redirect' => $this->site['sadres'] . 'mod_content/list'
                    ]);
                    exit;
                }
                
                $this->_oke_message($this->lang['mod_content']['message']['oke']['add']);
                Helper::yonlendir($this->site['sadres'] . 'mod_content/list', 1);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => $this->lang['mod_content']['message']['error']['add'] ?? 'Add operation failed'
                    ]);
                    exit;
                }
                
                $this->_error_message($this->lang['mod_content']['message']['oke']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_content.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_content_model = Load::model('mod_content');

            $this->mod_content_model->id = $id;
            $this->mod_content_model->lang = Req::Post_Safe('lang');
            $this->mod_content_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_content_model->category_id = Req::Post_Safe('category_id');
            $this->mod_content_model->status = Roll::hasPermission('mod_content', 'edit_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_content_model->created_at = Req::Post_Safe('created_at');
            // $this->mod_content_model->updated_at = date('Y-m-d H:i:s'); // Already in ajax object if saved correctly
            $this->mod_content_model->deck = Req::Post_Safe('deck');

            $this->mod_content_model->summary = Req::Post('summary');
            $this->mod_content_model->summary = Helper::clear_html($this->mod_content_model->summary);

            $this->mod_content_model->content = Req::Post('content');
            $this->mod_content_model->content = Helper::clear_html($this->mod_content_model->content);

            $this->mod_content_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_content_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_content_model->file_id = Req::Post_Safe('file_id');
            $this->mod_content_model->acl_id = Req::Post_Safe('acl_id');
            
            // Update if this content was previously sent to menu
            $new_mod_content = new mod_content_model();
            $new_mod_content->where_id($id)->gRo()->sync();
            $url = $new_mod_content->lang . '/i/' . $new_mod_content->seo;

            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_content_model->video = serialize($data);
            } else {
                $this->mod_content_model->video = '';
            }

            if (isset($this->mod_content_model->user_id)) {
                unset($this->mod_content_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {


                $image = Load::mod('image');
                $image->mod = 'mod_content';
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
                    $old_data = Load::model('mod_content');
                    $old_data->where_id($id)->gRo()->sync();
                    if ($old_data->image != '') {
                        $image->mod = 'mod_content';
                        $image->url = $old_data->image;
                        $image->_delete_files($option);
                    }
                    $this->mod_content_model->image = $new_image_url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_content_model->image)) {
                    unset($this->mod_content_model->image);
                }
            }

            $this->mod_content_model->id = $id;
            if ($this->mod_content_model->update('id')) {
                // Update if this content was previously sent to menu
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_content_model->name;
                    $mod_menu_model->lang = $this->mod_content_model->lang;
                    $mod_menu_model->url = $this->mod_content_model->seo;
                    $mod_menu_model->status = $this->mod_content_model->status;
                    $mod_menu_model->acl_id = $this->mod_content_model->acl_id;
                    $mod_menu_model->url = $this->mod_content_model->lang . '/i/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }

                $this->_oke_message($this->lang['mod_content']['message']['oke']['edit']);
                Helper::yonlendir($this->site['sadres'] . 'mod_content/list', 1);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_content']['message']['error']['edit']);
            }
        }
    }

    function delete()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_content',  'delete', $this->lang['mod_content']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Success';
            } else {
                echo 'Error';
            }
        } else {
            exit($this->lang['mod_content']['errors']['record']);
        }
    }

    public function _delete($delete)
    {
        $this->mod_content_model = Load::model('mod_content');
        foreach ($delete as $s) {
            $this->mod_content_model->flush();
            $this->mod_content_model->where_id($s)->gRo();
            if ($this->mod_content_model->getSize() === 0) {
                exit($this->lang['mod_content']['errors']['record']);
            }
            $this->mod_content_model->sync();
            // Check Moderate Permission if content belongs to another user
            if ($this->user->checkPermission('mod_content', 'moderate_delete', $this->mod_content_model->user_id, $this->lang['mod_content']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_content") === TRUE) {
                // Delete Gallery if exists for content
                Run::import('mod_gallery', '_remove', ['mod_content', $this->mod_content_model->id]);
            }
            // Check if module exists and File is being added
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_content") === TRUE) {
                // Delete File if exists for content
                Run::import('mod_attachment', '_remove', ['mod_content', $this->mod_content_model->id]);
            }

            if ($this->mod_content_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_content';
                $image->name = 'image';
                $image->url = $this->mod_content_model->image;
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

            $this->mod_content_model->delete();
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
        if (Roll::hasPermission('mod_content', 'add') === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['add']]);
            return;
        }

        $title = trim(Req::Post('title'));
        if (!$title) {
            echo json_encode(['status' => 'error', 'message' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['name'])]);
            return;
        }

        $this->mod_content_model = Load::model('mod_content');
        $this->mod_content_model->where_name($title)->gRo();
        
        if ($this->mod_content_model->getSize() > 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['validator']['title_exists']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_content']['validator']['title_unique']]);
        }
    }

    public function ajax_image_upload()
    {
        // Check Permission
        if ($this->user->hasPermission('mod_content', 'edit', $this->lang['mod_content']['role_errors']['edit'], 1) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['errors']['record']]);
            return;
        }

        $this->mod_content_model = Load::model('mod_content');
        $this->mod_content_model->where_id($id)->gRo();
        if ($this->mod_content_model->getSize() === 0) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['errors']['record']]);
            return;
        }
        $this->mod_content_model->sync();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_content', 'moderate_edit', $this->mod_content_model->user_id, $this->lang['mod_content']['role_errors']['moderate_edit']) === False) {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['role_errors']['moderate_edit']]);
            return;
        }

        if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
            $image = Load::mod('image');
            $image->mod = 'mod_content';
            $image->name = 'image';
            $option = [
                '/' => ['width' => 1200, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 250, 'height' => NULL, 'option' => 'width'],
            ];

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Remove the previous image before saving the new one.
                    if ($this->mod_content_model->image != '') {
                        $image->mod = 'mod_content';
                        $image->url = $this->mod_content_model->image;
                        $image->_delete_files($option);
                    }

                    // Instatantiate a fresh model for update to avoid any state issues
                    $update_model = Load::model('mod_content');
                    $update_model->id = $id;
                    $update_model->updated_at = date('Y-m-d H:i:s');
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['radres'] . 'mod_content/thumb/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['message']['error']['edit']]);
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
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_content']['message']['error']['add']]);
        }
    }
    public function datatable_json()
    {
        $i = Load::model('mod_content');

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
            (Roll::hasPermission('mod_content', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_content', 'moderate_delete') !== TRUE)
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
            'y' => ['success', $this->lang['mod_content']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_content']['admin']['radio']['publish_no']],
        ];

        $deck_list = [
            'y' => ['success', $this->lang['mod_content']['admin']['radio']['deck_yes']],
            'n' => ['danger', $this->lang['mod_content']['admin']['radio']['deck_no']],
        ];

        // Duplicate loop removed.
        // The correct loop is below, after user ID collection.
        
        // Collect user IDs
        $user_ids = [];
        
        $records = $i->toArray();
        if (is_array($records)) {
            foreach ($records as $item) {
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

        if ($getCount > 0 && is_array($records)) {
            foreach ($records as $content) {

                $last = "";
                if (Roll::hasPermission('mod_content', 'edit')) {
                    $last .= '<button onclick="mod_content_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['edit'] . '</span> </button>';
                }
                if (Roll::hasPermission('mod_content',  'delete')) {
                    $last .= '<button onclick="mod_content_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button'][ 'delete'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_content', 'gallery')) {
                    $last .= '<button onclick="mod_content_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['gallery'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_content', 'file')) {
                    $last .= '<button onclick="mod_content_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_content']['button']['file'] . '</span> </button>';
                }
                
                // Publish/Unpublish Button
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_content_table_ajax.akm.processQuickUnpublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_content_table_ajax.akm.processQuickPublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
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
                    '<div style="cursor:pointer;" onclick="mod_content_table_ajax.akm.processImageUpload(' . $content['id'] . '); return false;" title="' . $this->lang['mod_content']['resim_yukle_degistir'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['radres'] . 'mod_content/thumb/' . $content['image'] . '" width="50" height="50" style="object-fit:cover;">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_content']['resim_add'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'], // 7: Category ID
                    $content['status'],       // 8: Status (y/n)
                    $author_name             // 9: Author Name
                ];
            }
        }
        // Clean any output buffer to ensure valid JSON
        if (ob_get_level() > 0) ob_clean();
        
        $totalModel = Load::model('mod_content');
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalModel->getCount(),
        ]);
    }

    // Approve Page
    public function approve()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_content', 'approval', $this->lang['mod_content']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_approve($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'Error';
        }
    }

    private function _approve($query = NULL)
    {
        // Load mod_content Model
        $this->mod_content_model = Load::model('mod_content');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_content_model->id = $s;
                $this->mod_content_model->status = 'y';
                if ($this->mod_content_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }
    // Disapprove Page
    public function unapprove()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_content', 'approval', $this->lang['mod_content']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unapprove($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'Error';
        }
    }

    private function _unapprove($query = NULL)
    {
        // Load mod_content Model
        $this->mod_content_model = Load::model('mod_content');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_content_model->id = $s;
                $this->mod_content_model->status = 'n';
                if ($this->mod_content_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Success Message
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Error Message
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Message
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Check if request is AJAX (for wizard and other AJAX calls)
    private function _isAjaxRequest()
    {
        // Fast path: check the explicit POST flag first.
        if (Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1') {
            return true;
        }
        
        // Fallback: inspect the AJAX request header.
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }
}
