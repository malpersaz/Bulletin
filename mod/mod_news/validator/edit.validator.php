<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $mod_news_model;
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
        // keywords Required
        $this->valid->add(
            [
                'name' => 'keywords',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news']['validator']['required'], $this->lang['mod_news']['admin']['columns']['keywords'])
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
        // keywords Length
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

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with edit column
        $this->mod_news_model = Load::model('mod_news');
        $this->mod_news_model->where_seo_and_lang_and_not_id(html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8'), Req::Post_Safe('lang'), $id);
        if ($this->mod_news_model->limit(1)->gRo()->getSize() > 0) {
            $this->errors[] = sprintf($this->lang['mod_news']['validator']['unique'], $this->lang['mod_news']['admin']['columns']['seo'], $this->lang['mod_news']['admin']['columns']['seo']);
        }
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
