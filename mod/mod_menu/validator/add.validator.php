<?php
class add_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_menu');
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
                'mesaj' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['language'] ?? 'language', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
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
                'mesaj' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['name'] ?? 'name', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
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
                'mesaj' => str_replace(':field', $this->lang['mod_menu']['admin']['columns']['parent_id'] ?? 'parent_id', $this->lang['mod_menu']['validation']['required'] ?? '%s Pêwîst e')
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
                'mesaj' => str_replace([':field', ':min', ':max'], [$this->lang['mod_menu']['admin']['columns']['name'] ?? 'name', '2', '255'], $this->lang['mod_menu']['validation']['min_length'] ?? '%s Divê herî kêm %s tîpan be')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // target in Array
        $this->valid->ekle(
            [
                'name' => 'target',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => "Hedef xelet e"
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // status in Array
        $this->valid->ekle(
            [
                'name' => 'status',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => "Weşan xelet e"
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
