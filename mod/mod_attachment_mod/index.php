<?php

/**
 * mod_attachment_mod controller
 * Modernized version extending BaseModuleController
 * 
 * Amed Framework
 */
class mod_attachment_mod_mod extends BaseModuleController
{
    public $mod_attachment_mod_model;

    public function __construct()
    {
        parent::__construct('mod_attachment_mod');
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
        Run::import('mod_attachment', '_remove', [$model->mod_name]);
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
        $this->val = Load::validator('mod_attachment_mod.add');

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
            $model = Load::model('mod_attachment_mod');
            $this->processFieldData($model);
            
            if ($model->save(true)) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => $this->lang['mod_attachment_mod']['message']['success']['add'],
                        'redirect' => $this->site['saddress'] . 'mod_attachment_mod/list'
                    ]);
                }
                $this->_success_message($this->lang['mod_attachment_mod']['message']['success']['add']);
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $this->lang['mod_attachment_mod']['message']['error']['add']
                    ]);
                }
                $this->_error_message($this->lang['mod_attachment_mod']['message']['error']['add']);
            }
        }
    }

    protected function _edit($id = null)
    {
        $isAjax = $this->isAjaxRequest();
        $this->val = Load::validator('mod_attachment_mod.edit');

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
            $model = Load::model('mod_attachment_mod');
            $model->id = $id;
            $this->processFieldData($model);
            
            if (isset($model->user_id)) {
                unset($model->user_id);
            }

            if ($model->update('id')) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => $this->lang['mod_attachment_mod']['message']['success']['edit'],
                        'redirect' => $this->site['saddress'] . 'mod_attachment_mod/list'
                    ]);
                }
                $this->_success_message($this->lang['mod_attachment_mod']['message']['success']['edit']);
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $this->lang['mod_attachment_mod']['message']['error']['edit']
                    ]);
                }
                $this->_error_message($this->lang['mod_attachment_mod']['message']['error']['edit']);
            }
        }
    }
}
