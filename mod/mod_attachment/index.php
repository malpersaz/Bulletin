<?php

/**
 * mod_attachment controller
 * Modernized version extending BaseModuleController
 * 
 * Amed Framework
 */
class mod_attachment_mod extends BaseModuleController
{
    public $mlang;
    public $mod_attachment_model;
    public $r;

    public function __construct()
    {
        parent::__construct('mod_attachment');

        if (Req::is_Post('sid')) {
            Registry::add('session_sid', Req::Post_Safe('sid'));
        }
    }

    public function index()
    {
        echo 'Attachment Module';
    }

    private function _resolve_record_title($record, $configured_value = null)
    {
        if (!is_array($record) || $record === [] || $configured_value === null || $configured_value === '') {
            return false;
        }

        if (!array_key_exists($configured_value, $record)) {
            return false;
        }

        $value = trim((string) $record[$configured_value]);
        if ($value === '') {
            return false;
        }

        if ($configured_value === 'id' || substr($configured_value, -3) === '_id') {
            return false;
        }

        return $value;
    }

    public function _check_mod($mod, $vid)
    {
        if ($mod === null) {
            exit($this->lang['mod_attachment']['errors']['missing_module_name']);
        }

        if ($vid === null) {
            exit($this->lang['mod_attachment']['errors']['missing_record_id']);
        }

        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name($mod) === false) {
            try {
                $rawName = str_replace('mod_', '', $mod);
                $displayName = ucfirst($rawName) . ' Files';
                $safeName = addslashes($displayName);
                $safeMod = addslashes($mod);
                $this->db->query("INSERT INTO `mod_attachment_mod` (`name`, `mod_name`, `mod_key`, `mod_value`, `user_id`) VALUES ('{$safeName}', '{$safeMod}', 'id', 'name', 1)");
                $this->mod->flush();
            } catch (Exception $e) {
                // Table might not exist yet
            }
        }

        if ($this->mod->exists_mod_name($mod) === false) {
            exit($this->lang['mod_attachment']['errors']['invalid_module']);
        }

        $this->mod->getResultOne();
        $this->mod->sync();

        $this->mlang = Load::lang($mod);
        $roleError = $this->mlang[$mod]['role_errors']['file'] ?? ($this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied');

        $hasPermission = (
            Role::hasPermission($mod, 'file') ||
            Role::hasPermission($mod, 'edit') ||
            Role::hasPermission('mod_attachment', 'list') ||
            Role::hasPermission('mod_attachment', 'edit') ||
            $this->user->hasPermission($mod, 'file', $roleError, 1)
        );

        if (!$hasPermission) {
            exit($roleError);
        }

        $this->model = Load::model($mod);
        if (!$this->model || $this->model->exists_id($vid) === false) {
            Helper::redirect($this->site['address'] . $mod . '/list', 1);
            print($this->lang['mod_attachment']['errors']['invalid_record']);
            return false;
        }

        $this->model->getResultOne();
        $this->model->sync();

        $this->theme->set('key', $this->mod->mod_key);
        $this->theme->set('value', $this->mod->mod_value);
        $this->theme->set('mod_name', $mod);

        $this->mod_attachment_model = Load::model('mod_attachment');
        $this->mod_attachment_model->where_vid_and_mod_name($vid, $mod)->gR();

        $record = $this->model->toArray();
        $record_title = $this->_resolve_record_title($record, $this->mod->mod_value);
        if ($record_title === false) {
            exit($this->lang['mod_attachment']['errors']['invalid_record_title_field']);
        }

        $this->theme->set('d', $record);
        $this->theme->set('record_title', $record_title);
        $this->theme->set('vid', $vid);

        return true;
    }

