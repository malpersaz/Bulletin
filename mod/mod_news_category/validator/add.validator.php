<?php
class add_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];
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
        $this->valid->ekle(
            [
                'name' => 'lang',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // name Required
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // parent_id Required
        $this->valid->ekle(
            [
                'name' => 'parent_id',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['parent_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Required
        $this->valid->ekle(
            [
                'name' => 'seo',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['seo'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Required
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['keywords'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // description Required
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['required'], $this->lang['mod_news_category']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // name Length
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // seo Length
        $this->valid->ekle(
            [
                'name' => 'seo',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['seo'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Length
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['keywords'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        // description Length
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['length'], $this->lang['mod_news_category']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }


        #Check inarray
        // root in Array
        $this->valid->ekle(
            [
                'name' => 'root',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_news_category']['validator']['inarray'], $this->lang['mod_news_category']['admin']['columns']['root'], 'y,n')
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
            $this->hatalar = $this->valid->list_errors();
        }

        if (count($this->hatalar) > 0) {
            return True;
        }
        return False;
    }
}
