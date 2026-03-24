<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $mod_content_model;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_content');
    }
    public function check($id)
    {
        #Check Required
        // lang Required
        $this->valid->ekle(
            [
                'name' => 'lang',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['language'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // category_id Required
        $this->valid->ekle(
            [
                'name' => 'category_id',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['category_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // created_at Required
        $this->valid->ekle(
            [
                'name' => 'created_at',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['created_at'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['content'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['seo'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['title'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['keywords'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['required'], $this->lang['mod_content']['admin']['columns']['description'])
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['length'], $this->lang['mod_content']['admin']['columns']['name'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['length'], $this->lang['mod_content']['admin']['columns']['seo'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['length'], $this->lang['mod_content']['admin']['columns']['title'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['length'], $this->lang['mod_content']['admin']['columns']['keywords'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['length'], $this->lang['mod_content']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // status in Array
        $this->valid->ekle(
            [
                'name' => 'status',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_content']['validator']['inarray'], $this->lang['mod_content']['admin']['columns']['status'], 'y,n')
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
                'mesaj' => sprintf($this->lang['mod_content']['validator']['inarray'], $this->lang['mod_content']['admin']['columns']['deck'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column düzenle
        $this->mod_content_model = Load::model('mod_content');
        $this->mod_content_model->where_seo_and_lang_and_not_id(html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8'), Req::Post_Safe('lang'), $id);
        if ($this->mod_content_model->limit(1)->gRo()->getSize() > 0) {
            $this->hatalar[] = sprintf($this->lang['mod_content']['validator']['unique'], $this->lang['mod_content']['admin']['columns']['seo'], $this->lang['mod_content']['admin']['columns']['seo']);
        }
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
