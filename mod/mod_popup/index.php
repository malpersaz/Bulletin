<?php

/* 
 * AKM Software.
 * @2015
 * Popup Module Page
 * mod/mod_popup/index.php
 */

class mod_popup_mod extends indexMain
{
    public $mod_popup_model;

    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->theme = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Registry::get('site_config');
        // Load Language File
        $this->lang = Load::lang('mod_popup');
        // Send Language File to Template
        $this->theme->lang($this->lang);
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    /**
     * Add Page
     */
    public function add()
    {
        // Check Add Permission
        if ($this->user->hasPermission('mod_popup', 'add', $this->lang['mod_popup']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_popup']['add'];
        $this->theme->tpl('mod_popup/add');
        $this->theme->set('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->theme->set('lang_lang', $this->db->getResult()->toArray());
    }

    /**
     * Edit Page
     */
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_popup', 'edit', $this->lang['mod_popup']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_popup_model = Load::model('mod_popup');
        $id = Req::Url_Clear($id);
        if ($this->mod_popup_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_popup']['errors']['record']);
            Run::import('management', '_index');
            $this->theme->tpl('mod_popup/edit');
            return;
        }

        $d = $this->mod_popup_model->getResultOne()->toArray();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_popup', 'moderate_edit', $d['user_id'], $this->lang['mod_popup']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_popup']['edit'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->tpl('mod_popup/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->theme->set('lang_lang', $this->db->getResult()->toArray());
    }

    /**
     * List Page
     */
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_popup', 'list', $this->lang['mod_popup']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_popup']['list'];
        $this->theme->tpl('mod_popup/list');
        $this->theme->set('site', $this->site);
    }

    public function image_remove_image()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_popup', 'edit', $this->lang['mod_popup']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_popup_model = Load::model('mod_popup');
        $this->mod_popup_model->where_id($id)->gRo();
        if ($this->mod_popup_model->getSize() === 0) {
            echo $this->lang['mod_popup']['errors']['record'];
            return FALSE;
        }


        $this->mod_popup_model->sync();
        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_popup', 'moderate_edit', $this->mod_popup_model->user_id, $this->lang['mod_popup']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_popup_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_popup';
            $image->url = $this->mod_popup_model->image;
            $option = [
                '/' =>
                [
                    'width' => 900,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 100,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option)) {
                $this->mod_popup_model->image = '';
                $this->mod_popup_model->update('id');
                echo 'Success';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Success';
        }
    }

    /**
     * Add Function
     */
    private function _add()
    {
        $this->val = Load::validator('mod_popup.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_popup_model = Load::model('mod_popup');

            $this->mod_popup_model->lang = Req::Post_Safe('lang');
            $this->mod_popup_model->name = Req::Post_Safe('name');


            $this->mod_popup_model->publish_at = Req::Post_Safe('publish_at');

            $this->mod_popup_model->expired_at = Req::Post_Safe('expired_at');

            $this->mod_popup_model->status = 'n';

            $this->mod_popup_model->content = Req::Post_Safe('content');

            $this->mod_popup_model->duration = Req::Post_Safe('duration');


            $this->mod_popup_model->video = Req::Post_Safe('video');
            $this->mod_popup_model->link = Req::Post_Safe('link');


            $this->mod_popup_model->user_id = $this->session->get('id');

            // If Image exists
            if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_popup';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 900,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_popup_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_popup_model->image)) {
                    unset($this->mod_popup_model->image);
                }
            }

