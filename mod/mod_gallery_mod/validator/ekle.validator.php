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
        $this->lang = Load::lang('mod_gallery_mod');
    }
    public function check()
    {
        #Check Required
        // name Required
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_name Required
        $this->valid->ekle(
            [
                'name' => 'mod_name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_key Required
        $this->valid->ekle(
            [
                'name' => 'mod_key',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_key'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_value Required
        $this->valid->ekle(
            [
                'name' => 'mod_value',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_value'])
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
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_name Length
        $this->valid->ekle(
            [
                'name' => 'mod_name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_key Length
        $this->valid->ekle(
            [
                'name' => 'mod_key',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_key'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_value Length
        $this->valid->ekle(
            [
                'name' => 'mod_value',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_value'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray

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
