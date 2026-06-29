<?php

class add_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_redirect');
    }

    public function check()
    {
        $this->valid->ekle([
            'name' => 'old_url',
            'fonk' => 'required',
            'arg' => '',
            'mesaj' => sprintf($this->lang['mod_redirect']['validator']['required'], $this->lang['mod_redirect']['admin']['columns']['old_url']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->ekle([
            'name' => 'new_url',
            'fonk' => 'required',
            'arg' => '',
            'mesaj' => sprintf($this->lang['mod_redirect']['validator']['required'], $this->lang['mod_redirect']['admin']['columns']['new_url']),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->ekle([
            'name' => 'old_url',
            'fonk' => 'length',
            'arg' => ['min' => '2', 'max' => '250'],
            'mesaj' => sprintf($this->lang['mod_redirect']['validator']['length'], $this->lang['mod_redirect']['admin']['columns']['old_url'], '2', '250'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->ekle([
            'name' => 'new_url',
            'fonk' => 'length',
            'arg' => ['min' => '1', 'max' => '250'],
            'mesaj' => sprintf($this->lang['mod_redirect']['validator']['length'], $this->lang['mod_redirect']['admin']['columns']['new_url'], '1', '250'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->ekle([
            'name' => 'status_code',
            'fonk' => 'inarray',
            'arg' => '301,302',
            'mesaj' => sprintf($this->lang['mod_redirect']['validator']['inarray'], $this->lang['mod_redirect']['admin']['columns']['status_code'], '301,302'),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        // Check if old_url is unique in database
        $old_url = trim((string) Req::Post_Safe('old_url'));
        $db = Load::model('mod_redirect');
        $exists = $db->where('old_url', $old_url)->single();
        if ($exists) {
            $this->hatalar[] = $this->lang['mod_redirect']['validator']['unique_old_url'];
            return true;
        }

        return false;
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->hatalar = $this->valid->list_errors();
        }

        return count($this->hatalar) > 0;
    }
}
