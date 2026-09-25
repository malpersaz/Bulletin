<?php
class send_validator
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

        // Recipient.
        $this->valid->add(
            [
                 'name' => 'recipient',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['recipient_required']
            ]
        );

        // Subject.
        $this->valid->add(
            [
                 'name' => 'subject',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['subject_required']
            ]

        );
        // Message
        $this->valid->add(
            [
                 'name' => 'message',
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
        $u = Load::model('mod_user');

        if ($u->exists_username(Req::Post_Safe('recipient')) === False) {
            $this->errors[] = $errorBag['user_not_found'];
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
