<?php

class edit_validator
{
    public $valid;
    public $lang;
    public $mod_socialmedia_post_mod_model;
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_socialmedia_post_mod');
    }

    public function check($id = null)
    {
        $requiredFields = ['name', 'mod_name', 'mod_key', 'mod_value'];
        foreach ($requiredFields as $field) {
            $columnLabel = $this->lang['mod_socialmedia_post_mod']['admin']['columns'][$field] ?? ucfirst(str_replace('_', ' ', $field));
            $message = isset($this->lang['mod_socialmedia_post_mod']['validator']['required'])
                ? sprintf($this->lang['mod_socialmedia_post_mod']['validator']['required'], $columnLabel)
                : sprintf('Please do not leave the %s field empty', $columnLabel);

            $this->valid->add([
                'name' => $field,
                'func' => 'required',
                'arg' => '',
                'message' => $message,
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        $lengthFields = [
            'name' => ['min' => 2, 'max' => 255],
            'mod_name' => ['min' => 2, 'max' => 255],
            'mod_key' => ['min' => 1, 'max' => 255],
            'mod_value' => ['min' => 1, 'max' => 255],
        ];
        foreach ($lengthFields as $field => $args) {
            $columnLabel = $this->lang['mod_socialmedia_post_mod']['admin']['columns'][$field] ?? ucfirst(str_replace('_', ' ', $field));
            $message = isset($this->lang['mod_socialmedia_post_mod']['validator']['length'])
                ? sprintf($this->lang['mod_socialmedia_post_mod']['validator']['length'], $columnLabel, $args['min'], $args['max'])
                : sprintf('%s must be between %s and %s characters', $columnLabel, $args['min'], $args['max']);

            $this->valid->add([
                'name' => $field,
                'func' => 'length',
                'arg' => [
                    'min' => (string) $args['min'],
                    'max' => (string) $args['max'],
                ],
                'message' => $message,
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        $modName = trim((string) Req::Post_Safe('mod_name'));
        if ($modName !== '') {
            $this->mod_socialmedia_post_mod_model = Load::model('mod_socialmedia_post_mod');
            $this->mod_socialmedia_post_mod_model->where_mod_name($modName);
            if ($id !== null && intval($id) > 0) {
                $this->mod_socialmedia_post_mod_model->addWhere("id != '" . intval($id) . "'");
            }
            if ($this->mod_socialmedia_post_mod_model->limit(1)->gRo()->getSize() > 0) {
                $columnLabel = $this->lang['mod_socialmedia_post_mod']['admin']['columns']['mod_name'] ?? 'Module Name';
                $message = isset($this->lang['mod_socialmedia_post_mod']['validator']['unique'])
                    ? sprintf($this->lang['mod_socialmedia_post_mod']['validator']['unique'], $columnLabel, $columnLabel)
                    : sprintf('The %s you entered is already registered in the system. Please specify a different %s!', $columnLabel, $columnLabel);
                $this->errors[] = $message;
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
}
