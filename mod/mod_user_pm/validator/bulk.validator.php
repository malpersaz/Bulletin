<?php
class bulk_validator
{
    public $valid;
    public $lang;
    public $errors=[];

    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_user_pm');
    }

    public function check()
    {
        $errorBag = $this->lang['mod_user_pm']['error'];

        // Konu.
        $this->valid->add(
            [
                 'name' => 'subject',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['subject_required']
            ]

        );
        // Mesaj
        $this->valid->add(
            [
                 'name' => 'message',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['message_empty']
            ]

        );
        if ($this->_check() === True) {
            return True;
        }
        if ($this->valid->check()) {
            $this->errors = $this->valid->list_errors();
        }

        if (Req::Post_Safe('recipients') === '') {
            $this->errors[] = $errorBag['users_not_selected'];
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
