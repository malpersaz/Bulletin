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
    protected $has_faq = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_publication_category' => 'select_nested_category_id_result',
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $need_content_refs = [
        'mod_gallery_mod' => ['mod_publication_gallery_id'],
        'mod_attachment' => ['mod_publication_file_id']
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

    protected $menu_url_pattern = '{lang}/publication/{seo}';

    public function __construct()
    {
        parent::__construct('mod_publication');
    }

    protected function getFormData()
    {
        $data = parent::getFormData();

        // Load flat category list for card/wizard selectors
        $catModel = Load::model('mod_publication_category');
        $catModel->select('id, name, lang');
        $catModel->addWhere("root != 'y'");
        $catModel->order('`order`', 'ASC')->all();
        $data['categories'] = $catModel->toArray();
        $data['categories_flat'] = $data['categories'];

        return $data;
    }

    public function datatable_json()
    {
        if ($this->user->hasPermission('mod_publication', 'list', $this->lang['mod_publication']['role_errors']['list'] ?? 'Access denied', 1) === false) {
            return false;
        }

        $i = Load::model('mod_publication');
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

        if (Req::is_Post('filter')) {
            $filter = Req::Post_Safe('filter');
            if (!empty($filter['name'])) {
                $wheres[] = "name LIKE '%" . Req::Clear($filter['name']) . "%'";
            }
            if (!empty($filter['lang'])) {
                $wheres[] = "lang = '" . Req::Clear($filter['lang']) . "'";
            }
            if (!empty($filter['category_id'])) {
                $wheres[] = "category_id = " . intval($filter['category_id']);
            }
        }

        if (Role::hasPermission('mod_publication', 'moderate_edit') !== true && Role::hasPermission('mod_publication', 'moderate_delete') !== true) {
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

        $records = $i->toArray();

        // Calculate statistics
        $totalModel = Load::model('mod_publication');
        if (Role::hasPermission('mod_publication', 'moderate_edit') !== true && Role::hasPermission('mod_publication', 'moderate_delete') !== true) {
            $totalModel->addWhere("user_id='" . $this->session->get('id') . "'");
        }
        $totalPublication = $totalModel->getCount();

        $draftModel = Load::model('mod_publication');
        $draftModel->addWhere("status = 'n'");
        if (Role::hasPermission('mod_publication', 'moderate_edit') !== true && Role::hasPermission('mod_publication', 'moderate_delete') !== true) {
            $draftModel->addWhere("user_id='" . $this->session->get('id') . "'");
        }
        $draftPublication = $draftModel->getCount();

        // Fetch author names
        $user_ids = [];
        if (is_array($records)) {
            foreach ($records as $item) {
                if (isset($item['user_id']) && $item['user_id'] > 0) {
                    $user_ids[] = (int) $item['user_id'];
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
                    $user_map[$u['id']] = trim($u['name'] . ' ' . $u['last_name']);
                }
            }
        }

        $data = [];
        if ($getCount > 0 && is_array($records)) {
            foreach ($records as $content) {
                $author_name = isset($user_map[$content['user_id']]) ? $user_map[$content['user_id']] : '';

                $image_html = '';
                if (!empty($content['image'])) {
                    $image_html = '<img src="' . $this->site['iaddress'] . 'mod_publication/thumb/' . $content['image'] . '" width="50" height="50" class="table-thumb-cover">';
                }

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    $image_html,
                    strtoupper($content['lang'] ?? 'TR'),
                    date('d.m.Y H:i', strtotime($content['created_at'])),
                    '',
                    (int) $content['category_id'],
                    $content['status'],
                    $author_name,
                    isset($content['slider']) && $content['slider'] === 'y' ? 1 : 0,
                    isset($content['deck']) && $content['deck'] === 'y' ? 1 : 0
                ];
            }
        }

        $draw = Req::is_Post('draw') ? intval(Req::Post_Safe('draw')) : 1;
        $output = [
            'draw' => $draw,
            'recordsTotal' => $getCount,
            'recordsFiltered' => $getCount,
            'totalPublication' => $totalPublication,
            'draftPublication' => $draftPublication,
            'data' => $data
        ];

        echo json_encode($output);
        exit;
    }

    public function toggle_hero_slider()
    {
        if ($this->user->hasPermission('mod_publication', 'edit', $this->lang['mod_publication']['role_errors']['edit'] ?? 'Access denied', 1) === False) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_publication']['role_errors']['edit'] ?? 'Access denied']);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id === 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record'] ?? 'Record not found']);
            return;
        }

        $this->mod_publication_model = Load::model('mod_publication');
        $this->mod_publication_model->where_id($id)->gRo();
        if ($this->mod_publication_model->getSize() === 0) {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_publication']['errors']['record'] ?? 'Record not found']);
            return;
        }
        $this->mod_publication_model->sync();

        $new_slider = (($this->mod_publication_model->slider ?? 'n') === 'y') ? 'n' : 'y';

        $update_model = Load::model('mod_publication');
        $update_model->id = $id;
        $update_model->slider = $new_slider;
        $update_model->updated_at = date('Y-m-d H:i:s');

        if ($update_model->update('id')) {
            $this->jsonResponse([
                'status' => 'success',
                'slider' => $new_slider,
                'in_hero_slider' => $new_slider === 'y',
                'message' => $new_slider === 'y'
                    ? ($this->lang['mod_publication']['hero_slider_added'] ?? 'Added to slider')
                    : ($this->lang['mod_publication']['hero_slider_removed'] ?? 'Removed from slider')
            ]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => $this->lang['mod_publication']['hero_slider_error'] ?? 'Slider update failed']);
        }
    }
}
