<?php
class add_validator
{
    public $valid;
    public $lang;
    public $errors = [];
    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_news_category');
    }
    public function check()
    {
        #Check Required
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['language'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['name'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['parent_id'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['seo'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['title'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['keywords'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['description'])
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['name'], '2', '255')
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['seo'], '1', '255')
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['title'], '1', '255')
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['keywords'], '1', '255')
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
                'message' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }


        #Check inarray
        // root in Array
        $this->valid->add(
            [
                'name' => 'root',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_news_category']['validator']['inarray'], $this->lang['mod_news_category']['admin']['columns']['root'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column

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
