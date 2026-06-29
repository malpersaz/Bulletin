<?php
class add_validator
{
    public $valid;
    public $lang;
    public $mod_document_model;
    public $errors = [];

    function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_document');
    }

    public function check()
    {
        // Required Fields
        $required_fields = ['lang', 'name', 'seo', 'title', 'keywords', 'description'];
        foreach ($required_fields as $field) {
            $this->valid->ekle([
                'name' => $field,
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns'][$field])
            ]);
            if ($this->_check()) return true;
        }

        // Length Checks
        $length_fields = [
            'name' => ['min' => 2, 'max' => 255],
            'seo' => ['min' => 1, 'max' => 255],
            'title' => ['min' => 1, 'max' => 255],
            'keywords' => ['min' => 1, 'max' => 255],
            'description' => ['min' => 1, 'max' => 500]
        ];
        foreach ($length_fields as $field => $args) {
            $this->valid->ekle([
                'name' => $field,
                'func' => 'length',
                'arg' => $args,
                'message' => sprintf($this->lang['mod_document']['validator']['length'], $this->lang['mod_document']['admin']['columns'][$field], $args['min'], $args['max'])
            ]);
            if ($this->_check()) return true;
        }

        // Status check
        $this->valid->ekle([
            'name' => 'status',
            'func' => 'array',
            'arg' => ['y', 'n'],
            'message' => sprintf($this->lang['mod_document']['validator']['required'], $this->lang['mod_document']['admin']['columns']['status'])
        ]);
        if ($this->_check()) return true;

        // Unique SEO Check
        $this->mod_document_model = Load::model('mod_document');
        $this->mod_document_model->where_seo_and_lang(Req::Post_Safe('seo'), Req::Post_Safe('lang'));
        if ($this->mod_document_model->limit(1)->gRo()->getSize() > 0) {
            $this->errors[] = sprintf($this->lang['mod_document']['validator']['unique'], $this->lang['mod_document']['admin']['columns']['seo'], $this->lang['mod_document']['admin']['columns']['seo']);
            return true;
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