    public function list($mod = null, $vid = null)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);

        if ($mod === '') {
            $mod = null;
        }
        if ($vid === '') {
            $vid = null;
        }

        if ($mod === null && $vid === null) {
            if ($this->user->hasPermission('mod_attachment', 'list', $this->lang['mod_attachment']['role_errors']['list']) === false) {
                return false;
            }

            Run::import('management', '_index');
            $this->site['title'] = $this->lang['mod_attachment']['admin']['file_management'];
            $this->theme->set('site', $this->site);
            $this->theme->set('is_global_list', true);

            $this->mod_attachment_model = Load::model('mod_attachment');
            $this->mod_attachment_model->select('id,filename,url,ext,original_name,type,created_at,mod_name,vid,user_id')->gR();

            if ($this->mod_attachment_model->getSize() !== 0) {
                $this->theme->set('dosyalar', $this->mod_attachment_model->toArray());
            }

            $this->theme->tpl('mod_attachment/list');
            return true;
        }

        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment']['admin']['file_management'];
        $this->theme->set('site', $this->site);
        $this->theme->set('is_global_list', false);

        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $current_url = 'mod_attachment/list/' . $mod . '/' . $vid;
        if ($referer !== '' && strpos($referer, $this->site['saddress']) === 0 && strpos($referer, $current_url) === false) {
            $back_url = $referer;
        } else {
            $back_url = $this->site['saddress'] . $mod . '/list';
        }
        $this->theme->set('back_url', $back_url);

        if ($this->mod_attachment_model->getSize() !== 0) {
            $this->theme->set('dosyalar', $this->mod_attachment_model->toArray());
        }

        $this->theme->tpl('mod_attachment/list');
        return true;
    }

    public function edit($id = null)
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        $this->r = Load::model('mod_attachment');
        $id = Req::Post_Safe('id');
        $this->r->where_id($id)->gRo();

        if ($this->r->getSize() === 0) {
            exit($this->lang['mod_attachment']['errors']['invalid_file']);
        }

        $this->r->id = $id;
        $this->r->filename = Req::Post_Safe('name');
        $this->r->update('id');
        echo 'OK';
    }

    public function delete()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        $file_model = Load::model('mod_attachment');
        $id = Req::Post_Safe('id');
        $file_model->where_id_and_vid_and_mod_name($id, $vid, $mod)->limit(1)->gRo();

        if ($file_model->getSize() === 0) {
            exit($this->lang['mod_attachment']['errors']['invalid_file']);
        }

        $file_model->sync();

        $file = Load::mod('file');
        $file->mod = $mod;
        $file->url = $file_model->url;

        if ($file->_delete() && $file_model->delete()) {
            echo 'OK';
            return;
        }

        echo $file->error;
    }

    public function add()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_attachment']['admin']['upload_area'];
        $this->theme->set('site', $this->site);
        $this->theme->set('vid', $vid);
        $this->theme->tpl('mod_attachment/add');

        return true;
    }

    public function upload()
    {
        $input_name = Req::Post_Safe('isim');
        if (Req::is_Files($input_name) === false) {
            exit($this->lang['mod_attachment']['errors']['upload_field_missing']);
        }

        $vid = Req::Post_Safe('vid');
        $mod = basename(Req::Post_Safe('mod_name'));
        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        $valid_ext = ['txt', 'xls', 'jpg', 'png', 'gif', 'bmp', 'jpeg', 'JPG', 'JPEG', 'PNG', 'GIF', 'rar', 'zip', 'pdf', 'doc', 'docx', 'ods', 'ppt', 'odt', 'epub', 'RAR', 'ZIP', 'PDF', 'DOC', 'DOCX', 'ODS', 'PPT', 'ODT', 'EPUB'];

        if (!Req::is_Files($input_name) || Req::Files_Name($input_name) == '') {
            exit($this->lang['mod_attachment']['errors']['uploaded_file_missing']);
        }

        $file = Load::mod('file');
        $file->mod = $mod;
        $file->name = $input_name;

        if (!$file->_upload(['valid_ext' => $valid_ext])) {
            echo $file->error;
            return false;
        }

        $result = $file->result;
        $file_model = Load::model('mod_attachment');
        $file_model->filename = Req::Clear($result['original_name']);
        $file_model->url = $result['url'];
        $file_model->ext = $result['ext'];
        $file_model->original_name = $result['original_name'];
        $file_model->type = $result['type'];
        $file_model->mod_name = $mod;
        $file_model->vid = $vid;
        $file_model->user_id = $this->session->get('id');

        if ($file_model->save()) {
            echo $result['original_name'];
            return true;
        }

        echo 'ERROR';
        return false;
    }

    protected function _message($message)
    {
        $this->theme->set('Success', true);
        $this->theme->set('message', $message);
    }

    protected function _error_message($message)
    {
        $this->theme->set('error', true);
        $this->theme->set('message', $message);
    }

    public function _remove($mod, $vid = null)
    {
        $this->r = Load::model('mod_attachment');

        if ($vid == null) {
            $this->r->where_mod_name($mod)->gR();
        } else {
            $this->r->where_vid_and_mod_name($vid, $mod)->gR();
        }

        if ($this->r->getSize() === 0) {
            return true;
        }

        foreach ($this->r->toArray() as $file_data) {
            $file = Load::mod('file');
            $file->mod = $mod;
            $file->url = $file_data['url'];

            if ($file->_delete()) {
                continue;
            }

            echo $file->error;
            return false;
        }

        $this->r->delete();
        return true;
    }
}
