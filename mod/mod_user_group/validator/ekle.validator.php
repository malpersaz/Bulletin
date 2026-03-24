<?php
class ekle_validator
{
	public $valid;
    public $lang;
    public $hatalar = [];
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
		$this->valid->ekle(
			[
				'name' => 'name',
				'fonk' => 'required',
				'arg' => '',
				'mesaj' => $this->lang['mod_user_group']['hata']['1']
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
			$this->hatalar = $this->valid->list_errors();
		}

		if (count($this->hatalar) > 0) {
			return True;
		}
		return False;
	}
}
