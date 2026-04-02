<?php

class mod_attachment_mod_mod extends indexMain
{
    public $mod_attachment_mod_model;

    public function __construct()
    {
        parent::__construct();

        $this->tema = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');
        $this->lang = Load::lang('mod_attachment_mod');
        $this->tema->lang($this->lang);
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_attachment_mod', 'add', $this->lang['mod_attachment_mod']['role_errors']['add'], 1) === false) {
            return false;
        }

        if (Req::is_Post('add')) {
            $this->_add();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment_mod']['add'];
        $this->tema->tpl('mod_attachment_mod/add');
        $this->tema->veri('site', $this->site);
    }

    public function edit($id = null)
    {
        if ($this->user->hasPermission('mod_attachment_mod', 'edit', $this->lang['mod_attachment_mod']['role_errors']['edit'], 1) === false) {
            return false;
        }

        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');
        $id = Req::Url_Clear($id);

        if ($this->mod_attachment_mod_model->exists_id($id) === false) {
            $this->_error($this->lang['mod_attachment_mod']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_attachment_mod/edit');
            return;
        }

        $record = $this->mod_attachment_mod_model->getResultOne()->toArray();

        if ($this->user->checkPermission('mod_attachment_mod', 'moderate_edit', $record['user_id'], $this->lang['mod_attachment_mod']['role_errors']['moderate_edit'], 1) === false) {
            return false;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_attachment_mod']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $record);
        $this->tema->tpl('mod_attachment_mod/edit');
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_attachment_mod', 'list', $this->lang['mod_attachment_mod']['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment_mod']['list'];
        $this->tema->tpl('mod_attachment_mod/list');
        $this->tema->veri('site', $this->site);
    }

    private function _add()
    {
        $this->val = Load::validator('mod_attachment_mod.add');

        if ($this->val->check()) {
            foreach ($_POST as $key => $value) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }

            $this->tema->veri('errors', $this->val->hatalar);
            return;
        }

        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');
        $this->mod_attachment_mod_model->name = Req::Post_Safe('name');
        $this->mod_attachment_mod_model->mod_name = Req::Post_Safe('mod_name');
        $this->mod_attachment_mod_model->mod_key = Req::Post_Safe('mod_key');
        $this->mod_attachment_mod_model->mod_value = Req::Post_Safe('mod_value');
        $this->mod_attachment_mod_model->user_id = $this->session->getir('id');

        if ($this->mod_attachment_mod_model->save(true)) {
            $this->_oke_message($this->lang['mod_attachment_mod']['message']['oke']['add']);
            return;
        }

        $this->_error_message($this->lang['mod_attachment_mod']['message']['error']['add']);
    }

    private function _edit($id = null)
    {
        $this->val = Load::validator('mod_attachment_mod.edit');

        if ($this->val->check($id)) {
            foreach ($_POST as $key => $value) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }

            $this->tema->veri('errors', $this->val->hatalar);
            return;
        }

        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');
        $this->mod_attachment_mod_model->id = $id;
        $this->mod_attachment_mod_model->name = Req::Post_Safe('name');
        $this->mod_attachment_mod_model->mod_name = Req::Post_Safe('mod_name');
        $this->mod_attachment_mod_model->mod_key = Req::Post_Safe('mod_key');
        $this->mod_attachment_mod_model->mod_value = Req::Post_Safe('mod_value');

        if (isset($this->mod_attachment_mod_model->user_id)) {
            unset($this->mod_attachment_mod_model->user_id);
        }

        if ($this->mod_attachment_mod_model->update('id')) {
            $this->_oke_message($this->lang['mod_attachment_mod']['message']['oke']['edit']);
            return;
        }

        $this->_error_message($this->lang['mod_attachment_mod']['message']['error']['edit']);
    }

    public function delete()
    {
        if ($this->user->hasPermission('mod_attachment_mod', 'delete', $this->lang['mod_attachment_mod']['role_errors']['delete']) === false) {
            return false;
        }

        if (!Req::is_Post('delete')) {
            exit($this->lang['mod_attachment_mod']['errors']['record']);
        }

        if ($this->_delete(Req::Post_Safe('delete'))) {
            echo 'OK';
            return;
        }

        echo 'Error';
    }

