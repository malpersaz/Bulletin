<?php
class edit_validator
{
	public $valid;
    public $lang;
    public $errors = [];
	function __construct()
	{
		// Load the validator library.
		$this->valid = Load::lib('Validator');
		// Load the module language file.
		$this->lang = Load::lang('mod_user_group');
	}
	public function check($id)
	{
		// Validate the group name.
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
