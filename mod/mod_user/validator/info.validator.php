<?php
class info_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File
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
            // Send errors to the theme
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

        if (!$this->_password_matches($session->get('id'), Req::Post_Safe($currentPasswordField))) {
            $this->errors[] = $this->lang['mod_user']['error']['current_password_incorrect'];
        }

        if ($this->_check() === True) {
            return True;
        }

        return False;
    }

    private function _password_matches($user_id, $plain_password)
    {
        $class = Load::modelfile('mod_user');
        $u = new $class();
        $u->select('password')->where_id($user_id)->limit(1)->gRo();
        $user = $u->toArray();
        if (!is_array($user)) {
            return False;
        }

        if (!is_string($plain_password) || $plain_password === '') {
            return False;
        }

        $stored_password = isset($user['password']) && is_string($user['password']) ? $user['password'] : '';
        if ($stored_password === '') {
            return False;
        }

        if (function_exists('password_get_info') && function_exists('password_verify')) {
            $info = password_get_info($stored_password);
            if (!empty($info['algo'])) {
                return password_verify($plain_password, $stored_password);
            }
        }

        $legacy_hash = md5($plain_password);
        if (function_exists('hash_equals')) {
            return hash_equals((string)$stored_password, $legacy_hash);
        }

        return (string)$stored_password === $legacy_hash;
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
