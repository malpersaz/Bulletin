<?php
class add_validator
{
    public $valid;
    public $lang;
    public $mod_imagecategories_model;
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_imagecategories');
    }

    public function check()
    {
        foreach (['lang', 'name', 'parent_id', 'seo', 'title', 'keywords', 'description'] as $field) {
            $this->valid->ekle([
                'name' => $field,
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_imagecategories']['validator']['required'], $this->lang['mod_imagecategories']['admin']['columns'][$field]),
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        $lengthRules = [
            'name' => ['2', '255'],
            'seo' => ['1', '255'],
            'title' => ['1', '255'],
            'keywords' => ['1', '255'],
            'description' => ['1', '255'],
        ];

        foreach ($lengthRules as $field => $range) {
            $this->valid->ekle([
                'name' => $field,
                'fonk' => 'length',
                'arg' => ['min' => $range[0], 'max' => $range[1]],
                'mesaj' => sprintf($this->lang['mod_imagecategories']['validator']['length'], $this->lang['mod_imagecategories']['admin']['columns'][$field], $range[0], $range[1]),
            ]);
            if ($this->_check() === true) {
                return true;
            }
        }

        $this->valid->ekle([
            'name' => 'root',
            'fonk' => 'array',
            'arg' => ['y', 'n'],
            'mesaj' => sprintf($this->lang['mod_imagecategories']['validator']['inarray'], $this->lang['mod_imagecategories']['admin']['columns']['root'], 'y,n'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->mod_imagecategories_model->where_seo_and_lang(html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8'), Req::Post_Safe('lang'));
        if ($this->mod_imagecategories_model->limit(1)->gRo()->getSize() > 0) {
            $this->errors[] = sprintf(
                $this->lang['mod_imagecategories']['validator']['unique'],
                $this->lang['mod_imagecategories']['admin']['columns']['seo'],
                $this->lang['mod_imagecategories']['admin']['columns']['seo']
            );
        }

        return $this->_check();
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->errors = $this->valid->list_errors();
        }

        return count($this->errors) > 0;
    }
}
