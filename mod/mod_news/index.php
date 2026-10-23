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
    protected $has_faq = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_news_category' => 'select_nested_category_id_result',
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $need_content_refs = [
        'mod_gallery_mod' => ['mod_news_gallery_id'],
        'mod_attachment' => ['mod_news_file_id']
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
        'deck' => ['type' => 'safe'],
        'summary' => ['type' => 'html'],
        'content' => ['type' => 'html']
    ];

    protected $menu_url_pattern = '{lang}/news/{seo}';

    public function __construct()
    {
        parent::__construct('mod_news');
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Get All Categories for Wizard (Flat List with Lang)
        $mod_news_category = Load::model('mod_news_category');
        $mod_news_category->select('id, name, lang');
        $mod_news_category->addWhere("root != 'y'");
        $mod_news_category->order('order', 'ASC')->all();
        $data['categories'] = $mod_news_category->toArray();
        $data['categories_flat'] = $data['categories'];

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
        // Check list permission
        if ($this->user->hasPermission('mod_news', 'list', $this->lang['mod_news']['role_errors']['list'], 1) === False) {
            return False;
        }

        $i = Load::model('mod_news');
        $i->select('id, name, image, lang, status, created_at, category_id, user_id, slider, deck');
        
        $limit = 10;
        $offset = 0;
        $order = 'id';
        $sort = 'desc';

        $columns = ['id', 'name', 'image', 'lang', 'status', 'created_at', 'category_id', 'user_id', 'slider', 'deck'];

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
        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }

        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $k => $v) {
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

        if (Role::hasPermission('mod_news', 'moderate_edit') !== TRUE && Role::hasPermission('mod_news', 'moderate_delete') !== TRUE) {
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
                if (Role::hasPermission('mod_news', 'edit')) {
                    $last .= '<button data-action="edit" data-id="' . $content['id'] . '" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['edit'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_news', 'delete')) {
                    $last .= '<button data-action="delete" data-id="' . $content['id'] . '" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['delete'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_news', 'gallery')) {
                    $last .= '<button data-action="gallery" data-id="' . $content['id'] . '" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['gallery'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_news', 'file')) {
                    $last .= '<button data-action="file" data-id="' . $content['id'] . '" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_news']['button']['file'] . '</span> </button>';
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
                    '<div class="table-img-trigger" data-action="upload-image" data-id="' . $content['id'] . '" title="' . $this->lang['mod_news']['upload_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['iaddress'] . 'mod_news/thumb/' . $content['image'] . '" width="50" height="50" class="table-thumb-cover">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_news']['add_image'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['created_at'],
                    $last,
                    $content['category_id'],
                    $content['status'],
                    $author_name,
                    isset($content['slider']) && $content['slider'] === 'y' ? 1 : 0,
                    isset($content['deck']) && $content['deck'] === 'y' ? 1 : 0
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        $modelClass = Load::modelfile('mod_news');
        $totalModel = new $modelClass();
        if (
            (Role::hasPermission('mod_news', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_news', 'moderate_delete') !== TRUE)
        ) {
            $totalModel->where(['user_id' => $this->session->get('id')]);
        }
        $totalCount = $totalModel->getCount();

        $draftModel = new $modelClass();
        $draftWheres = ['status' => 'n'];
        if (
            (Role::hasPermission('mod_news', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_news', 'moderate_delete') !== TRUE)
        ) {
            $draftWheres['user_id'] = $this->session->get('id');
        }
        $draftModel->where($draftWheres);
        $draftCount = $draftModel->getCount();
        $publishedCount = max(0, $totalCount - $draftCount);

        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $totalCount,
            'totalNews' => $totalCount,
            'publishedNews' => $publishedCount,
            'draftNews' => $draftCount,
        ]);
    }

    public function all_video()
    {
        if (Role::hasPermission('mod_news', 'add') === false && Role::hasPermission('mod_news', 'edit') === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }

        $results = [];
        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;

        if (BridgeCleanupService::tableExists('mod_video')) {
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
        }

        if (BridgeCleanupService::tableExists('mod_video_embed')) {
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
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
        return true;
    }

    public function all_video_get($str = null)
    {
        if (Role::hasPermission('mod_news', 'add') === false && Role::hasPermission('mod_news', 'edit') === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }
        if ($str === null) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }

        $str = base64_decode($str);
        $data = @unserialize($str, ['allowed_classes' => false]);

        if (is_array($data) === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }

        $video_ids = [];
        $vid_ids = [];

        foreach ($data as $prefixed_id) {
            $parts = explode(':', $prefixed_id, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 'video') {
                    $video_ids[] = intval($parts[1]);
                } elseif ($parts[0] === 'vid') {
                    $vid_ids[] = intval($parts[1]);
                }
            }
        }

        $results = [];

        if ($video_ids !== [] && BridgeCleanupService::tableExists('mod_video')) {
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

        if ($vid_ids !== [] && BridgeCleanupService::tableExists('mod_video_embed')) {
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

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
        return true;
    }

    public function ajax_check_title()
    {
        if (Role::hasPermission('mod_news', 'add') === false) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_news']['role_errors']['add']]);
            return;
        }

        $title = trim(Req::Post('title'));
        if ($title === '' || $title === '0') {
            $this->jsonResponse(['status' => 'error', 'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['name'])]);
            return;
        }

        $this->mod_news_model = Load::model('mod_news');
        $this->mod_news_model->where_name($title)->gRo();

        if ($this->mod_news_model->getSize() > 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_news']['validator']['title_exists']]);
        } else {
            $this->jsonResponse(['status' => 'success', 'message' => $this->lang['mod_news']['validator']['title_unique']]);
        }
    }

    /**
     * AJAX AI Assistant endpoint for editorial tasks (deck generation, headlines, SEO)
     */
    public function ajax_ai_assist()
    {
        if (Role::hasPermission('mod_news', 'edit') === false && Role::hasPermission('mod_news', 'add') === false) {
            $this->jsonResponse([
                'success' => false,
                'error' => $this->lang['mod_news']['role_errors']['edit']
            ]);
            return;
        }

        $action = trim((string) Req::Post_Safe('action'));
        $title = trim((string) Req::Post_Safe('title'));
        $content = trim((string) Req::Post_Safe('content'));
        $lang = strtolower(trim((string) Req::Post_Safe('lang')));
        if (empty($lang)) {
            $lang = Registry::has('lang') ? Registry::get('lang') : ($this->site['lang'] ?? 'en');
        }

        $aiService = new AIService();
        if (!$aiService->hasApiKey()) {
            $this->jsonResponse([
                'success' => false,
                'error' => $this->lang['mod_news']['ai']['missing_key']
            ]);
            return;
        }

        switch ($action) {
            case 'generate_deck':
                if (empty($content) && empty($title)) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => $this->lang['mod_news']['ai']['content_required']
                    ]);
                    return;
                }
                $res = $aiService->generateDeck($title, $content, $lang);
                $this->jsonResponse($res);
                return;

            case 'suggest_headlines':
                if (empty($content) && empty($title)) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => $this->lang['mod_news']['ai']['content_required']
                    ]);
                    return;
                }
                $res = $aiService->suggestHeadlines($content, $title, $lang);
                $this->jsonResponse($res);
                return;

            case 'generate_seo':
                $res = $aiService->generateSeoMeta($title, $content, $lang);
                $this->jsonResponse($res);
                return;

            default:
                $this->jsonResponse([
                    'success' => false,
                    'error' => $this->lang['mod_news']['ai']['invalid_action']
                ]);
                return;
        }
    }

    private function toggleFlag(string $column, ?string $forceValue = null, ?string $actionRole = null)
    {
        $hasEdit = Role::hasPermission('mod_news', 'edit');
        $hasActionRole = $actionRole !== null ? Role::hasPermission('mod_news', $actionRole) : false;

        if (!$hasEdit && !$hasActionRole) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_news']['role_errors']['edit'],
            ]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            $query = Req::Post_Safe('query');
            if (is_array($query) && !empty($query)) {
                $id = intval($query[0]);
            }
        }
        if ($id <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_news']['errors']['record'],
            ]);
            return;
        }

        $newsModel = Load::model('mod_news');
        $newsModel->where_id($id)->gRo();
        if ($newsModel->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_news']['errors']['record'],
            ]);
            return;
        }

        $newsModel->sync();
        $record = $newsModel->toArray();
        if ($this->user->checkPermission('mod_news', 'moderate_edit', $record['user_id'], $this->lang['mod_news']['role_errors']['moderate_edit'], 1) === false) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_news']['role_errors']['moderate_edit'],
            ]);
            return;
        }

        $currentVal = isset($record[$column]) ? (string) $record[$column] : 'n';
        $newVal = $forceValue !== null ? $forceValue : ($currentVal === 'y' ? 'n' : 'y');

        $updateModel = Load::model('mod_news');
        $updateModel->id = $id;
        $updateModel->$column = $newVal;
        $updateModel->updated_at = date('Y-m-d H:i:s');

        if ($updateModel->update('id')) {
            $response = [
                'status' => 'success',
                $column => $newVal,
            ];
            if ($column === 'slider') {
                $response['in_hero_slider'] = ($newVal === 'y');
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_news']['hero_slider_added']
                    : $this->lang['mod_news']['hero_slider_removed'];
            } elseif ($column === 'deck') {
                $response['in_deck'] = ($newVal === 'y');
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_news']['deck_added']
                    : $this->lang['mod_news']['deck_removed'];
            } elseif ($column === 'status') {
                $response['is_published'] = ($newVal === 'y');
                $response['new_status'] = $newVal;
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_news']['context_menu']['published_msg']
                    : $this->lang['mod_news']['context_menu']['unpublished_msg'];
            }
            $this->jsonResponse($response);
            return;
        }

        $errorMessage = $this->lang['mod_news']['errors']['record'];
        if ($column === 'slider') {
            $errorMessage = $this->lang['mod_news']['hero_slider_error'];
        } elseif ($column === 'deck') {
            $errorMessage = $this->lang['mod_news']['deck_error'];
        }

        $this->jsonResponse([
            'status' => 'error',
            'message' => $errorMessage,
        ]);
    }

    public function toggle_hero_slider()
    {
        $this->toggleFlag('slider');
    }

    public function toggle_deck()
    {
        $this->toggleFlag('deck');
    }

    public function toggle_status()
    {
        $this->toggleFlag('status', null, 'approval');
    }

    public function approve()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'y', $query, 'approval')) {
            $this->echoSuccessResponse();
            return;
        }
        echo 'ERROR';
    }

    public function unapprove()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'n', $query, 'approval')) {
            $this->echoSuccessResponse();
            return;
        }
        echo 'ERROR';
    }
}
