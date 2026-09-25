<?php

class add_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_redirect');
    }

    public function check()
    {
        $this->valid->add([
            'name' => 'old_url',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_redirect']['validator']['required'], $this->lang['mod_redirect']['admin']['columns']['old_url']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'new_url',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_redirect']['validator']['required'], $this->lang['mod_redirect']['admin']['columns']['new_url']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'old_url',
            'func' => 'length',
            'arg' => ['min' => '2', 'max' => '250'],
            'message' => sprintf($this->lang['mod_redirect']['validator']['length'], $this->lang['mod_redirect']['admin']['columns']['old_url'], '2', '250'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'new_url',
            'func' => 'length',
            'arg' => ['min' => '1', 'max' => '250'],
            'message' => sprintf($this->lang['mod_redirect']['validator']['length'], $this->lang['mod_redirect']['admin']['columns']['new_url'], '1', '250'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'status_code',
            'func' => 'inarray',
            'arg' => '301,302',
            'message' => sprintf($this->lang['mod_redirect']['validator']['inarray'], $this->lang['mod_redirect']['admin']['columns']['status_code'], '301,302'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        // Check if old_url is unique in database
        $old_url = trim((string) Req::Post_Safe('old_url'));
        $db = Load::model('mod_redirect');
        $exists = $db->where('old_url', $old_url)->single();
        if ($exists) {
            $this->errors[] = $this->lang['mod_redirect']['validator']['unique_old_url'];
            return true;
        }

        return false;
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->errors = $this->valid->list_errors();
        }

        return count($this->errors) > 0;
    }
}
