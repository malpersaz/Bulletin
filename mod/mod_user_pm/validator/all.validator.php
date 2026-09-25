<?php
class all_validator
{
    public $valid;
    public $lang;
    public $errors=[];

    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_user_pm');
    }

    public function check()
    {
        $errorBag = $this->lang['mod_user_pm']['error'];
        $subjectField = 'subject';
        $messageField = 'message';

        // Konu.
        $this->valid->add(
            [
                 'name' => $subjectField,
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['subject_required']
            ]

        );
        // Mesaj
        $this->valid->add(
            [
                 'name' => $messageField,
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['message_empty']
            ]

        );
        if ($this->_check() === True) {
            return True;
        }
        if ($this->valid->check()) {
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
