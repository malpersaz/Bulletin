<?php
class duzenle_validator
{
    public $valid;
    public $lang;
    public $mod_document_model;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_document');
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['language'])
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['name'])
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['seo'])
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['title'])
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['keywords'])
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['description'])
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
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns']['name'], '2', '255')
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns']['seo'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns']['title'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns']['keywords'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns']['description'], '1', '255')
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
                'mesaj' => sprintf($this->lang['mod_document']['validator']['inarray'], $this->lang['mod_document']['admin']['columns']['published'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column düzenle
        $this->mod_document_model = Load::model('mod_document');
        $this->mod_document_model->where_seo_and_lang_and_not_id(Req::Post_Safe('seo'), Req::Post_Safe('lang'), $id);
        if ($this->mod_document_model->limit(1)->gRo()->getSize() > 0) {
            $this->hatalar[] = sprintf($this->lang['mod_document']['validator']['unique'], $this->lang['mod_document']['admin']['columns']['seo'], $this->lang['mod_document']['admin']['columns']['seo']);
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
