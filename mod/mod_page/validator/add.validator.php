<?php
class add_validator
{
    private const MENU_SELECTION_NONE = '__none__';
    private const MENU_SELECTION_TOP = '__top__';

    public $valid;
    public $lang;
    public $mod_page_model;
    public $errors = [];
    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_page');
    }

    private function addInvalidValueError($label)
    {
        $this->errors[] = sprintf($this->lang['mod_page']['validator']['inarray'], $label, 'numeric values');
    }

    private function validateNumericField($field, $label, $allowZero = true)
    {
        $value = Req::Post_Safe($field);
        if ($value === '') {
            if ($allowZero) {
                return 0;
            }
            $this->addInvalidValueError($label);
            return FALSE;
        }

        if (!Req::isNumeric((string) $value)) {
            $this->addInvalidValueError($label);
            return FALSE;
        }

        return (int) $value;
    }

    private function validateMenuField($field, $label)
    {
        $value = trim((string) Req::Post_Safe($field));
        if ($value === '' || $value === self::MENU_SELECTION_NONE) {
            return [
                'include' => FALSE,
                'parent_id' => 0,
            ];
        }

        if ($value === self::MENU_SELECTION_TOP) {
            return [
                'include' => TRUE,
                'parent_id' => 0,
            ];
        }

        if (!Req::isNumeric((string) $value)) {
            $this->addInvalidValueError($label);
            return FALSE;
        }

        return [
            'include' => TRUE,
            'parent_id' => (int) $value,
        ];
    }

    private function validateReferenceExists($modelName, $id, $label, $lang = '')
    {
        if ($id === 0) {
            return;
        }

        $model = Load::model($modelName);
        $model->where_id($id);
        if ($lang !== '') {
            $model->where_lang($lang);
        }
        $model->limit(1)->gRo();
        if ($model->getSize() === 0) {
            $this->errors[] = $label . ': ' . $this->lang['mod_page']['errors']['record'];
        }
    }

    public function check()
    {
        #Check Required
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // name Required
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // parent_id Required
        $this->valid->add(
            [
                'name' => 'parent_id',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['parent_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // content Required
        $this->valid->add(
            [
                'name' => 'content',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['content'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // summary Required
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['summary'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Required
        $this->valid->add(
            [
                'name' => 'seo',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['seo'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Required
        $this->valid->add(
            [
                'name' => 'keywords',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['keywords'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Required
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // name Length
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['name'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Length
        $this->valid->add(
            [
                'name' => 'seo',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['seo'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Length
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length
        $this->valid->add(
            [
                'name' => 'keywords',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['keywords'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Length
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // summary Length
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'length',
                'arg' => [
                    'min' => '90',
                    'max' => '120'
                ],
                'message' => $this->lang['mod_page']['validator']['deck_length']
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // root in Array
        $this->valid->add(
            [
                'name' => 'root',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['root'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // status in Array
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // deck in Array
        $this->valid->add(
            [
                'name' => 'deck',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['deck'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column
        $this->mod_page_model = Load::model('mod_page');
        $this->mod_page_model->where_seo_and_lang(Req::Post_Safe('seo'), Req::Post_Safe('lang'));
        if ($this->mod_page_model->limit(1)->gRo()->getSize() > 0) {
            $this->errors[] = sprintf($this->lang['mod_page']['validator']['unique'], $this->lang['mod_page']['admin']['columns']['seo'], $this->lang['mod_page']['admin']['columns']['seo']);
        }
        if ($this->_check() === True) {
            return True;
        }

        $lang = Req::Post_Safe('lang');
        $parentId = $this->validateNumericField('parent_id', $this->lang['mod_page']['admin']['columns']['parent_id']);
        $categoryId = $this->validateNumericField('category_id', $this->lang['mod_page']['admin']['columns']['category_id']);
        $menuSelection = $this->validateMenuField('menu', $this->lang['mod_page']['admin']['columns']['menu']);
        $aclId = $this->validateNumericField('acl_id', $this->lang['mod_page']['admin']['columns']['acl_id']);
        $galleryId = $this->validateNumericField('gallery_id', $this->lang['mod_page']['admin']['columns']['gallery_id']);
        $fileId = $this->validateNumericField('file_id', $this->lang['mod_page']['admin']['columns']['file_id']);

        if ($this->_check() === True) {
            return True;
        }

        $this->validateReferenceExists('mod_page', $parentId, $this->lang['mod_page']['admin']['columns']['parent_id'], $lang);
        if ($menuSelection !== FALSE && $menuSelection['parent_id'] > 0) {
            $this->validateReferenceExists('mod_menu', $menuSelection['parent_id'], $this->lang['mod_page']['admin']['columns']['menu'], $lang);
        }
        $this->validateReferenceExists('mod_acl', $aclId, $this->lang['mod_page']['admin']['columns']['acl_id']);
        $this->validateReferenceExists('mod_page', $galleryId, $this->lang['mod_page']['admin']['columns']['gallery_id'], $lang);
        $this->validateReferenceExists('mod_page', $fileId, $this->lang['mod_page']['admin']['columns']['file_id'], $lang);

        if ($this->_check() === True) {
            return True;
        }

        return False;
    }

    public function _check()
    {

        if ($this->valid->check()) {
            $this->errors = $this->valid->list_errors();
        }

        if (count($this->errors) > 0) {
            return True;
        }
        return False;
    }
}
