<?php
class edit_validator
{
    use ModuleValidatorTrait;

    public $valid;
    public $lang;
    public $mod_event_model;
    public $module_name = 'mod_event';
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_event');
    }

    public function check()
    {
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['language'])
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
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // subject Required
        $this->valid->add(
            [
                'name' => 'subject',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['subject'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // category_id Required
        $this->valid->add(
            [
                'name' => 'category_id',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['category_id'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // start_date Required
        $this->valid->add(
            [
                'name' => 'start_date',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['start_date'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // summary Required
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['summary'])
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // content Required
        $this->valid->add(
            [
                'name' => 'content',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_event']['validator']['required'], $this->lang['mod_event']['admin']['columns']['content'])
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

        // name Length
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_event']['validator']['length'], $this->lang['mod_event']['admin']['columns']['name'], '1', '255')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // subject Length
        $this->valid->add(
            [
                'name' => 'subject',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '120'
                ],
                'message' => sprintf($this->lang['mod_event']['validator']['length'], $this->lang['mod_event']['admin']['columns']['subject'], '2', '120')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // summary Length
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'length',
                'arg' => [
                    'min' => '90',
                    'max' => '255'
                ],
                'message' => $this->lang['mod_event']['validator']['deck_length']
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        // Date order validation: end_date must be >= start_date if provided
        $startDate = (string) Req::Post_Safe('start_date');
        $endDate = (string) Req::Post_Safe('end_date');
        if ($startDate !== '' && $endDate !== '') {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            if ($startTimestamp !== false && $endTimestamp !== false && $endTimestamp < $startTimestamp) {
                $this->appendError($this->lang['mod_event']['validator']['invalid_date_range']);
                return true;
            }
        }

        // Enum arrays
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_event']['validator']['inarray'], $this->lang['mod_event']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === true) {
            return true;
        }

        if (Req::Post_Safe('slider') !== null && Req::Post_Safe('slider') !== '') {
            $this->valid->add(
                [
                    'name' => 'slider',
                    'func' => 'array',
                    'arg' => ['y', 'n'],
                    'message' => sprintf($this->lang['mod_event']['validator']['inarray'], $this->lang['mod_event']['admin']['columns']['slider'], 'y,n')
                ]
            );
            if ($this->_check() === true) {
                return true;
            }
        }

        if (Req::Post_Safe('deck') !== null && Req::Post_Safe('deck') !== '') {
            $this->valid->add(
                [
                    'name' => 'deck',
                    'func' => 'array',
                    'arg' => ['y', 'n'],
                    'message' => sprintf($this->lang['mod_event']['validator']['inarray'], $this->lang['mod_event']['admin']['columns']['deck'], 'y,n')
                ]
            );
            if ($this->_check() === true) {
                return true;
            }
        }

        // Validate active language
        $requestedLang = (string) Req::Post_Safe('lang');
        if (!$this->validateLanguage($requestedLang, 'mod_event')) {
            return true;
        }

        // Validate category relation
        $categoryId = (int) Req::Post_Safe('category_id');
        if (!$this->validateCategoryRelation($categoryId, $requestedLang, 'mod_event_category', 'mod_event')) {
            return true;
        }

        // Determine record ID for uniqueness exclusion
        $recordId = (int) Req::Post_Safe('id');
        if ($recordId <= 0 && Req::Param(0) !== null && is_numeric(Req::Param(0))) {
            $recordId = (int) Req::Param(0);
        }

        // Validate SEO uniqueness excluding current record
        if (!$this->validateSeoUniqueness('mod_event', (string) $_POST['seo'], $requestedLang, $recordId, 'mod_event')) {
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
