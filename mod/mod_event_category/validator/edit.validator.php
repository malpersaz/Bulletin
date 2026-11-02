<?php
class edit_validator
{
    use ModuleValidatorTrait;

    public $valid;
    public $lang;
    public $module_name = 'mod_event_category';
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_event_category');
    }

    public function check()
    {
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // name Required
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // parent_id Required
        $this->valid->add(
            [
                'name' => 'parent_id',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['parent_id'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // Normalize SEO slug
        $rawSeo = (string) Req::Post('seo');
        if (trim($rawSeo) === '') {
            $rawSeo = (string) Req::Post('name');
        }
        $_POST['seo'] = $this->normalizeSeo($rawSeo);

        // seo Required
        $this->valid->add(
            [
                'name' => 'seo',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['seo'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // title Required
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // description Required
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event_category']['validator']['required'], $this->lang['mod_event_category']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // name Length
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_event_category']['validator']['length'], $this->lang['mod_event_category']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // seo Length
        $this->valid->add(
            [
                'name' => 'seo',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_event_category']['validator']['length'], $this->lang['mod_event_category']['admin']['columns']['seo'], '1', '255')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // title Length
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_event_category']['validator']['length'], $this->lang['mod_event_category']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // description Length
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_event_category']['validator']['length'], $this->lang['mod_event_category']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // Validate active language
        $requestedLang = (string) Req::Post_Safe('lang');
        if (!$this->validateLanguage($requestedLang, 'mod_event_category')) {
            return true;
        }

        // Determine record ID for uniqueness exclusion
        $recordId = (int) Req::Post_Safe('id');
        if ($recordId <= 0 && Req::Param(0) !== null && is_numeric(Req::Param(0))) {
            $recordId = (int) Req::Param(0);
        }

        // Validate SEO uniqueness
        if (!$this->validateSeoUniqueness('mod_event_category', (string) $_POST['seo'], $requestedLang, $recordId, 'mod_event_category')) {
            return true;
        }

        return false;
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->errors = $this->valid->list_errors();
        }

        if (count($this->errors) > 0) {
            return true;
        }
        return false;
    }
}
