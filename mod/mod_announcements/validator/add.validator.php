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
        // Language File
        $this->lang = Load::lang('mod_announcements');
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['required'], $this->lang['mod_announcements']['admin']['columns']['language'])
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['required'], $this->lang['mod_announcements']['admin']['columns']['name'])
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['required'], $this->lang['mod_announcements']['admin']['columns']['created_at'])
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['required'], $this->lang['mod_announcements']['admin']['columns']['content'])
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
                    'max' => '70'
                ],
                'message' => $this->lang['mod_announcements']['validator']['title_length']
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['required'], $this->lang['mod_announcements']['admin']['columns']['summary'])
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
                'message' => $this->lang['mod_announcements']['validator']['deck_length']
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['inarray'], $this->lang['mod_announcements']['admin']['columns']['status'], 'y,n')
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
                'message' => sprintf($this->lang['mod_announcements']['validator']['inarray'], $this->lang['mod_announcements']['admin']['columns']['deck'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // Additional checks if needed
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
