<?php
class change_password_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_user');
    }

    public function check()
    {
        $currentPasswordField = 'current_password';

        // Current Password.
        $this->valid->add(
            [
                'name' => $currentPasswordField,
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['current_password_required']
            ]
        );

        // New Password.
        $this->valid->add(
            [
                'name' => 'new_password',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['new_password_required']
            ]
        );

        $this->valid->add(
            [
                'name' => 'new_password',
                'func' => 'length',
                'arg' => ['min' => 6, 'max' => 20],
                'message' => $this->lang['mod_user']['error']['password_length_error']
            ]
        );

        // Confirm Password.
        $this->valid->add(
            [
                'name' => 'confirm_password',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['confirm_password_required']
            ]
        );

        $this->valid->add(
            [
                'name' => 'confirm_password',
                'func' => 'length',
                'arg' => ['min' => 6, 'max' => 20],
                'message' => $this->lang['mod_user']['error']['password_length_error']
            ]
        );

        if ($this->_check() === True) {
            return True;
        }

        if ($this->valid->check()) {
            // Hataları Temaya Gönderelim
            $this->errors = $this->valid->list_errors();
        }

        // Passwords Mismatch Check
        if (Req::Post_Safe('new_password') != Req::Post_Safe('confirm_password')) {
            $this->errors[] = $this->lang['mod_user']['error']['passwords_mismatch'];
        }

        // Current Password Validation
        $u = Load::model('mod_user');
        $u->where_id_and_password(Load::lib('Session')->getir('id'), md5(Req::Post_Safe($currentPasswordField)))->gRo();
        if ($u->getSize() === 0) {
            $this->errors[] = $this->lang['mod_user']['error']['current_password_incorrect'];
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
