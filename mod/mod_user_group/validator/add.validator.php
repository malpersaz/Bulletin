<?php
class add_validator
{
	public $valid;
    public $lang;
    public $errors = [];
	function __construct()
	{
		// Validator Kütüphanesini Yükleyelim
		$this->valid = Load::lib('Validator');
		// Dil Dosyası.
		$this->lang = Load::lang('mod_user_group');
	}
	public function check()
	{
		// AD.
		$this->valid->add(
			[
				'name' => 'name',
				'func' => 'required',
				'arg' => '',
				'message' => $this->lang['mod_user_group']['hata']['1']
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
