<?php
class edit_validator
{
    public $valid;
    public $lang;
    public $errors = [];
    function __construct()
    {
        // Load Validator Library
        $this->valid = Load::lib('Validator');
        // Language File.
        $this->lang = Load::lang('mod_video_embed');
    }
    public function check($id)
    {
        #Check Required
        // lang Required
        $this->valid->add(
            [
                'name' => 'lang',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['language'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // category_id Required
        $this->valid->add(
            [
                'name' => 'category_id',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['category_id'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // name Required
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['name'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Required
        $this->valid->add(
            [
                'name' => 'sure',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['duration'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // created_at Required
        $this->valid->add(
            [
                'name' => 'created_at',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['created_at'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // embed Required
        $this->valid->add(
            [
                'name' => 'embed',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['embed'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // content Required
        $this->valid->add(
            [
                'name' => 'content',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['content'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Required
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['title'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Required
        $this->valid->add(
            [
                'name' => 'keywords',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['keywords'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Required
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'required',
                'arg' => '',
                'message' => sprintf($this->lang['mod_video_embed']['validator']['required'], $this->lang['mod_video_embed']['admin']['columns']['description'])
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check length
        // name Length
        $this->valid->add(
            [
                'name' => 'name',
                'func' => 'length',
                'arg' => [
                    'min' => '2',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['name'], '2', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // sure Length
        $this->valid->add(
            [
                'name' => 'sure',
                'func' => 'length',
                'arg' => [
                    'min' => '5',
                    'max' => '8'
                ],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['duration'], '5', '8')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // title Length
        $this->valid->add(
            [
                'name' => 'title',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['title'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // keywords Length
        $this->valid->add(
            [
                'name' => 'keywords',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['keywords'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }
        // description Length
        $this->valid->add(
            [
                'name' => 'description',
                'func' => 'length',
                'arg' => [
                    'min' => '1',
                    'max' => '255'
                ],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['length'], $this->lang['mod_video_embed']['admin']['columns']['description'], '1', '255')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        if ($this->_checkUnsupportedCharacters() === True) {
            return True;
        }

        #Check inarray
        // status in Array
        $this->valid->add(
            [
                'name' => 'status',
                'func' => 'array',
                'arg' => ['y', 'n'],
                'message' => sprintf($this->lang['mod_video_embed']['validator']['inarray'], $this->lang['mod_video_embed']['admin']['columns']['status'], 'y,n')
            ]
        );
        if ($this->_check() === True) {
            return True;
        }

        #Check Email

        #Check Tcno

        #Check Unique

        #Check Unique with edit column

        return False;
    }

    private function _checkUnsupportedCharacters()
    {
        $fields = [
            'name' => $this->lang['mod_video_embed']['admin']['columns']['name'],
            'content' => $this->lang['mod_video_embed']['admin']['columns']['content'],
            'title' => $this->lang['mod_video_embed']['admin']['columns']['title'],
            'keywords' => $this->lang['mod_video_embed']['admin']['columns']['keywords'],
            'description' => $this->lang['mod_video_embed']['admin']['columns']['description'],
        ];

        foreach ($fields as $field => $label) {
            $value = (string) Req::Post($field);
            if ($value !== '' && preg_match('/[\x{10000}-\x{10FFFF}]/u', $value) === 1) {
                $this->errors[] = sprintf(
                    $this->lang['mod_video_embed']['validator']['unsupported_emoji'],
                    $label
                );
            }
        }

        return count($this->errors) > 0;
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
