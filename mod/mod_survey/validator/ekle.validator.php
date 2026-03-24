<?php

class ekle_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];

    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_survey');
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
                'mesaj' => sprintf($this->lang['mod_survey']['validator']['required'], $this->lang['mod_survey']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // soru Required
        $this->valid->ekle(
            [
                'name' => 'soru',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_survey']['validator']['required'], $this->lang['mod_survey']['admin']['columns']['soru'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // soru Length
        $this->valid->ekle(
            [
                'name' => 'soru',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_survey']['validator']['length'], $this->lang['mod_survey']['admin']['columns']['soru'], '2', '255')
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
