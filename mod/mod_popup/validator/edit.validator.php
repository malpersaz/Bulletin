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
        $this->lang = Load::lang('mod_popup');
    }
    public function check($id)
    {
        #Check Required
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['language'])
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
                'message' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['name'])
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
                'message' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['created_at'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // expired_at Required
        $this->valid->add(
            [
                'name' => 'etarih',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_popup']['validator']['required'], $this->lang['mod_popup']['admin']['columns']['etarih'])
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
                'message' => sprintf($this->lang['mod_popup']['validator']['length'], $this->lang['mod_popup']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // video in Array
        $this->valid->add(
            [
                'name' => 'video',
                'func' => 'array',
                'arg' => ['url', 'youtube', 'vimeo', 'instagram'],
                'message' => sprintf($this->lang['mod_popup']['validator']['inarray'], $this->lang['mod_popup']['admin']['columns']['video'], 'url,youtube,vimeo,instagram')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        # Check identification

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
