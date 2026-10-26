<?php
class mod_user_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_user');
    }

    public function check()
    {
        // Name.
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['register']['error']['name_required']
            ]
        );
        // Last Name.
        $this->valid->add(
            [
                'name' => 'last_name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['register']['error']['surname_required']
            ]
        );
        
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'alpha_unicode',
                'arg' => '',
                'message' => $this->lang['mod_user']['register']['error']['name_invalid_chars']
            ]
        );

        $this->valid->add(
            [
                'name' => 'last_name',
                'func' => 'alpha_unicode',
                'arg' => '',
                'message' => $this->lang['mod_user']['register']['error']['surname_invalid_chars']
            ]
        );

        // Username.
        $this->valid->add(
            [
                'name' => 'username',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['specify_username']
            ]
        );
        $this->valid->add(
            [
                'name' => 'username',
                'func' => 'alphanum',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['username_invalid_chars']
            ]
        );

        $this->valid->add(
            [
                'name' => 'username',
                'func' => 'length',
                'arg' => ['min' => 2, 'max' => 20],
                'message' => $this->lang['mod_user']['error']['username_length_error']
            ]
        );

        // Password.
        $this->valid->add(
            [
                'name' => 'password',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['specify_password']
            ]

        );

        $this->valid->add(
            [
                'name' => 'password',
                'func' => 'length',
                'arg' => ['min' => 8, 'max' => 64],
                'message' => $this->lang['mod_user']['error']['password_length_error']
            ]
        );
        // E-mail.
        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['specify_email']
            ]

        );

        // E-mail.
        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'email',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['invalid_email']
            ]

        );

        // Phone (optional in admin create, validate format if provided)
        $phone_input = Req::Post_Safe('phone');
        if (!empty($phone_input)) {
            if (!preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone_input)) {
                $this->errors[] = $this->lang['mod_user']['register']['error']['invalid_phone'] ?? 'Invalid phone number';
            }
        }


        if ($this->_check() === True) {
            return True;
        }

        $u = Load::model('mod_user');

        if ($u->exists_username(Req::Post_Safe('username'))) {
            $this->errors[] = $this->lang['mod_user']['error']['username_registered'];
        }

        if ($u->exists_email(Req::Post_Safe('email'))) {
            $this->errors[] = $this->lang['mod_user']['error']['email_registered'];
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
