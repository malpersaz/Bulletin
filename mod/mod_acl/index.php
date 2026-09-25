<?php

/* AKM Software.
 * @2010
 * mod_acl Module Page
 * mod/mod_acl/index.php
 */

class mod_acl_mod extends indexMain
{
    public $mod_acl_model;

    function __construct()
    {
        parent::__construct();
        // Load Admin Template Library
        $this->theme = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Load Language File
        $this->lang = Load::lang('mod_acl');
        // Send Language to Template
        $this->theme->lang($this->lang);
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    private function _ugr()
    {
        $this->db->fetch_array("SELECT name, group_id,parent_id FROM `mod_user_group` ORDER BY `order` ASC");
        $s = array_merge([['name' => $this->lang['mod_acl']['guest'], 'group_id' => '0', 'parent_id' => '0']], $this->db->toArray());
        //$s=Helper::nested($s,'parent_id','group_id');
        $this->theme->set('_group_list', $s);
    }


    // Add Page
    public function add()
    {
        // Does user have Add Permission?
        if ($this->user->hasPermission('mod_acl', 'add', $this->lang['mod_acl']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_acl']['add'];
        $this->theme->tpl('mod_acl/add');
        $this->theme->set('site', $this->site);
        $this->_ugr();
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Does user have Edit Permission?
        if ($this->user->hasPermission('mod_acl', 'edit', $this->lang['mod_acl']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_acl_model = Load::model('mod_acl');
        $id = Req::Url_Clear($id);
        if ($this->mod_acl_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_acl']['errors']['record']);
            Run::import('management', '_index');
            $this->theme->tpl('mod_acl/edit');
            return;
        }


        $d = $this->mod_acl_model->getResultOne()->toArray();
        $d['acl'] = unserialize($d['acl']);
        // Does user have permission to edit content added by someone else?
        if ($this->user->checkPermission('mod_acl', 'moderate_edit', $d['user_id'], $this->lang['mod_acl']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_acl']['edit'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->tpl('mod_acl/edit');
        $this->_ugr();
    }

    // List Page
    public function list()
    {
        // Does user have List Permission?
        if ($this->user->hasPermission('mod_acl', 'list', $this->lang['mod_acl']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_acl']['list'];
        $this->theme->tpl('mod_acl/list');
        $this->theme->set('site', $this->site);
    }

    // Add Handler Function
    private function _add()
    {
        $this->val = Load::validator('mod_acl.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_acl_model = Load::model('mod_acl');

            $this->mod_acl_model->name = Req::Post_Safe('name');

            $this->mod_acl_model->acl = serialize(Req::Post_Safe('group_id'));

            $this->mod_acl_model->user_id = $this->session->get('id');

            // Save record
            if ($this->mod_acl_model->save(TRUE)) {
                $this->_success_message($this->lang['mod_acl']['message']['success']['add']);
            } else {
                $this->_error_message($this->lang['mod_acl']['message']['error']['add']);
            }
        }
    }

    // Edit Handler Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_acl.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_acl_model = Load::model('mod_acl');

            $this->mod_acl_model->id = $id;
            $this->mod_acl_model->name = Req::Post_Safe('name');


            $this->mod_acl_model->acl = serialize(Req::Post_Safe('group_id'));
            if (isset($this->mod_acl_model->user_id)) {
                unset($this->mod_acl_model->user_id);
            }


            // Update record
            $this->mod_acl_model->id = $id;
            if ($this->mod_acl_model->update('id')) {
                // Clear Cache
                if (Cache::exists("permission-acl-" . $id)) {
                    Cache::delete("permission-acl-" . $id);
                }
                $this->_success_message($this->lang['mod_acl']['message']['success']['edit']);
            } else {
                $this->_error_message($this->lang['mod_acl']['message']['error']['edit']);
            }
        }
    }

    public function delete()
    {
        // Does user have Delete Permission?
        if ($this->user->hasPermission('mod_acl', 'delete', $this->lang['mod_acl']['role_errors']['delete']) === False) {
            return False;
        }
        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post_Safe('delete'))) {
                echo 'Success';
            } else {
                echo 'Error';
            }
        } else {
            exit($this->lang['mod_acl']['errors']['record']);
        }
    }

    public function _delete($ids)
    {
        $this->mod_acl_model = Load::model('mod_acl');
        foreach ($ids as $s) {
            $this->mod_acl_model->flush();
            $this->mod_acl_model->where_id($s)->gRo();
            if ($this->mod_acl_model->getSize() === 0) {
                exit($this->lang['mod_acl']['errors']['record']);
            }
            $this->mod_acl_model->sync();
            // Does user have permission to delete content added by someone else?
            if ($this->user->checkPermission('mod_acl', 'moderate_delete', $this->mod_acl_model->user_id, $this->lang['mod_acl']['role_errors']['moderate_delete']) === False) {
                return False;
            }


            $this->mod_acl_model->delete();
        }
        return TRUE;
    }



    public function datatable_json()
    {


        $i = Load::model('mod_acl');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $i->select($colomns);
        $limit = 50;
        $offset = 0;
        $order = $colomns[0];;
        $sort = 'desc';

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (
                isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])

            ) {
                $order = $colomns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);



        $wheres = [];

        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }
        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    if (isset($v['from']) && isset($v['to']) && $this->_valid_date($v['from']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " BETWEEN '" . $v['from'] . "' AND '" . $v['to'] . "'";
                    } elseif (
                        isset($v['from']) && $this->_valid_date($v['from'])
                    ) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (
                        isset($v['to']) && $this->_valid_date($v['to'])
                    ) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }
        if (
            (Role::hasPermission('mod_acl', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_acl', 'moderate_delete') !== TRUE)
        ) {
            $wheres[] = "user_id='" . $this->session->get('id') . "'";
        }

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $i->addWhere($wheres);
        }


        if (Req::is_Post('length') && intval(Req::Post_Safe('length'))) {
            $limit = intval(Req::Post_Safe('length'));
        }


        if (Req::is_Post('start') && intval(Req::Post_Safe('start'))) {
            $offset = intval(Req::Post_Safe('start'));
        }
        $getCount = $i->getCount();
        $i->limit($limit, $offset);


        $i->gR();
        $data = [];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {
                $data[] = [
                    '<input type="checkbox" name="id[]" class="table-checkbox" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    ''
                ];
            }
        }
        $ii = new mod_acl_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
        ]);
    }


    // Success Message
    private function _success_message($message)
    {
        $this->theme->set('success', True);
        $this->theme->set('message', $message);
    }

    // Error Message
    private function _error_message($message)
    {
        $this->theme->set('error', True);
        $this->theme->set('errors', [$message]);
    }

    // Exception Message
    private function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }
}
