<?php
/* AKM Software.
 * @2015
 * mod_announcements Modül Sayfası
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

    // Image fields configuration  
    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 100,
                    'height' => NULL,
                    'option' => 'width',
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

    // All CRUD operations (add, edit, list, delete, image_remove_image) are now handled by BaseModuleController
    // All bulk operations (publish, unpublish, activate, deactivate, enableDeck, disableDeck) are inherited
    // All image handling is handled automatically via image_fields configuration
    // All datatable_json is handled via DataTableService with getDatatableColumns() configuration
    // All form data loading is handled via need_simple_data configuration
}
