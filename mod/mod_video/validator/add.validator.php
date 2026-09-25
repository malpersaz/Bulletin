<?php
class add_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_video');
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
                'message' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['lang'])
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
                'message' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // duration Required
        $this->valid->add(
            [
                'name' => 'duration',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['duration'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // created_at Required
        $this->valid->add(
            [
                'name' => 'created_at',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video']['validator']['required'], $this->lang['mod_video']['admin']['columns']['created_at'])
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
                'message' => sprintf($this->lang['mod_video']['validator']['length'], $this->lang['mod_video']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // duration Length
        $this->valid->add(
            [
                'name' => 'duration',
                'func' => 'length',
                'arg' => [
                    'min' => '5',
                    'max' => '8'
                ],
                'message' => sprintf($this->lang['mod_video']['validator']['length'], $this->lang['mod_video']['admin']['columns']['duration'], '5', '8')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // status in Array
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_video']['validator']['inarray'], $this->lang['mod_video']['admin']['columns']['status'], 'y,n')
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
            $this->errors = $this->valid->list_errors();
        }

        if (count($this->errors) > 0) {
            return True;
        }
        return False;
    }
}
