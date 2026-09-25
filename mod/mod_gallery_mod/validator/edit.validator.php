<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $errors = [];
    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File
        $this->lang = Load::lang('mod_gallery_mod');
    }
    public function check($id)
    {
        #Check Required
        // name Required
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_name Required
        $this->valid->add(
            [
                'name' => 'mod_name',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_key Required
        $this->valid->add(
            [
                'name' => 'mod_key',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_key'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_value Required
        $this->valid->add(
            [
                'name' => 'mod_value',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['required'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_value'])
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
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_name Length
        $this->valid->add(
            [
                'name' => 'mod_name',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_key Length
        $this->valid->add(
            [
                'name' => 'mod_key',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_key'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // mod_value Length
        $this->valid->add(
            [
                'name' => 'mod_value',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_gallery_mod']['validator']['length'], $this->lang['mod_gallery_mod']['admin']['columns']['mod_value'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with edit column

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
