<?php
class add_validator
{
    public $valid;
    public $lang;
    public $mod_page_model;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_page');
    }

    private function addInvalidValueError($label)
    {
        $this->hatalar[] = sprintf($this->lang['mod_page']['validator']['inarray'], $label, 'numeric values');
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
            $this->hatalar[] = $label . ': ' . $this->lang['mod_page']['errors']['record'];
        }
    }

    public function check()
    {
        #Check Required
        // lang Required
        $this->valid->ekle(
            [
                'name' => 'lang',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // name Required
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // parent_id Required
        $this->valid->ekle(
            [
                'name' => 'parent_id',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['parent_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // content Required
        $this->valid->ekle(
            [
                'name' => 'content',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['content'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Required
        $this->valid->ekle(
            [
                'name' => 'seo',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['seo'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Required
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['keywords'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Required
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_page']['validator']['required'], $this->lang['mod_page']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // name Length
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['name'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Length
        $this->valid->ekle(
            [
                'name' => 'seo',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['seo'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Length
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['keywords'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Length
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['length'], $this->lang['mod_page']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // root in Array
        $this->valid->ekle(
            [
                'name' => 'root',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['root'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // status in Array
        $this->valid->ekle(
            [
                'name' => 'status',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // deck in Array
        $this->valid->ekle(
            [
                'name' => 'deck',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_page']['validator']['inarray'], $this->lang['mod_page']['admin']['columns']['deck'], 'y,n')
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
            $this->hatalar[] = sprintf($this->lang['mod_page']['validator']['unique'], $this->lang['mod_page']['admin']['columns']['seo'], $this->lang['mod_page']['admin']['columns']['seo']);
        }
        if ($this->_check() === True) {
            return True;
        }

        $lang = Req::Post_Safe('lang');
        $parentId = $this->validateNumericField('parent_id', $this->lang['mod_page']['admin']['columns']['parent_id']);
        $categoryId = $this->validateNumericField('category_id', $this->lang['mod_page']['admin']['columns']['category_id']);
        $menuId = $this->validateNumericField('menu', $this->lang['mod_page']['admin']['columns']['menu']);
        $aclId = $this->validateNumericField('acl_id', $this->lang['mod_page']['admin']['columns']['acl_id']);
        $galleryId = $this->validateNumericField('gallery_id', $this->lang['mod_page']['admin']['columns']['gallery_id']);
        $fileId = $this->validateNumericField('file_id', $this->lang['mod_page']['admin']['columns']['file_id']);

        if ($this->_check() === True) {
            return True;
        }

        $this->validateReferenceExists('mod_page', $parentId, $this->lang['mod_page']['admin']['columns']['parent_id'], $lang);
        $this->validateReferenceExists('mod_category', $categoryId, $this->lang['mod_page']['admin']['columns']['category_id'], $lang);
        $this->validateReferenceExists('mod_menu', $menuId, $this->lang['mod_page']['admin']['columns']['menu'], $lang);
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
            $this->hatalar = $this->valid->list_errors();
        }

        if (count($this->hatalar) > 0) {
            return True;
        }
        return False;
    }
}
