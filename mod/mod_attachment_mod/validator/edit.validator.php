<?php

class edit_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_attachment_mod');
    }

    public function check($id)
    {
        $this->valid->add([
            'name' => 'name',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['name']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_name',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_name']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_key',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_key']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_value',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'name',
            'func' => 'length',
            'arg' => ['min' => '2', 'max' => '255'],
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['name'], '2', '255'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_name',
            'func' => 'length',
            'arg' => ['min' => '2', 'max' => '255'],
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_name'], '2', '255'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_key',
            'func' => 'length',
            'arg' => ['min' => '2', 'max' => '255'],
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_key'], '2', '255'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'mod_value',
            'func' => 'length',
            'arg' => ['min' => '2', 'max' => '255'],
            'message' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value'], '2', '255'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        return $this->_check_display_field();
    }

    private function _check_display_field()
    {
        $mod_value = trim((string) Req::Post_Safe('mod_value'));
        if ($mod_value !== '' && ($mod_value === 'id' || substr($mod_value, -3) === '_id')) {
            $this->errors[] = sprintf($this->lang['mod_attachment_mod']['validator']['display_field'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value']);
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
