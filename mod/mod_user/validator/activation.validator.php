<?php
class activation_validator
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
			$this->errors[] = $this->lang['mod_user']['activation']['error']['invalid_captcha'];
		}

		if ($this->_check() === True) {
			return True;
		}

		// Activation Code
		$this->valid->add(
			[
				'name' => 'code',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['activation']['error']['code_required']
			]
		);


		if ($this->_check() === True) {
			return True;
		}

		$code = Load::model('mod_user.activation');

		if ($code->exists_code(Req::Post_Safe('code')) === False) {
			$this->errors[] = $this->lang['mod_user']['activation']['error']['invalid_code'];
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
