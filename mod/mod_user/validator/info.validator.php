<?php
class info_validator
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

        $this->valid->add(
            [
                'name' => $currentPasswordField,
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['current_password_required']
            ]
        );

        // Name.
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['name_required']
            ]
        );

        // Surname.
        $this->valid->add(
            [
                'name' => 'last_name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['surname_required']
            ]
        );

        // Email.
        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['specify_email']
            ]
        );

        if ($this->_check() === True) {
            return True;
        }

        if ($this->valid->check()) {
            // Hataları Temaya Gönderelim
            $this->errors = $this->valid->list_errors();
        }

        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'email',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['email_invalid_format']
            ]
        );

        if ($this->_check() === True) {
            return True;
        }

        $u = Load::model('mod_user');
        $session = Load::lib('Session');

        $u->where_not_id_and_email($session->get('id'), Req::Post_Safe('email'))->gRo();
        if ($u->getSize() !== 0) {
            $this->errors[] = $this->lang['mod_user']['error']['email_registered'];
        }

        if ($this->_check() === True) {
            return True;
        }

        $u = Load::model('mod_user');
        $u->where_id_and_password($session->get('id'), md5(Req::Post_Safe($currentPasswordField)))->gRo();
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