    public function _delete($delete_ids)
    {
        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');

        foreach ($delete_ids as $id) {
            $this->mod_attachment_mod_model->flush();
            $this->mod_attachment_mod_model->where_id($id)->gRo();

            if ($this->mod_attachment_mod_model->getSize() === 0) {
                exit($this->lang['mod_attachment_mod']['errors']['record']);
            }

            $this->mod_attachment_mod_model->sync();

            if ($this->user->checkPermission('mod_attachment_mod', 'moderate_delete', $this->mod_attachment_mod_model->user_id, $this->lang['mod_attachment_mod']['role_errors']['moderate_delete']) === false) {
                return false;
            }

            Run::import('mod_attachment', '_remove', [$this->mod_attachment_mod_model->mod_name]);
            $this->mod_attachment_mod_model->delete();
        }

        return true;
    }

    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }

        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }

    public function datatable_json()
    {
        $model = Load::model('mod_attachment_mod');

        $columns = [];
        $columns[] = 'id';
        $columns[] = 'name';
        $columns[] = 'mod_name';
        $columns[] = 'mod_key';
        $columns[] = 'mod_value';

        $model->select($columns);
        $limit = 50;
        $offset = 0;
        $order = $columns[0];
        $sort = 'desc';

        if (Req::is_Post('order')) {
            $orderPayload = Req::Post_Safe('order');

            if (isset($orderPayload[0]['column']) && ($orderPayload[0]['column'] > 0) && isset($columns[$orderPayload[0]['column'] - 1])) {
                $order = $columns[$orderPayload[0]['column'] - 1];
            }

            if (isset($orderPayload[0]['dir']) && in_array($orderPayload[0]['dir'], ['asc', 'desc'])) {
                $sort = $orderPayload[0]['dir'];
            }
        }

        $model->order($order, $sort);

        $wheres = [];

        if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
            foreach (Req::Post_Safe('arama') as $key => $value) {
                if (in_array($key, $columns) && $value != '') {
                    $wheres[] = $key . " LIKE '%" . Req::Clear($value) . "%'";
                }
            }
        }

        if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
            foreach (Req::Post_Safe('arama_radio') as $key => $value) {
                if (in_array($key, $columns) && $value != '') {
                    $wheres[] = $key . "='" . Req::Clear($value) . "'";
                }
            }
        }

        if (Req::is_Post('arama_created_at') && count(Req::Post_Safe('arama_created_at')) > 0) {
            foreach (Req::Post_Safe('arama_created_at') as $key => $value) {
                if (!in_array($key, $columns) || $value == '') {
                    continue;
                }

                if (isset($value['from']) && isset($value['to']) && $this->_valid_date($value['from']) && $this->_valid_date($value['to'])) {
                    $wheres[] = $key . " BETWEEN '" . $value['from'] . "' AND '" . $value['to'] . "'";
                } elseif (isset($value['from']) && $this->_valid_date($value['from'])) {
                    $wheres[] = $key . " > '" . $value['from'] . "'";
                } elseif (isset($value['to']) && $this->_valid_date($value['to'])) {
                    $wheres[] = $key . " < '" . $value['to'] . "'";
                }
            }
        }

        if (Roll::hasPermission('mod_attachment_mod', 'moderate_edit') !== true && Roll::hasPermission('mod_attachment_mod', 'moderate_delete') !== true) {
            $wheres[] = "user_id='" . $this->session->getir('id') . "'";
        }

        if (count($wheres) > 0) {
            $model->addWhere("(" . implode(") AND (", $wheres) . ")");
        }

        if (Req::is_Post('length') && intval(Req::Post_Safe('length'))) {
            $limit = intval(Req::Post_Safe('length'));
        }

        if (Req::is_Post('start') && intval(Req::Post_Safe('start'))) {
            $offset = intval(Req::Post_Safe('start'));
        }

        $filteredCount = $model->getCount();
        $model->limit($limit, $offset);
        $model->gR();

        $data = [];

        if ($filteredCount > 0) {
            foreach ($model->toArray() as $content) {
                $actions = '';

                if (Roll::hasPermission('mod_attachment_mod', 'edit')) {
                    $actions .= '<button onclick="mod_attachment_mod_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_attachment_mod']['button']['edit'] . ' </button>';
                }

                if (Roll::hasPermission('mod_attachment_mod', 'delete')) {
                    $actions .= '<button onclick="mod_attachment_mod_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_attachment_mod']['button']['delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_attachment_mod', 'gallery')) {
                    $actions .= '<button onclick="mod_attachment_mod_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_attachment_mod']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_attachment_mod', 'file')) {
                    $actions .= '<button onclick="mod_attachment_mod_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_attachment_mod']['button']['file'] . ' </button>';
                }

                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    $content['mod_name'],
                    $content['mod_key'],
                    $content['mod_value'],
                    $actions,
                ];
            }
        }

        $countModel = new mod_attachment_mod_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $filteredCount,
            'recordsTotal' => $countModel->getCount(),
        ]);
    }

    private function _oke_message($message)
    {
        $this->tema->veri('oke', true);
        $this->tema->veri('mesaj', $message);
    }

    private function _error_message($message)
    {
        $this->tema->veri('error', true);
        $this->tema->veri('errors', [$message]);
    }

    private function _error($message)
    {
        $this->tema->veri('error', true);
        $this->tema->veri('mesaj', $message);
    }
}
