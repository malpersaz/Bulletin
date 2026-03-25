<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];
    function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_attachment_mod');
    }
    public function check($id)
    {
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_key',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_key'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_value',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['required'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_key',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_key'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        $this->valid->ekle(
            [
                'name' => 'mod_value',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_attachment_mod']['validator']['length'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        if ($this->_check_display_field() === True) {
            return True;
        }

        return False;
    }

    private function _check_display_field()
    {
        $mod_value = trim((string) Req::Post_Safe('mod_value'));
        if ($mod_value !== '' && substr($mod_value, -3) === '_id') {
            $this->hatalar[] = sprintf($this->lang['mod_attachment_mod']['validator']['display_field'], $this->lang['mod_attachment_mod']['admin']['columns']['mod_value']);
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
