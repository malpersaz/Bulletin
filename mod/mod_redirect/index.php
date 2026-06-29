<?php

class mod_redirect_mod extends indexMain
{
    public $mod_redirect_model;

    public function __construct()
    {
        parent::__construct();
        // Load templates lib
        $this->tema = Load::lib('AdminTemplate');
        // Load database lib
        $this->db = Load::lib('Database');
        // Load language files
        $this->lang = Load::lang('mod_redirect');
        $this->tema->lang($this->lang);
        // Session lib
        $this->session = Load::lib('Session');
        // User Model for permission checks
        $this->user = Load::model('mod_user.user');
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_redirect', 'list', $this->lang['mod_redirect']['role_errors']['list'], 1) === FALSE) {
            return FALSE;
        }

        Run::import('management', '_index');

        $model = Load::model('mod_redirect');
        $model->order('id', 'desc')->gR();
        $redirects = $model->toArray();

        $this->site['title'] = $this->lang['mod_redirect']['list'];
        $this->tema->tpl('mod_redirect/list');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('redirects', $redirects);
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_redirect', 'add', $this->lang['mod_redirect']['role_errors']['add'], 1) === FALSE) {
            return FALSE;
        }

        if (Req::is_Post('add')) {
            $this->_add();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_redirect']['add'];
        $this->tema->tpl('mod_redirect/add');
        $this->tema->veri('site', $this->site);
    }

    private function _add()
    {
        $this->val = Load::validator('mod_redirect.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $model = Load::model('mod_redirect');
            $model->old_url = trim((string) Req::Post_Safe('old_url'));
            $model->new_url = trim((string) Req::Post_Safe('new_url'));
            $model->status_code = (int) Req::Post_Safe('status_code');

            if ($model->save(TRUE)) {
                $this->session->set_flash('success_message', $this->lang['mod_redirect']['message']['oke']['add']);
                Url::redir($this->site['saddress'] . 'mod_redirect/list');
            } else {
                $this->_error_message($this->lang['mod_redirect']['message']['error']['add']);
            }
        }
    }

    public function edit($id = NULL)
    {
        if ($this->user->hasPermission('mod_redirect', 'edit', $this->lang['mod_redirect']['role_errors']['edit'], 1) === FALSE) {
            return FALSE;
        }

        $id = Req::Url_Clear($id);
        $model = Load::model('mod_redirect');
        if ($model->exists_id($id) === FALSE) {
            $this->_error($this->lang['mod_redirect']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_redirect/edit');
            return;
        }

        $redirect = $model->getResultOne()->toArray();

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_redirect']['edit'];
        $this->tema->tpl('mod_redirect/edit');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('redirect', $redirect);
    }

    private function _edit($id)
    {
        $this->val = Load::validator('mod_redirect.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $model = Load::model('mod_redirect');
            $model->id = $id;
            $model->old_url = trim((string) Req::Post_Safe('old_url'));
            $model->new_url = trim((string) Req::Post_Safe('new_url'));
            $model->status_code = (int) Req::Post_Safe('status_code');

            if ($model->update('id')) {
                $this->session->set_flash('success_message', $this->lang['mod_redirect']['message']['oke']['edit']);
                Url::redir($this->site['saddress'] . 'mod_redirect/list');
            } else {
                $this->_error_message($this->lang['mod_redirect']['message']['error']['edit']);
            }
        }
    }

    public function delete()
    {
        if ($this->user->hasPermission('mod_redirect', 'delete', $this->lang['mod_redirect']['role_errors']['delete'], 1) === FALSE) {
            if (Req::is_Ajax()) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['role_errors']['delete']]);
                return;
            }
            return FALSE;
        }

        $id = (int) Req::Post_Safe('id');
        if (!$id) {
            if (Req::is_Ajax()) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['errors']['record']]);
                return;
            }
            exit($this->lang['mod_redirect']['errors']['record']);
        }

        $model = Load::model('mod_redirect');
        $model->where_id($id)->gR();
        if ($model->getSize() === 0) {
            if (Req::is_Ajax()) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['errors']['record']]);
                return;
            }
            exit($this->lang['mod_redirect']['errors']['record']);
        }

        $model->sync();
        if ($model->delete()) {
            if (Req::is_Ajax()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $this->lang['mod_redirect']['message']['oke']['delete']
                ]);
                return;
            }
            Url::redir($this->site['saddress'] . 'mod_redirect/list');
        } else {
            if (Req::is_Ajax()) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['errors']['record']]);
                return;
            }
            exit($this->lang['mod_redirect']['errors']['record']);
        }
    }

    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', TRUE);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
