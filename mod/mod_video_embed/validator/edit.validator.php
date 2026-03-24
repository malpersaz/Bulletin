<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $hatalar = [];
    function __construct()
    {
        // Validator Kütüphanesini Yükleyelim
        $this->valid = Load::lib('Validator');
        // Dil Dosyası.
        $this->lang = Load::lang('mod_video_embed');
    }
    public function check($id)
    {
        #Check Required
        // lang Required
        $this->valid->ekle(
            [
                'name' => 'lang',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // category_id Required
        $this->valid->ekle(
            [
                'name' => 'category_id',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['category_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // name Required
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Required
        $this->valid->ekle(
            [
                'name' => 'sure',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['sure'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // created_at Required
        $this->valid->ekle(
            [
                'name' => 'created_at',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['tarih'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // embed Required
        $this->valid->ekle(
            [
                'name' => 'embed',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['embed'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // content Required
        $this->valid->ekle(
            [
                'name' => 'content',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['content'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Required
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['keywords'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Required
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'required',
                'arg' => '',
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['aciklama'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // name Length
        $this->valid->ekle(
            [
                'name' => 'name',
                'fonk' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Length
        $this->valid->ekle(
            [
                'name' => 'sure',
                'fonk' => 'length',
                'arg' => [
                    'min' => '5',
                    'max' => '8'
                ],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['sure'], '5', '8')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Length
        $this->valid->ekle(
            [
                'name' => 'title',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length
        $this->valid->ekle(
            [
                'name' => 'keywords',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['keywords'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Length
        $this->valid->ekle(
            [
                'name' => 'description',
                'fonk' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['aciklama'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check inarray
        // status in Array
        $this->valid->ekle(
            [
                'name' => 'status',
                'fonk' => 'array',
                'arg' => ['y', 'n'],
                'mesaj' => sprintf($this->lang['mod_video_embed']['validator']['inarray'], $this->lang['mod_video_embed']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with column düzenle

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
