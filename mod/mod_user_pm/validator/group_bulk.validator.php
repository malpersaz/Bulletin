<?php
class group_bulk_validator
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

        // Grup.
        $this->valid->add(
            [
                 'name' => 'group_id',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['group_not_selected']
            ]
        );

        // Konu.
        $this->valid->add(
            [
                 'name' => 'subject',
                 'func' => 'required',
                 'arg' => '',
                 'message' => $errorBag['subject_required']
            ]

        );
        // Mesaj
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
        $u = Load::model('mod_user_group');
        $u->where_group_id(Req::Post_Safe('group_id'));
        if ($u->gRo()->getSize() === 0) {
            $this->errors[] = $errorBag['group_not_found'];
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
