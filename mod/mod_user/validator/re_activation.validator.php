<?php
class re_activation_validator
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
			$this->errors[] = $this->lang['mod_user']['re_activation']['error']['email_required'];
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
				'message' => $this->lang['mod_user']['re_activation']['error']['invalid_code']
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
				'message' => $this->lang['mod_user']['re_activation']['error']['invalid_email_format']
			]
		);

		if ($this->_check() === True) {
			return True;
		}


		$u = Load::model('mod_user');
		$u->Getemail(Req::Post_Safe('email'))->gro();

		if ($u->getSize() === 0) {
			$this->errors[] = $this->lang['mod_user']['re_activation']['error']['email_not_registered'];
		}

		if ($this->_check() === True) {
			return True;
		}
		// SYNC edelim
		$u->sync();


		if ($u->status == 'y') {
			$this->errors[] = $this->lang['mod_user']['re_activation']['error']['account_already_active'];
		}

		if ($u->status == 'a') {
			$this->errors[] = $this->lang['mod_user']['re_activation']['error']['admin_activation_required'];
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
