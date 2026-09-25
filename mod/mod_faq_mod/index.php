<?php

/**
 * mod_faq_mod controller
 * Modernized version extending BaseModuleController
 * 
 * Amed Framework
 */
class mod_faq_mod_mod extends BaseModuleController
{
    public $mod_faq_mod_model;

    public function __construct()
    {
        parent::__construct('mod_faq_mod');
    }

    protected function getDatatableColumns()
    {
        return [
            'name',
            'mod_name',
            'mod_key',
            'mod_value'
        ];
    }

    protected function beforeDelete($model, $id)
    {
        Run::import('mod_faq', '_remove', [$model->mod_name]);
        return $model;
    }

    protected function _success_message($message)
    {
        $this->theme->set('success', true);
        $this->theme->set('message', $message);
    }

    protected function _error_message($message)
    {
        $this->theme->set('error', true);
        $this->theme->set('errors', [$message]);
    }

    protected function _add()
    {
        $isAjax = $this->isAjaxRequest();
        $this->val = Load::validator('mod_faq_mod.add');

        if ($this->val->check()) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->errors),
                    'errors' => $this->val->errors
                ]);
            }
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $model = Load::model('mod_faq_mod');
            $this->processFieldData($model);
            
            if ($model->save(true)) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => isset($this->lang['mod_faq_mod']['message']['success']['add']) ? $this->lang['mod_faq_mod']['message']['success']['add'] : 'FAQ module added successfully.',
                        'redirect' => $this->site['saddress'] . 'mod_faq_mod/list'
                    ]);
                }
                $this->_success_message(isset($this->lang['mod_faq_mod']['message']['success']['add']) ? $this->lang['mod_faq_mod']['message']['success']['add'] : 'FAQ module added successfully.');
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq_mod']['message']['error']['add']) ? $this->lang['mod_faq_mod']['message']['error']['add'] : 'Failed to add FAQ module.'
                    ]);
                }
                $this->_error_message(isset($this->lang['mod_faq_mod']['message']['error']['add']) ? $this->lang['mod_faq_mod']['message']['error']['add'] : 'Failed to add FAQ module.');
            }
        }
    }

    protected function _edit($id = null)
    {
        $isAjax = $this->isAjaxRequest();
        $this->val = Load::validator('mod_faq_mod.edit');

        if ($this->val->check($id)) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->errors),
                    'errors' => $this->val->errors
                ]);
            }
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $model = Load::model('mod_faq_mod');
            $model->id = $id;
            $this->processFieldData($model);
            
            if (isset($model->user_id)) {
                unset($model->user_id);
            }

            if ($model->update('id')) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => isset($this->lang['mod_faq_mod']['message']['success']['edit']) ? $this->lang['mod_faq_mod']['message']['success']['edit'] : 'FAQ module updated successfully.',
                        'redirect' => $this->site['saddress'] . 'mod_faq_mod/list'
                    ]);
                }
                $this->_success_message(isset($this->lang['mod_faq_mod']['message']['success']['edit']) ? $this->lang['mod_faq_mod']['message']['success']['edit'] : 'FAQ module updated successfully.');
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq_mod']['message']['error']['edit']) ? $this->lang['mod_faq_mod']['message']['error']['edit'] : 'Failed to update FAQ module.'
                    ]);
                }
                $this->_error_message(isset($this->lang['mod_faq_mod']['message']['error']['edit']) ? $this->lang['mod_faq_mod']['message']['error']['edit'] : 'Failed to update FAQ module.');
            }
        }
    }
}
