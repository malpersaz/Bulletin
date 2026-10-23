<?php
class edit_validator
{
    use ModuleValidatorTrait;

    public $valid;
    public $lang;
    public $mod_news_model;
    public $module_name = 'mod_news';
    public $errors = [];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_news');
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
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['language'])
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
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // category_id Required
        $this->valid->add(
            [
                'name' => 'category_id',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['category_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // created_at Required
        $this->valid->add(
            [
                'name' => 'created_at',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['created_at'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // summary Required
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['summary'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // news body Required
        $this->valid->add(
            [
                'name' => 'content',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['news'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Required
        $this->valid->add(
            [
                'name' => 'seo',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['seo'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // description Required
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // Normalize SEO slug (Rule 10)
        $_POST['seo'] = $this->normalizeSeo((string) Req::Post('seo'));

        #Check length
        // name Length
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['name'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
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
                'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['seo'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
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
                'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length (optional)
        if (trim((string) Req::Request('keywords')) !== '') {
            $this->valid->add(
                [
                    'name' => 'keywords',
                    'func' => 'length',
                    'arg' => [
                        'min' => '1',
                        'max' => '255'
                    ],
                    'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['keywords'], '1', '255')
                ]
            );
            if ($this->_check() === True) {
                return True;
            }
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
                'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // summary Length
        $this->valid->add(
            [
                'name' => 'summary',
                'func' => 'length',
                'arg' => [
                    'min' => '90',
                    'max' => '120'
                ],
                'message' => $this->lang['mod_news']['validator']['deck_length']
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // status in Array
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_news']['validator']['inarray'], $this->lang['mod_news']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // deck in Array
        $this->valid->add(
            [
                'name' => 'deck',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_news']['validator']['inarray'], $this->lang['mod_news']['admin']['columns']['deck'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // Validate active language
        $requestedLang = (string) Req::Post_Safe('lang');
        if (!$this->validateLanguage($requestedLang, 'mod_news')) {
            return True;
        }

        // Validate category relation (exists, non-root, matching language)
        $categoryId = (int) Req::Post_Safe('category_id');
        if (!$this->validateCategoryRelation($categoryId, $requestedLang, 'mod_news_category', 'mod_news')) {
            return True;
        }

        // Validate SEO uniqueness within language excluding current record
        if (!$this->validateSeoUniqueness('mod_news', (string) $_POST['seo'], $requestedLang, (int) $id, 'mod_news')) {
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

