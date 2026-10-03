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

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'image|image',
            'category_id|category',
            'lang|badge',
            'created_at|datetime',
            'status|badge'
        ];
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
