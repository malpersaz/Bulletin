<?php
/* AKM Software.
 * @2015
 * mod_document Module Page - Modernized version extending BaseModuleController
 * mod/mod_document/index.php
 */
class mod_document_mod extends BaseModuleController
{
    public $mod_document_model;

    protected $has_gallery = true;
    protected $has_files = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $need_nested_data = [
        'mod_menu' => 'select_nested_menu_result'
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 1200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 300,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ]
        ]
    ];

    protected $special_fields = [
        'description' => ['type' => 'safe']
    ];

    protected $menu_url_pattern = '{lang}/document/{seo}';

    public function __construct()
    {
        parent::__construct('mod_document');
    }

    protected function getFormData()
    {
        return parent::getFormData();
    }

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'image|image',
            'lang|badge',
            'status|badge'
        ];
    }
}