            if ($this->mod_popup_model->save(TRUE)) {
                $this->_success_message($this->lang['mod_popup']['message']['success']['add']);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_popup']['message']['success']['add']);
            }
        }
    }

    /**
     * Edit Function
     */
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_popup.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_popup_model = Load::model('mod_popup');

            $this->mod_popup_model->id = $id;
            $this->mod_popup_model->lang = Req::Post_Safe('lang');
            $this->mod_popup_model->name = Req::Post_Safe('name');


            $this->mod_popup_model->publish_at = Req::Post_Safe('publish_at');

            $this->mod_popup_model->expired_at = Req::Post_Safe('expired_at');

            $this->mod_popup_model->content = Req::Post_Safe('content');

            $this->mod_popup_model->duration = Req::Post_Safe('duration');


            $this->mod_popup_model->video = Req::Post_Safe('video');
            $this->mod_popup_model->link = Req::Post_Safe('link');


            if (isset($this->mod_popup_model->user_id)) {
                unset($this->mod_popup_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_popup';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 900,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_popup_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_popup_model->image)) {
                    unset($this->mod_popup_model->image);
                }
            }

            $this->mod_popup_model->id = $id;
            if ($this->mod_popup_model->update('id')) {
                $this->_success_message($this->lang['mod_popup']['message']['success']['edit']);
            } else {
                // If Image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_popup']['message']['error']['edit']);
            }
        }
    }



    public function delete()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_popup',  'delete', $this->lang['mod_popup']['role_errors']['delete']) === False) {
            return False;
        }
        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post_Safe('delete'))) {
                echo 'Success';
            } else {
                echo 'ERROR';
            }
        } else {
            exit($this->lang['mod_popup']['errors']['record']);
        }
    }

    public function _delete($ids)
    {
        $this->mod_popup_model = Load::model('mod_popup');
        foreach ($ids as $s) {
            $this->mod_popup_model->flush();
            $this->mod_popup_model->where_id($s)->gRo();
            if ($this->mod_popup_model->getSize() === 0) {
                exit($this->lang['mod_popup']['errors']['record']);
            }
            $this->mod_popup_model->sync();
            // Check Moderate Permission if content belongs to another user
            if ($this->user->checkPermission('mod_popup', 'moderate_delete', $this->mod_popup_model->user_id, $this->lang['mod_popup']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_popup") === TRUE) {
                // Delete Gallery if exists for content
                Run::import('mod_gallery', '_remove', ['mod_popup', $this->mod_popup_model->id]);
            }
            // Check if module exists and Attachment is being added
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_popup") === TRUE) {
                // Delete Attachment if exists for content
                Run::import('mod_attachment', '_remove', ['mod_popup', $this->mod_popup_model->id]);
            }

            if ($this->mod_popup_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_popup';
                $image->name = 'image';
                $image->url = $this->mod_popup_model->image;
                $option = [
                    '/' =>
                    [
                        'width' => 900,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_delete_files($option) !== TRUE) {
                    echo $image->error;
                }
            }


            $this->mod_popup_model->delete();
        }
        return TRUE;
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


        $i = Load::model('mod_popup');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'lang';
        $colomns[] = 'publish_at';
        $colomns[] = 'expired_at';
        $colomns[] = 'status';
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
            (Role::hasPermission('mod_popup', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_popup', 'moderate_delete') !== TRUE)
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
        //print_r($i);exit;
        $data = [];


        $status_list = [


            'y' => ['success', $this->lang['mod_popup']['admin']['radio']['publish_yes']],


            'n' => ['danger', $this->lang['mod_popup']['admin']['radio']['publish_no']],


        ];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Role::hasPermission('mod_popup', 'edit')) {
                    $last .= '<button onclick="mod_popup_table_ajax.akm.processpublish(\'' . $content['id'] . '\');return false;" class="btn default btn-xs green">
    <i class="fa fa-arrow-circle-up"></i> ' . $this->lang['mod_popup']['grid']['publish']['key'] . ' </button>';

                    $last .= '<button onclick="mod_popup_table_ajax.akm.processunpublish(\'' . $content['id'] . '\');return false;" class="btn default btn-xs blue">
    <i class="fa fa-arrow-circle-down"></i> ' . $this->lang['mod_popup']['grid']['unpublish']['key'] . ' </button>';

                    $last .= '<button onclick="mod_popup_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_popup']['button']['edit'] . ' </button>';
                }
                if (Role::hasPermission('mod_popup',  'delete')) {
                    $last .= '<button onclick="mod_popup_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_popup']['button']['delete'] . ' </button>';
                }

                if (Role::hasPermission('mod_popup', 'gallery')) {
                    $last .= '<button onclick="mod_popup_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_popup']['button']['gallery'] . ' </button>';
                }

                if (Role::hasPermission('mod_popup', 'file')) {
                    $last .= '<button onclick="mod_popup_table_ajax.akm.processFile(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_popup']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['iaddress'] . 'mod_popup/thumb/' . $content['image'] . '">',
                    $content['lang'],
                    $content['publish_at'],
                    $content['expired_at'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last
                ];
            }
        }
        $ii = new mod_popup_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Is it done?'
        ]);
    }


    /**
     * Publish Page
     */
    public function publish()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_popup', 'edit', $this->lang['mod_popup']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_publish($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _publish($query = NULL)
    {
        // Load mod_popup Model
        $this->mod_popup_model = Load::model('mod_popup');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_popup_model->where_id($s)->gRo();
                if ($this->mod_popup_model->getSize() === 0) {
                    return FALSE;
                }
                $this->mod_popup_model->sync();
                $up = new mod_popup_model();
                $up->status = 'n';
                $up->where_lang_and_not_id($this->mod_popup_model->lang, $s)->update();
                $this->mod_popup_model->id = $s;
                $this->mod_popup_model->status = 'y';
                if ($this->mod_popup_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Unpublish Page
     */
    public function unpublish()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_popup', 'edit', $this->lang['mod_popup']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unpublish($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _unpublish($query = NULL)
    {
        // Load mod_popup Model
        $this->mod_popup_model = Load::model('mod_popup');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_popup_model->id = $s;
                $this->mod_popup_model->status = 'n';
                if ($this->mod_popup_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Success Message
     */
    private function _success_message($message)
    {
        $this->theme->set('Success', True);
        $this->theme->set('message', $message);
    }

    /**
     * Error Message
     */
    private function _error_message($message)
    {
        $this->theme->set('error', True);
        $this->theme->set('errors', [$message]);
    }

    /**
     * Error Message
     */
    private function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }
}
