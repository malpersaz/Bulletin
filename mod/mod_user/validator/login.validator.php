<?php
class login_validator
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
		// Username.
		$this->valid->add(
			[
				'name' => 'username',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['error']['specify_username']
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

		if ($this->_check() === True) {
			return True;
		}

		if ($this->valid->check()) {
			// Assign variables to theme template
			$this->errors = $this->valid->list_errors();
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
