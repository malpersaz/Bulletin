<?php

class add_validator
{
    public $valid;
    public $lang;
    public $errors = [];

    public function __construct()
    {
        $this->valid = Load::lib('Validator');
        $this->lang = Load::lang('mod_socialmedia_post');
    }

    public function check()
    {
        $this->valid->add([
            'name' => 'lang',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf(
                $this->lang['mod_socialmedia_post']['validator']['required'],
                $this->lang['mod_socialmedia_post']['admin']['columns']['language']
            ),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'url',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf(
                $this->lang['mod_socialmedia_post']['validator']['required'],
                $this->lang['mod_socialmedia_post']['admin']['columns']['url']
            ),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'name',
            'func' => 'required',
            'arg' => '',
            'message' => sprintf(
                $this->lang['mod_socialmedia_post']['validator']['required'],
                $this->lang['mod_socialmedia_post']['admin']['columns']['name']
            ),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $this->valid->add([
            'name' => 'name',
            'func' => 'length',
            'arg' => ['min' => '1', 'max' => '255'],
            'message' => sprintf(
                $this->lang['mod_socialmedia_post']['validator']['length'],
                $this->lang['mod_socialmedia_post']['admin']['columns']['name'],
                '1',
                '255'
            ),
        ]);
        if ($this->_check() === true) {
            return true;
        }

        $rawUrl = trim((string) Req::Post('url'));
        $detected = SocialPostProviderService::detectProvider($rawUrl);
        if ($detected === null) {
            $this->errors['url'] = $this->lang['mod_socialmedia_post']['validator']['invalid_url']
                ?? $this->lang['mod_socialmedia_post']['errors']['invalid_url'];
            return true;
        }

        $statusVal = trim((string) Req::Post_Safe('status'));
        if ($statusVal !== '' && !in_array($statusVal, ['y', 'n'], true)) {
            $this->errors['status'] = sprintf(
                $this->lang['mod_socialmedia_post']['validator']['inarray'],
                $this->lang['mod_socialmedia_post']['admin']['columns']['status']
            );
            return true;
        }

        $langCode = trim((string) Req::Post_Safe('lang'));
        $existingModel = Load::model('mod_socialmedia_post');
        $existingModel->flush();
        $existingModel->where_provider($detected['provider'])
            ->addWhere("post_id='" . Req::Clear($detected['post_id']) . "'")
            ->addWhere("lang='" . Req::Clear($langCode) . "'")
            ->limit(1)
            ->gRo();
        if ($existingModel->getSize() > 0) {
            $this->errors['url'] = $this->lang['mod_socialmedia_post']['errors']['duplicate_post'];
            return true;
        }

        return false;
    }

    public function _check()
    {
        if ($this->valid->check()) {
            $this->errors = array_merge($this->errors, $this->valid->list_errors());
        }

        return count($this->errors) > 0;
    }
}
