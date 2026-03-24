<?php
class ekle_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_popup');
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
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['language'])
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
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['name'])
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
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['created_at'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // etarih Required
        $this->valid->ekle(
            [
                'name' => 'etarih',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['etarih'])
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
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['length'], $this->lang['mod_popup']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray

        // video in Array
        $this->valid->ekle(
            [
                'name' => 'video',
                'fonk' => 'array',
                'arg' => ['url', 'youtube', 'vimeo', 'instagram'],
                'mesaj' => sprintf($this->lang['mod_popup']['validator']['inarray'], $this->lang['mod_popup']['admin']['columns']['video'], 'url,youtube,vimeo,instagram')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column

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
