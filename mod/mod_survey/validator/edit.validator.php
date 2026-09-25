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
        $this->lang = Load::lang('mod_survey');
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
                'message' => sprintf($this->lang['mod_survey']['validation']['required'], $this->lang['mod_survey']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // question Required
        $this->valid->add(
            [
                'name' => 'question',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_survey']['validation']['required'], $this->lang['mod_survey']['admin']['columns']['question'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // question Length
        $this->valid->add(
            [
                'name' => 'question',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_survey']['validation']['min_length'], $this->lang['mod_survey']['admin']['columns']['question'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

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
