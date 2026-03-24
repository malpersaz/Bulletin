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
        $this->lang = Load::lang('mod_video');
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['language'])
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Required
        $this->valid->ekle(
            [
                'name' => 'sure',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['sure'])
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['created_at'])
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['length'], $this->lang['mod_video']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Length
        $this->valid->ekle(
            [
                'name' => 'sure',
                'fonk' => 'length',
                'arg' => [
                    'min' => '5',
                    'max' => '8'
                ],
                'mesaj' => sprintf($this->lang['mod_video']['validator']['length'], $this->lang['mod_video']['admin']['columns']['sure'], '5', '8')
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['inarray'], $this->lang['mod_video']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

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
