<?php
class edit_validator
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

    public function check($id)
    {
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['name_required']
            ]
        );

        $this->valid->add(
            [
                'name' => 'last_name',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['surname_required']
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

        // Email.
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

        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['specify_email']
            ]
        );

        $this->valid->add(
            [
                'name' => 'email',
                'func' => 'email',
                'arg' => '',
                'message' => $this->lang['mod_user']['error']['invalid_email']
            ]
        );

        $this->valid->add(
            [
                'name' => 'phone',
                'func' => 'required',
                'arg' => '',
                'message' => $this->lang['mod_user']['register']['error']['phone_required']
            ]
        );

        if (Req::Post_Safe('password') != '') {
            $this->valid->add(
                [
                    'name' => 'password',
                    'func' => 'length',
                    'arg' => ['min' => 6, 'max' => 20],
                    'message' => $this->lang['mod_user']['error']['password_length_error']
                ]
            );
        }

        if ($this->_check() === True) {
            return True;
        }

        if ($this->valid->check()) {
            // Assign variables to theme template
            $this->errors = $this->valid->list_errors();
        }

        $u = Load::model('mod_user');

        // Check if username is already taken by someone else
        if ($u->where_not_id_and_username($id, Req::Post_Safe('username'))->gRo()->getSize() !== 0) {
            $this->errors[] = $this->lang['mod_user']['error']['username_registered'];
        }

        // Check if email is already taken by someone else
        if ($u->where_not_id_and_email($id, Req::Post_Safe('email'))->gRo()->getSize() !== 0) {
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
