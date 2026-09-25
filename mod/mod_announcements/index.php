<?php
/* AKM Software.
 * @2015
 * mod_announcements Module Page
 * mod/mod_announcements/index.php
 */
class mod_announcements_mod extends BaseModuleController
{
    public $mod_announcements_model;

    // Configuration for data loading
    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $has_gallery = true;
    protected $has_files = true;

    // Image fields configuration (3:4 portrait ratio matching latest_announcements.twig sidebar card)
    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 600,
                    'height' => 780,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 300,
                    'height' => 390,
                    'option' => 'width',
                ],
                '/original' => [
                    'width' => NULL,
                    'height' => NULL,
                    'option' => 'original',
                ],
            ]
        ]
    ];

    public function __construct()
    {
        parent::__construct('mod_announcements');
    }

    // Special fields configuration
    protected $special_fields = [
        'content' => ['type' => 'html']
    ];

    protected function getDatatableColumns()
    {
        // Using the new formatter API - similar to menu URL patterns
        return [
            'id',
            'name|bold',          // Title in bold
            'image|image',      // Image with thumbnail
            'lang|badge',       // Language with colored badges
            'created_at|datetime',       // Date with formatting
            'status|badge'       // Status with badge formatter
        ];
    }

    protected function beforeSave($model)
    {
        $model->created_at = $this->normalizeDateTime(Req::Post_Safe('created_at'));
        $model->user_id = $this->session->get('id');

        return $model;
    }

    protected function beforeUpdate($model, $id)
    {
        if (isset($model->created_at)) {
            $model->created_at = $this->normalizeDateTime($model->created_at);
        }

        return $model;
    }

    // All CRUD operations (add, edit, list, delete, image_remove_image) are now handled by BaseModuleController
    // ACL authorization checks (hasPermission) are inherited from BaseModuleController
    // All bulk operations (publish, unpublish, activate, deactivate, enableDeck, disableDeck) are inherited
    // All image handling is handled automatically via image_fields configuration
    // All datatable_json is handled via DataTableService with getDatatableColumns() configuration
    // All form data loading is handled via need_simple_data configuration
}
