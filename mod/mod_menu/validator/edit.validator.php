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
        // Language File.
        $this->lang = Load::lang('mod_menu');
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
                'message' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['language'] ?? 'language', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
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
                'message' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['name'] ?? 'name', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // parent_id Required
        $this->valid->add(
            [
                'name' => 'parent_id',
                'func' => 'required',
                'arg' => '',
                'message' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['parent_id'] ?? 'parent_id', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
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
                'message' => str_replace([':field', ':min', ':max'], [$this->lang['mod_menu']['admin']['columns']['name'] ?? 'name', '2', '255'], $this->lang['mod_menu']['validation']['min_length'] ?? '%s Divê herî kêm %s tîpan be')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // target in Array
        $this->valid->add(
            [
                'name' => 'target',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => "Hedef xelet e"
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // status in Array
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => "Weşan xelet e"
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        # Check national ID

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
