<?php
/* AKM Software.
 * @2015
 * mod_news Module Page
 * mod/mod_news/index.php
 */
class mod_news_mod extends indexMain
{
    public $mod_news_model;
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
        $this->lang = Load::lang('mod_news');
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
        if ($this->user->hasPermission('mod_news', 'add', $this->lang['mod_news']['role_errors']['add'], 1) === False) {
        // Check if it is an AJAX request
            if ($this->_isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['add']]);
                exit;
            }
            return False;
        }
        
        // AJAX Exception Handler
        if (Req::is_Post('add')) {
            try {
                $result = $this->_add();
                // Check if it is an AJAX request - if so, exit as _add() handles JSON
                if ($this->_isAjaxRequest()) {
                    exit; 
                }
            } catch (Exception $e) {
                // Log the error
                $logFile = root_dir . 'logs/ajax_errors.log';
                $logMessage = '[' . date('Y-m-d H:i:s') . '] AJAX Error in mod_news/add: ' . $e->getMessage() . "\n";
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
                throw $e; // Non-AJAX request, handle exception normally
            }
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_news']['add'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('wizard_module', 'mod_news');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_news_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_news` ORDER BY id DESC");
        $this->tema->veri('mod_news_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_news` ORDER BY id DESC");
        $this->tema->veri('mod_news_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
        $mod_news_nested = Load::model('mod_menu');
        $mod_news_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_news_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_news_category = Load::model('mod_news_category');
        $mod_news_category->select('id, name, lang');
        $mod_news_category->addWhere("parent_id != '0'");
        $mod_news_category->all();
        $categories = $mod_news_category->toArray();
        $this->tema->veri('categories', $categories);
        $this->tema->tpl('mod_news/add');
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_news', 'edit', $this->lang['mod_news']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_news_model = Load::model('mod_news');
        $id = Req::Url_Clear($id);
        if ($this->mod_news_model->exists_id($id) === False) {
            $this->_fatal_error($this->lang['mod_news']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_news/edit');
            return;
        }

        $d = $this->mod_news_model->getResultOne()->toArray();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_news', 'moderate_edit', $d['user_id'], $this->lang['mod_news']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_news']['edit'];
        $this->tema->veri('wizard_module', 'mod_news');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->flush();
        $mod_news_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_news_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_news` ORDER BY id DESC");
        $this->tema->veri('mod_news_gallery_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_news` ORDER BY id DESC");
        $this->tema->veri('mod_news_file_id', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_news_category = Load::model('mod_news_category');
        $mod_news_category->select('id, name, lang');
        $mod_news_category->addWhere("parent_id != '0'");
        $mod_news_category->all();
        $categories = $mod_news_category->toArray();
        $this->tema->veri('categories', $categories);
        $this->tema->tpl('mod_news/edit');
    }

    // List Page
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_news', 'list', $this->lang['mod_news']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_news']['list'];
        
        // Stats
        $mod_news = Load::model('mod_news');
        $mod_news->flush();
        $total_news = $mod_news->getCount();
        
        $mod_news->flush();
        $mod_news->where(['status' => 'n']);
        $draft_news = $mod_news->getCount();

        // Get All Categories for ID -> Name mapping in JS
        $mod_news_category = Load::model('mod_news_category');
        $mod_news_category->select('id, name, lang');
        $mod_news_category->addWhere("parent_id != '0'");
        $mod_news_category->all();
        $categories = $mod_news_category->toArray();

        $this->tema->veri('total_news', $total_news);
        $this->tema->veri('draft_news', $draft_news);
        $this->tema->veri('categories', $categories);
        $this->tema->veri('site', $this->site);

        $this->tema->tpl('mod_news/list');
    }

    public function image_remove_image()
    {
        // Check Edit Permission
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
        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_news', 'moderate_edit', $this->mod_news_model->user_id, $this->lang['mod_news']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_news']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_news_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_news';
            $image->url = $this->mod_news_model->image;
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
        if (
            Roll::hasPermission('mod_news', 'add') === FALSE
            && Roll::hasPermission('mod_news', 'edit') === FALSE
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
                    'name' => $item['name'] . ' (' . $this->lang['mod_news']['local'] . ')'
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
                    'name' => $item['name'] . ' (' . $this->lang['mod_news']['embed'] . ')'
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
            Roll::hasPermission('mod_news', 'add') === FALSE
            && Roll::hasPermission('mod_news', 'edit') === FALSE
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
        
        $this->val = Load::validator('mod_news.add');
        if ($this->val->check()) {
            // Validator found errors
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->errors),
                    'errors' => $this->val->errors
                ]);
                exit;
            }
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $this->mod_news_model = Load::model('mod_news');

            $this->mod_news_model->lang = Req::Post_Safe('lang');
            $this->mod_news_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_news_model->category_id = Req::Post_Safe('category_id');
            $this->mod_news_model->status = Roll::hasPermission('mod_news', 'change_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_news_model->created_at = Req::Post_Safe('created_at') ? Req::Post_Safe('created_at') : date('Y-m-d H:i:s');
            $this->mod_news_model->updated_at = date('Y-m-d H:i:s');
            $this->mod_news_model->deck = Req::Post_Safe('deck');

            $this->mod_news_model->summary = Req::Post('summary');
            $this->mod_news_model->summary = Helper::clear_html($this->mod_news_model->summary);

            $this->mod_news_model->content = Req::Post('content');
            $this->mod_news_model->content = Helper::clear_html($this->mod_news_model->content);

            $this->mod_news_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_news_model->file_id = Req::Post_Safe('file_id');
            $this->mod_news_model->acl_id = Req::Post_Safe('acl_id');
            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_news_model->video = serialize($data);
            } else {
                $this->mod_news_model->video = '';
            }

            $this->mod_news_model->user_id = $this->session->getir('id');

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_news';
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
                    $this->mod_news_model->image = $image->url;
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
                if (isset($this->mod_news_model->image)) {
                    unset($this->mod_news_model->image);
                }
            }

            if ($this->mod_news_model->save(TRUE)) {
                // If requested to add to menu
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_news_model->name;
                    $this->model->lang = $this->mod_news_model->lang;
                    $this->model->url = $this->mod_news_model->seo;
                    $this->model->status = $this->mod_news_model->status;
                    $this->model->acl_id = $this->mod_news_model->acl_id;
                    $this->model->url = $this->mod_news_model->lang . '/news/' . $this->model->url;
                    $this->model->save();
                }
                
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'success',
                        'message' => $this->lang['mod_news']['message']['oke']['add'],
                        'redirect' => $this->site['sadres'] . 'mod_news/list'
                    ]);
                    exit;
                }
                
                $this->_success_message($this->lang['mod_news']['message']['oke']['add']);
                Helper::yonlendir($this->site['sadres'] . 'mod_news/list', 1);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => $this->lang['mod_news']['message']['error']['add'] ?? 'Addition failed'
                    ]);
                    exit;
                }
                
                $this->_error_message($this->lang['mod_news']['message']['error']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_news.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $this->mod_news_model = Load::model('mod_news');

            $this->mod_news_model->id = $id;
            $this->mod_news_model->lang = Req::Post_Safe('lang');
            $this->mod_news_model->name = html_entity_decode(trim(Req::Post('name')), ENT_QUOTES, 'UTF-8');

            $this->mod_news_model->category_id = Req::Post_Safe('category_id');
            $this->mod_news_model->status = Roll::hasPermission('mod_news', 'edit_status') === FALSE ? 'n' : Req::Post_Safe('status');

            $this->mod_news_model->created_at = Req::Post_Safe('created_at');
            // $this->mod_news_model->updated_at = date('Y-m-d H:i:s'); // Already in ajax object if saved correctly
            $this->mod_news_model->deck = Req::Post_Safe('deck');

            $this->mod_news_model->summary = Req::Post('summary');
            $this->mod_news_model->summary = Helper::clear_html($this->mod_news_model->summary);

            $this->mod_news_model->content = Req::Post('content');
            $this->mod_news_model->content = Helper::clear_html($this->mod_news_model->content);

            $this->mod_news_model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->title = html_entity_decode(trim(Req::Post('title')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->keywords = html_entity_decode(trim(Req::Post('keywords')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->description = html_entity_decode(trim(Req::Post('description')), ENT_QUOTES, 'UTF-8');
            $this->mod_news_model->gallery_id = Req::Post_Safe('gallery_id');
            $this->mod_news_model->file_id = Req::Post_Safe('file_id');
            $this->mod_news_model->acl_id = Req::Post_Safe('acl_id');
            
            // Update if this content was previously sent to menu
            $new_mod_news = new mod_news_model();
            $new_mod_news->where_id($id)->gRo()->sync();
            $url = $new_mod_news->lang . '/news/' . $new_mod_news->seo;

            $videos = explode(',', Req::Post_Safe('video'));
            foreach ($videos as $video_key) {
                if ($video_key !== '') {
                    $data[] = $video_key;
                }
            }
            if (isset($data) && is_array($data)) {
                $data = array_unique($data);
                $this->mod_news_model->video = serialize($data);
            } else {
                $this->mod_news_model->video = '';
            }

            if (isset($this->mod_news_model->user_id)) {
                unset($this->mod_news_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {


                $image = Load::mod('image');
                $image->mod = 'mod_news';
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
                    // Before uploading new image, delete the old one
                    $old_data = Load::model('mod_news');
                    $old_data->where_id($id)->gRo()->sync();
                    if ($old_data->image != '') {
                        $image->mod = 'mod_news';
                        $image->url = $old_data->image;
                        $image->_delete_files($option);
                    }
                    $this->mod_news_model->image = $new_image_url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_news_model->image)) {
                    unset($this->mod_news_model->image);
                }
            }

            $this->mod_news_model->id = $id;
            if ($this->mod_news_model->update('id')) {
                // Update if this content was previously sent to menu
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_news_model->name;
                    $mod_menu_model->lang = $this->mod_news_model->lang;
                    $mod_menu_model->url = $this->mod_news_model->seo;
                    $mod_menu_model->status = $this->mod_news_model->status;
                    $mod_menu_model->acl_id = $this->mod_news_model->acl_id;
                    $mod_menu_model->url = $this->mod_news_model->lang . '/news/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }

                $this->_success_message($this->lang['mod_news']['message']['oke']['edit']);
                Helper::yonlendir($this->site['sadres'] . 'mod_news/list', 1);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_news']['message']['error']['edit']);
            }
        }
    }

    function delete()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_news',  'delete', $this->lang['mod_news']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Success';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_news']['errors']['record']);
        }
    }

    public function _delete($delete)
    {
        $this->mod_news_model = Load::model('mod_news');
        foreach ($delete as $s) {
            $this->mod_news_model->flush();
            $this->mod_news_model->where_id($s)->gRo();
            if ($this->mod_news_model->getSize() === 0) {
                exit($this->lang['mod_news']['errors']['record']);
            }
            $this->mod_news_model->sync();
            // Check Moderate Permission if content belongs to another user
            if ($this->user->checkPermission('mod_news', 'moderate_delete', $this->mod_news_model->user_id, $this->lang['mod_news']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_news") === TRUE) {
                // Delete Gallery if exists for content
                Run::import('mod_gallery', '_remove', ['mod_news', $this->mod_news_model->id]);
            }
            // Check if module exists and File is being added
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_news") === TRUE) {
                // Delete File if exists for content
                Run::import('mod_attachment', '_remove', ['mod_news', $this->mod_news_model->id]);
            }

            if ($this->mod_news_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_news';
                $image->name = 'image';
                $image->url = $this->mod_news_model->image;
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

            $this->mod_news_model->delete();
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
        // Check Permission
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

        // Check Moderate Permission if content belongs to another user
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
                    // Before registering new image, delete the old one (if exists)
                    if ($this->mod_news_model->image != '') {
                        $image->mod = 'mod_news';
                        $image->url = $this->mod_news_model->image;
                        $image->_delete_files($option);
                    }

                    // Instatantiate a fresh model for update to avoid any state issues
                    $update_model = Load::model('mod_news');
                    $update_model->id = $id;
                    $update_model->updated_at = date('Y-m-d H:i:s');
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        if (ob_get_level() > 0) ob_clean();
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['radres'] . 'mod_news/thumb/' . $new_image_url . '?v=' . time()
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
        } else {
            if (ob_get_level() > 0) ob_clean();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_news']['message']['error']['add']]);
        }
    }
    public function datatable_json()
    {
        $i = Load::model('mod_news');

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
            (Roll::hasPermission('mod_news', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_news', 'moderate_delete') !== TRUE)
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
            'y' => ['success', $this->lang['mod_news']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_news']['admin']['radio']['publish_no']],
        ];

        $deck_list = [
            'y' => ['success', $this->lang['mod_news']['admin']['radio']['deck_yes']],
            'n' => ['danger', $this->lang['mod_news']['admin']['radio']['deck_no']],
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
                if (Roll::hasPermission('mod_news',  'delete')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button'][ 'delete'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_news', 'gallery')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['gallery'] . '</span> </button>';
                }

                if (Roll::hasPermission('mod_news', 'file')) {
                    $last .= '<button onclick="mod_news_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['file'] . '</span> </button>';
                }
                
                // Publish/Unpublish Button
                if ($content['status'] == 'y') {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_news_table_ajax.akm.processQuickUnpublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                     $last .= '<button class="btn btn-xs btn-' . $status_list[$content['status']]['0'] . ' hidden-xs" onclick="mod_news_table_ajax.akm.processQuickPublish(' . $content['id'] . '); return false;" title="' . $status_list[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
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
                    '<div style="cursor:pointer;" onclick="mod_news_table_ajax.akm.processImageUpload(' . $content['id'] . '); return false;" title="' . $this->lang['mod_news']['upload_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['radres'] . 'mod_news/thumb/' . $content['image'] . '" width="50" height="50" style="object-fit:cover;">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_news']['add_image'] . '</span></button>') .
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
        
        $totalModel = Load::model('mod_news');
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalModel->getCount(),
        ]);
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
                'in_hero_slider' => $new_slider_state === 'y',
                'message' => $new_slider_state === 'y' ? 'Sent to slider.' : 'Removed from slider.'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'Slider update failed.'
        ]);
    }

    private function _hasSliderColumn($table)
    {
        $this->db->fetch_array("SHOW COLUMNS FROM `" . $table . "` LIKE 'slider'");
        $result = $this->db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    // Approve Page
    public function approve()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_news', 'approval', $this->lang['mod_news']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_approve($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _approve($query = NULL)
    {
        // Load mod_news Model
        $this->mod_news_model = Load::model('mod_news');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_news_model->id = $s;
                $this->mod_news_model->status = 'y';
                if ($this->mod_news_model->update('id')) {
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
        if ($this->user->hasPermission('mod_news', 'approval', $this->lang['mod_news']['role_errors']['approval']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unapprove($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _unapprove($query = NULL)
    {
        // Load mod_news Model
        $this->mod_news_model = Load::model('mod_news');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_news_model->id = $s;
                $this->mod_news_model->status = 'n';
                if ($this->mod_news_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Success Message
    private function _success_message($message)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('message', $message);
    }

    // Error Message
    private function _error_message($message)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('errors', [$message]);
    }

    // Error Message
    private function _fatal_error($message)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('message', $message);
    }

    // Check if request is AJAX (for wizard and other AJAX calls)
    private function _isAjaxRequest()
    {
        // Simplest way: check POST parameter
        if (Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1') {
            return true;
        }
        
        // Fallback: check Header
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }
}
