<?php
class register_validator
{
	public $valid;
    public $lang;
    public $captcha;
    public $errors = [];
	function __construct()
	{
		// Load Validator Library
		$this->valid = Load::lib('Validator');
		// Language File.
		$this->lang = Load::lang('mod_user');
		$this->captcha = Load::lib('Captcha');
	}
	public function check()
	{
		if ($this->captcha->check(Req::Post_Safe('captcha'))) {
			$this->errors[] = $this->lang['mod_user']['register']['error']['invalid_captcha'];
		}

		if ($this->_check() === True) {
			return True;
		}

		// AD.
		$this->valid->add(
			[
				'name' => 'name',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['name_required']
			]
		);
		// last_name.
		$this->valid->add(
			[
				'name' => 'last_name',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['surname_required']
			]
		);

		// Username.
		$this->valid->add(
			[
				'name' => 'username',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['username_required']
			]
		);

		// Email.
		$this->valid->add(
			[
				'name' => 'email',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['email_required']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'name',
				'func' => 'trchar',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['name_invalid_chars']
			]
		);

		$this->valid->add(
			[
				'name' => 'last_name',
				'func' => 'trchar',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['surname_invalid_chars']
			]
		);

		$this->valid->add(
			[
				'name' => 'username',
				'func' => 'length',
				'arg' => ['min' => 2, 'max' => 12],
				'message' => $this->lang['mod_user']['register']['error']['username_length']
			]
		);

		$this->valid->add(
			[
				'name' => 'username',
				'func' => 'alphanum',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['username_invalid_chars']
			]
		);

		$this->valid->add(
			[
				'name' => 'password',
				'func' => 'length',
				'arg' => ['min' => 8, 'max' => 64],
				'message' => $this->lang['mod_user']['register']['error']['password_length']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$password = Req::Post_Safe('password');
		if (!preg_match('/[A-Z]/', $password) || 
			!preg_match('/[a-z]/', $password) || 
			!preg_match('/\d/', $password) || 
			!preg_match('/[^A-Za-z0-9]/', $password)) {
			$this->errors[] = $this->lang['mod_user']['error']['password_complexity_error'];
		}

		if (Req::Post_Safe('password') !== Req::Post_Safe('password2')) {
			$this->errors[] = $this->lang['mod_user']['error']['passwords_mismatch'];
		}

		if ($this->_check() === True) {
			return True;
		}

		// E-mail.
		$this->valid->add(
			[
				'name' => 'email',
				'func' => 'email',
				'arg' => '',
				'message' => $this->lang['mod_user']['register']['error']['invalid_email']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$u = Load::model('mod_user');

		if ($u->exists_username(Req::Post_Safe('username'))) {
			$this->errors[] = $this->lang['mod_user']['register']['error']['username_registered'];
		}

		if ($u->exists_email(Req::Post_Safe('email'))) {
			$this->errors[] = $this->lang['mod_user']['register']['error']['email_registered'];
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