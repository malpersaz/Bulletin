<?php
class password_reset_validator
{
	public $valid;
    public $lang;
    public $captcha;
    public $errors = [];
	function __construct()
	{
		// Load Validator Library
		$this->valid = Load::lib('Validator');
		// Language File
		$this->lang = Load::lang('mod_user');
		$this->captcha = Load::lib('Captcha');
	}
	public function check()
	{


		if ($this->captcha->check(Req::Post_Safe('captcha'))) {
			$this->errors[] = $this->lang['mod_user']['password_reset']['error']['invalid_captcha'];
		}

		if ($this->_check() === True) {
			return True;
		}


		// Reset Code
		$this->valid->add(
			[
				'name' => 'code',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['password_reset']['error']['code_required']
			]
		);


		if ($this->_check() === True) {
			return True;
		}

		$code = Load::model('mod_user.password_reset');
		$code->Getcode(Req::Post_Safe('code'))->getResultOne(TRUE, TRUE);

		if ($code->getSize() === 0) {
			$this->errors[] = $this->lang['mod_user']['password_reset']['error']['invalid_code'];
		}

		if ($this->_check() === True) {
			return True;
		}

		$db = Load::lib('Database');
		$safe_code = preg_replace('/[^A-Za-z0-9]/', '', Req::Post_Safe('code'));
		$sql = "SELECT TIMESTAMPDIFF(SECOND, `created_at`, NOW()) AS `age` FROM `mod_user_password_reset` WHERE `code` = '{$safe_code}'";
		$db->fetch_array($sql);
		$row = $db->getResultOne()->toArray();
		$age = isset($row['age']) ? (int)$row['age'] : null;

		if ($age === null || $age > 3600) {
			$this->errors[] = $this->lang['mod_user']['password_reset']['error']['expired_code'];
		}

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'new_password',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['error']['new_password_required']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'new_password',
				'func' => 'length',
				'arg' => ['min' => 8, 'max' => 64],
				'message' => $this->lang['mod_user']['error']['password_length_error']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$new_password = Req::Post_Safe('new_password');
		if (!preg_match('/[A-Z]/', $new_password) || 
			!preg_match('/[a-z]/', $new_password) || 
			!preg_match('/[0-9]/', $new_password) || 
			!preg_match('/[^A-Za-z0-9]/', $new_password)) {
			$this->errors[] = $this->lang['mod_user']['error']['password_complexity_error'];
		}

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'confirm_password',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['error']['confirm_password_required']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'confirm_password',
				'func' => 'equal',
				'arg' => 'new_password',
				'message' => $this->lang['mod_user']['error']['passwords_mismatch']
			]
		);

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
