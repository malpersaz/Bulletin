<?php

class add_validator
{
    public $valid;
    public $lang;
    public $mod_faq_mod_model;
    public $errors = [];

    public function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File
        $this->lang = Load::lang('mod_faq_mod');
    }

    public function check()
    {
        // Required Checks
        $required_fields = ['name', 'mod_name', 'mod_key', 'mod_value'];
        foreach ($required_fields as $field) {
            $column_label = isset($this->lang['mod_faq_mod']['admin']['columns'][$field]) ? $this->lang['mod_faq_mod']['admin']['columns'][$field] : ucfirst(str_replace('_', ' ', $field));
            $msg = isset($this->lang['mod_faq_mod']['validator']['required'])
                ? sprintf($this->lang['mod_faq_mod']['validator']['required'], $column_label)
                : sprintf('Please do not leave the %s field empty', $column_label);

            $this->valid->add([
                'name' => $field,
                'func' => 'required',
                'arg' => '',
                'message' => $msg
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        // Length Checks
        $length_fields = [
            'name' => ['min' => 2, 'max' => 255],
            'mod_name' => ['min' => 2, 'max' => 255],
            'mod_key' => ['min' => 1, 'max' => 255],
            'mod_value' => ['min' => 1, 'max' => 255]
        ];
        foreach ($length_fields as $field => $args) {
            $column_label = isset($this->lang['mod_faq_mod']['admin']['columns'][$field]) ? $this->lang['mod_faq_mod']['admin']['columns'][$field] : ucfirst(str_replace('_', ' ', $field));
            $msg = isset($this->lang['mod_faq_mod']['validator']['length'])
                ? sprintf($this->lang['mod_faq_mod']['validator']['length'], $column_label, $args['min'], $args['max'])
                : sprintf('%s must be between %s and %s characters', $column_label, $args['min'], $args['max']);

            $this->valid->add([
                'name' => $field,
                'func' => 'length',
                'arg' => [
                    'min' => (string)$args['min'],
                    'max' => (string)$args['max']
                ],
                'message' => $msg
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        // Unique mod_name Check
        $mod_name = trim((string)Req::Post_Safe('mod_name'));
        if ($mod_name !== '') {
            $this->mod_faq_mod_model = Load::model('mod_faq_mod');
            $this->mod_faq_mod_model->where_mod_name($mod_name);
            if ($this->mod_faq_mod_model->limit(1)->gRo()->getSize() > 0) {
                $column_label = isset($this->lang['mod_faq_mod']['admin']['columns']['mod_name']) ? $this->lang['mod_faq_mod']['admin']['columns']['mod_name'] : 'Module Name';
                $msg = isset($this->lang['mod_faq_mod']['validator']['unique'])
                    ? sprintf($this->lang['mod_faq_mod']['validator']['unique'], $column_label, $column_label)
                    : sprintf('The %s you entered is already registered in the system. Please specify a different %s!', $column_label, $column_label);
                $this->errors[] = $msg;
                return true;
            }
        }

        return false;
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->errors = array_merge($this->errors, $this->valid->list_errors());
        }

        return count($this->errors) > 0;
    }
    public function __get($name)
    {
        if ($name === "hatalar") {
            return $this->errors;
        }
        return $this->$name ?? null;
    }

    public function __set($name, $value)
    {
        if ($name === "hatalar") {
            $this->errors = $value;
        } else {
            $this->$name = $value;
        }
    }
}
