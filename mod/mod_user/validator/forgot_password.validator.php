<?php
class forgot_password_validator
{
	public $valid;
    public $lang;
    public $captcha;
    public $errors = [];
	function __construct()
	{
		// Validator Kütüphanesini Yükleyelim
		$this->valid = Load::lib('Validator');
		// Dil Dosyası.
		$this->lang = Load::lang('mod_user');
		$this->captcha = Load::lib('Captcha');
	}
	public function check()
	{


		if ($this->captcha->check(Req::Post_Safe('captcha'))) {
			$this->errors[] = $this->lang['mod_user']['forgot_password']['error']['email_not_registered_2'];
		}

		if ($this->_check() === True) {
			return True;
		}

		// Email.
		$this->valid->add(
			[
				'name' => 'email',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user']['forgot_password']['error']['invalid_email']
			]
		);

		if ($this->_check() === True) {
			return True;
		}

		$this->valid->add(
			[
				'name' => 'email',
				'func' => 'email',
				'arg' => '',
				'message' => $this->lang['mod_user']['forgot_password']['error']['invalid_email_format']
			]
		);

		if ($this->_check() === True) {
			return True;
		}


		$u = Load::model('mod_user');
		$u->Getemail(Req::Post_Safe('email'))->getResultOne(TRUE, TRUE);

		if ($u->getSize() === 0) {
			$this->errors[] = $this->lang['mod_user']['forgot_password']['error']['email_not_registered'];
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
