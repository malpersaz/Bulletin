<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_video');
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
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['lang'])
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
        // duration Required
        $this->valid->ekle(
            [
                'name' => 'duration',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['duration'])
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
        // duration Length
        $this->valid->ekle(
            [
                'name' => 'duration',
                'fonk' => 'length',
                'arg' => [
                    'min' => '5',
                    'max' => '8'
                ],
                'mesaj' => sprintf($this->lang['mod_video']['validator']['length'], $this->lang['mod_video']['admin']['columns']['duration'], '5', '8')
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

        #Check Unique with column düzenle

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
