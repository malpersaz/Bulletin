<?php
/**
 * Amed Framework
 * mod_event Module Page - Extending BaseModuleController
 * mod/mod_event/index.php
 */
class mod_event_mod extends BaseModuleController
{
    public $mod_event_model;

    protected $has_gallery = true;
    protected $has_files = true;
    protected $has_faq = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_event_category' => 'select_nested_category_id_result',
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $need_content_refs = [
        'mod_gallery_mod' => ['mod_event_gallery_id'],
        'mod_attachment' => ['mod_event_file_id'],
        'mod_faq_mod' => ['mod_event_faq_id']
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 800,
                    'height' => 1066,
                    'option' => 'cover',
                    'quality' => 82,
                ],
                '/thumb' => [
                    'width' => 400,
                    'height' => 533,
                    'option' => 'cover',
                    'quality' => 78,
                ],
            ]
        ]
    ];

    protected $special_fields = [
        'deck' => ['type' => 'safe'],
        'summary' => ['type' => 'html'],
        'content' => ['type' => 'html']
    ];

    protected $menu_url_pattern = '{lang}/event/{seo}';

    public function __construct()
    {
        parent::__construct('mod_event');
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Get all categories for wizard (flat list with language)
        $categoryModel = Load::model('mod_event_category');
        $categoryModel->select('id, name, lang');
        $categoryModel->addWhere("root != 'y'");
        $categoryModel->order('order', 'ASC')->all();
        $data['categories'] = $categoryModel->toArray();
        $data['categories_flat'] = $data['categories'];

        return $data;
    }

    protected function beforeSave($model)
    {
        $model->created_at = $this->normalizeDateTime(Req::Post_Safe('created_at'));
        $model->updated_at = date('Y-m-d H:i:s');

        $startDate = Req::Post_Safe('start_date');
        $endDate = Req::Post_Safe('end_date');
        $model->start_date = !empty($startDate) ? $this->normalizeDateTime($startDate) : date('Y-m-d H:i:s');
        $model->end_date = !empty($endDate) ? $this->normalizeDateTime($endDate) : $model->start_date;

        $model->user_id = $this->session->get('id');
        $model->location = Req::Post_Safe('location');
        $model->participants = Req::Post_Safe('participants');
        $model->guests = Req::Post_Safe('guests');
        $model->subject = Req::Post_Safe('subject');

        if (empty($model->seo) && !empty($model->name)) {
            $model->seo = Helper::legacy_seo($model->name);
        }

        $model->video = $this->serializeVideos(Req::Post_Safe('video'));

        return $model;
    }

    protected function beforeUpdate($model, $id)
    {
        $model->updated_at = date('Y-m-d H:i:s');
        if (isset($model->created_at)) {
            $model->created_at = $this->normalizeDateTime($model->created_at);
        }

        $startDate = Req::Post_Safe('start_date');
        $endDate = Req::Post_Safe('end_date');
        if (!empty($startDate)) {
            $model->start_date = $this->normalizeDateTime($startDate);
        }
        if (!empty($endDate)) {
            $model->end_date = $this->normalizeDateTime($endDate);
        }

        if (Req::is_Post('location')) {
            $model->location = Req::Post_Safe('location');
        }
        if (Req::is_Post('participants')) {
            $model->participants = Req::Post_Safe('participants');
        }
        if (Req::is_Post('guests')) {
            $model->guests = Req::Post_Safe('guests');
        }
        if (Req::is_Post('subject')) {
            $model->subject = Req::Post_Safe('subject');
        }

        if (empty($model->seo) && !empty($model->name)) {
            $model->seo = Helper::legacy_seo($model->name);
        }

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
            'start_date|datetime',
            'category_id|custom',
            'user_id|custom'
        ];
    }

    public function datatable_json()
    {
        if ($this->user->hasPermission('mod_event', 'list', $this->lang['mod_event']['role_errors']['list'], 1) === false) {
            return false;
        }

        $itemModel = Load::model('mod_event');
        $itemModel->select('id, name, image, lang, status, start_date, end_date, location, category_id, user_id, slider, deck, created_at');

        $limit = 10;
        $offset = 0;
        $order = 'id';
        $sort = 'desc';

        $columns = ['id', 'name', 'image', 'lang', 'status', 'start_date', 'end_date', 'location', 'category_id', 'user_id', 'slider', 'deck', 'created_at'];

        if (Req::is_Post('order')) {
            $orderParam = Req::Post_Safe('order');
            if (isset($orderParam[0]['column']) && ($orderParam[0]['column'] > 0) && isset($columns[$orderParam[0]['column'] - 1])) {
                $order = $columns[$orderParam[0]['column'] - 1];
            }
            if (isset($orderParam[0]['dir']) && in_array($orderParam[0]['dir'], ['asc', 'desc'])) {
                $sort = $orderParam[0]['dir'];
            }
        }
        $itemModel->order($order, $sort);

        $wheres = [];
        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $columnKey => $columnVal) {
                if (in_array($columnKey, $columns) && $columnVal !== '') {
                    $wheres[] = $columnKey . " LIKE '%" . Req::Clear($columnVal) . "%'";
                }
            }
        }

        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $columnKey => $columnVal) {
                if (in_array($columnKey, $columns) && $columnVal !== '') {
                    $wheres[] = $columnKey . "='" . Req::Clear($columnVal) . "'";
                }
            }
        }

        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $columnKey => $dateRange) {
                if (in_array($columnKey, $columns) && $dateRange !== '') {
                    if (isset($dateRange['from']) && isset($dateRange['to']) && $this->_valid_date($dateRange['from']) && $this->_valid_date($dateRange['to'])) {
                        $wheres[] = $columnKey . " BETWEEN '" . $dateRange['from'] . "' AND '" . $dateRange['to'] . "'";
                    } elseif (isset($dateRange['from']) && $this->_valid_date($dateRange['from'])) {
                        $wheres[] = $columnKey . " > '" . $dateRange['from'] . "'";
                    } elseif (isset($dateRange['to']) && $this->_valid_date($dateRange['to'])) {
                        $wheres[] = $columnKey . " < '" . $dateRange['to'] . "'";
                    }
                }
            }
        }

        if (Role::hasPermission('mod_event', 'moderate_edit') !== TRUE && Role::hasPermission('mod_event', 'moderate_delete') !== TRUE) {
            $wheres[] = "user_id='" . $this->session->get('id') . "'";
        }

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $itemModel->addWhere($wheres);
        }

        if (Req::is_Post('length') && intval(Req::Post_Safe('length')) > 0) {
            $limit = intval(Req::Post_Safe('length'));
        }
        if (Req::is_Post('start') && intval(Req::Post_Safe('start')) >= 0) {
            $offset = intval(Req::Post_Safe('start'));
        }

        $getCount = $itemModel->getCount();
        $itemModel->limit($limit, $offset);
        $itemModel->gR();

        $data = [];
        $records = $itemModel->toArray();

        $statusList = [
            'y' => ['success', $this->lang['mod_event']['admin']['radio']['publish_yes']],
            'n' => ['danger', $this->lang['mod_event']['admin']['radio']['publish_no']],
        ];

        // Fetch user mapping
        $userIds = [];
        if (is_array($records)) {
            foreach ($records as $item) {
                if (isset($item['user_id']) && $item['user_id'] > 0) {
                    $userIds[] = (int) $item['user_id'];
                }
            }
        }

        $userMap = [];
        if ($userIds !== []) {
            $userIds = array_unique($userIds);
            $userModel = Load::model('mod_user.user');
            $userModel->select('id, name, last_name');
            $userModel->addWhere("id IN (" . implode(',', $userIds) . ")");
            $userModel->gR();
            if ($userModel->getSize() > 0) {
                foreach ($userModel->toArray() as $u) {
                    $userMap[$u['id']] = $u['name'] . ' ' . $u['last_name'];
                }
            }
        }

        if ($getCount > 0 && is_array($records)) {
            foreach ($records as $content) {
                $actionButtons = "";
                if (Role::hasPermission('mod_event', 'edit')) {
                    $actionButtons .= '<button data-action="edit" data-id="' . $content['id'] . '" class="btn default btn-xs purple hidden-xs">
    <i class="fa fa-edit"></i> <span class="hidden-xs">' . $this->lang['mod_event']['button']['edit'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_event', 'delete')) {
                    $actionButtons .= '<button data-action="delete" data-id="' . $content['id'] . '" class="btn default btn-xs red hidden-xs">
    <i class="fa fa-trash-o"></i> <span class="hidden-xs">' . $this->lang['mod_event']['button']['delete'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_event', 'gallery')) {
                    $actionButtons .= '<button data-action="gallery" data-id="' . $content['id'] . '" class="btn default btn-xs blue hidden-xs">
    <i class="fa fa-file-image-o"></i> <span class="hidden-xs">' . $this->lang['mod_event']['button']['gallery'] . '</span> </button>';
                }
                if (Role::hasPermission('mod_event', 'file')) {
                    $actionButtons .= '<button data-action="file" data-id="' . $content['id'] . '" class="btn default btn-xs green hidden-xs">
    <i class="fa fa-file-zip-o"></i> <span class="hidden-xs">' . $this->lang['mod_event']['button']['file'] . '</span> </button>';
                }
                if ($content['status'] === 'y') {
                    $actionButtons .= '<button class="btn btn-xs btn-' . $statusList[$content['status']]['0'] . ' hidden-xs" data-action="unpublish" data-id="' . $content['id'] . '" title="' . $statusList[$content['status']]['1'] . '"><i class="fa fa-check"></i></button>';
                } else {
                    $actionButtons .= '<button class="btn btn-xs btn-' . $statusList[$content['status']]['0'] . ' hidden-xs" data-action="publish" data-id="' . $content['id'] . '" title="' . $statusList[$content['status']]['1'] . '"><i class="fa fa-times"></i></button>';
                }

                $authorName = isset($userMap[$content['user_id']]) ? $userMap[$content['user_id']] : 'Admin';

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div class="table-img-trigger" data-action="upload-image" data-id="' . $content['id'] . '" title="' . $this->lang['mod_event']['upload_image'] . '">' .
                    ($content['image'] ? '<img src="' . $this->site['iaddress'] . 'mod_event/thumb/' . $content['image'] . '" width="50" height="66" class="table-thumb-cover">' : '<button class="btn btn-xs btn-default"><i class="fa fa-plus"></i> <span class="hidden-xs">' . $this->lang['mod_event']['add_image'] . '</span></button>') .
                    '</div>',
                    $content['lang'],
                    $content['start_date'],
                    $actionButtons,
                    $content['category_id'],
                    $content['status'],
                    $authorName,
                    isset($content['slider']) && $content['slider'] === 'y' ? 1 : 0,
                    isset($content['deck']) && $content['deck'] === 'y' ? 1 : 0,
                    $content['end_date'] ?? '',
                    $content['location'] ?? ''
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        $modelClass = Load::modelfile('mod_event');
        $totalModel = new $modelClass();
        if (
            Role::hasPermission('mod_event', 'moderate_edit') !== TRUE
            &&
            Role::hasPermission('mod_event', 'moderate_delete') !== TRUE
        ) {
            $totalModel->where(['user_id' => $this->session->get('id')]);
        }
        $totalCount = $totalModel->getCount();

        $draftModel = new $modelClass();
        $draftWheres = ['status' => 'n'];
        if (
            Role::hasPermission('mod_event', 'moderate_edit') !== TRUE
            &&
            Role::hasPermission('mod_event', 'moderate_delete') !== TRUE
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
            'totalEvents' => $totalCount,
            'publishedEvents' => $publishedCount,
            'draftEvents' => $draftCount,
            'total_event' => $totalCount,
            'published_event' => $publishedCount,
            'draft_event' => $draftCount,
        ]);
    }

    public function all_video()
    {
        if (Role::hasPermission('mod_event', 'add') === false && Role::hasPermission('mod_event', 'edit') === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }

        $results = [];
        $queryParam = Req::is_Post('q') ? Req::Post_Safe('q') : null;

        if (BridgeCleanupService::tableExists('mod_video')) {
            $videoModel = Load::model('mod_video');
            $videoModel->select('id, name');
            if ($queryParam) {
                $videoModel->where_like_name('%' . $queryParam . '%');
            }
            $videoModel->order('created_at', 'DESC')->gR();

            if ($videoModel->getSize() > 0) {
                foreach ($videoModel->toArray() as $item) {
                    $results[] = [
                        'id'   => 'video:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_event']['local'] . ')'
                    ];
                }
            }
        }

        if (BridgeCleanupService::tableExists('mod_video_embed')) {
            $embedModel = Load::model('mod_video_embed');
            $embedModel->select('id, name');
            if ($queryParam) {
                $embedModel->where_like_name('%' . $queryParam . '%');
            }
            $embedModel->order('created_at', 'DESC')->gR();

            if ($embedModel->getSize() > 0) {
                foreach ($embedModel->toArray() as $item) {
                    $results[] = [
                        'id'   => 'vid:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_event']['embed'] . ')'
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
        if (Role::hasPermission('mod_event', 'add') === false && Role::hasPermission('mod_event', 'edit') === false) {
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

        $videoIds = [];
        $embedIds = [];

        foreach ($data as $prefixedId) {
            $parts = explode(':', $prefixedId, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 'video') {
                    $videoIds[] = intval($parts[1]);
                } elseif ($parts[0] === 'vid') {
                    $embedIds[] = intval($parts[1]);
                }
            }
        }

        $results = [];

        if ($videoIds !== [] && BridgeCleanupService::tableExists('mod_video')) {
            $videoModel = Load::model('mod_video');
            $videoModel->select('id, name')->where_id($videoIds)->gR();
            if ($videoModel->getSize() > 0) {
                foreach ($videoModel->toArray() as $item) {
                    $results[] = [
                        'id'   => 'video:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_event']['local'] . ')'
                    ];
                }
            }
        }

        if ($embedIds !== [] && BridgeCleanupService::tableExists('mod_video_embed')) {
            $embedModel = Load::model('mod_video_embed');
            $embedModel->select('id, name')->where_id($embedIds)->gR();
            if ($embedModel->getSize() > 0) {
                foreach ($embedModel->toArray() as $item) {
                    $results[] = [
                        'id'   => 'vid:' . $item['id'],
                        'name' => $item['name'] . ' (' . $this->lang['mod_event']['embed'] . ')'
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
        if (Role::hasPermission('mod_event', 'add') === false) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_event']['role_errors']['add']]);
            return;
        }

        $title = trim(Req::Post('title'));
        if ($title === '' || $title === '0') {
            $this->jsonResponse(['status' => 'error', 'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['name'])]);
            return;
        }

        $this->mod_event_model = Load::model('mod_event');
        $this->mod_event_model->where_name($title)->gRo();

        if ($this->mod_event_model->getSize() > 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_event']['validator']['title_exists']]);
        } else {
            $this->jsonResponse(['status' => 'success', 'message' => $this->lang['mod_event']['validator']['title_unique']]);
        }
    }

    private function toggleFlag(string $column, ?string $forceValue = null, ?string $actionRole = null)
    {
        $hasEdit = Role::hasPermission('mod_event', 'edit');
        $hasActionRole = $actionRole !== null ? Role::hasPermission('mod_event', $actionRole) : false;

        if (!$hasEdit && !$hasActionRole) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_event']['role_errors']['edit'],
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
                'message' => $this->lang['mod_event']['errors']['record'],
            ]);
            return;
        }

        $eventModel = Load::model('mod_event');
        $eventModel->where_id($id)->gRo();
        if ($eventModel->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_event']['errors']['record'],
            ]);
            return;
        }

        $eventModel->sync();
        $record = $eventModel->toArray();
        if ($this->user->checkPermission('mod_event', 'moderate_edit', $record['user_id'], $this->lang['mod_event']['role_errors']['moderate_edit'], 1) === false) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_event']['role_errors']['moderate_edit'],
            ]);
            return;
        }

        $currentVal = isset($record[$column]) ? (string) $record[$column] : 'n';
        $newVal = $forceValue !== null ? $forceValue : ($currentVal === 'y' ? 'n' : 'y');

        $updateModel = Load::model('mod_event');
        $updateModel->id = $id;
        $updateModel->$column = $newVal;
        $updateModel->updated_at = date('Y-m-d H:i:s');

        if ($updateModel->update('id')) {
            $response = [
                'status' => 'success',
            ];
            if ($column === 'slider') {
                $response['slider'] = $newVal;
                $response['in_hero_slider'] = ($newVal === 'y');
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_event']['hero_slider_added']
                    : $this->lang['mod_event']['hero_slider_removed'];
            } elseif ($column === 'deck') {
                $response['deck'] = $newVal;
                $response['in_deck'] = ($newVal === 'y');
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_event']['deck_added']
                    : $this->lang['mod_event']['deck_removed'];
            } elseif ($column === 'status') {
                $response['new_status'] = $newVal;
                $response['is_published'] = ($newVal === 'y');
                $response['message'] = $newVal === 'y'
                    ? $this->lang['mod_event']['context_menu']['published_msg']
                    : $this->lang['mod_event']['context_menu']['unpublished_msg'];
            } else {
                $response[$column] = $newVal;
            }
            $this->jsonResponse($response);
            return;
        }

        $errorMessage = $this->lang['mod_event']['errors']['record'];
        if ($column === 'slider') {
            $errorMessage = $this->lang['mod_event']['hero_slider_error'];
        } elseif ($column === 'deck') {
            $errorMessage = $this->lang['mod_event']['deck_error'];
        } elseif ($column === 'status') {
            $errorMessage = $this->lang['mod_event']['message']['error']['status']
                ?? ($this->lang['mod_event']['error_occurred'] ?? 'Failed to update status');
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

    public function list_upload_image()
    {
        $this->ajax_image_upload();
    }
}
