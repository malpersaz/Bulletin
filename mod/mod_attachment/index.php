<?php

/**
 * mod_attachment controller
 * Modernized version extending BaseModuleController
 * 
 * Amed Framework
 */
class mod_attachment_mod extends BaseModuleController
{
    public $module_lang;
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
        if (!is_array($record) || $record === []) {
            return false;
        }

        $candidates = array_filter(array_unique([
            $configured_value,
            'name',
            'title',
            'headline',
            'subject'
        ]));

        foreach ($candidates as $field) {
            if (!is_string($field) || $field === '' || $field === 'id' || substr($field, -3) === '_id') {
                continue;
            }
            if (array_key_exists($field, $record)) {
                $value = trim((string) $record[$field]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        if (isset($record['id'])) {
            return '#' . (string) $record['id'];
        }

        return false;
    }

    private function _has_attachment_permission($mod = null, $action = 'file')
    {
        $targetMod = ($mod && $mod !== 'mod_attachment') ? $mod : 'mod_attachment';
        $this->module_lang = Load::lang($targetMod);
        $roleError = $this->module_lang[$targetMod]['role_errors']['file']
            ?? ($this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied');

        return (
            Role::hasPermission($targetMod, 'file') ||
            Role::hasPermission($targetMod, 'edit') ||
            Role::hasPermission('mod_attachment', 'list') ||
            Role::hasPermission('mod_attachment', 'edit') ||
            Role::hasPermission('mod_attachment', $action) ||
            $this->user->hasPermission($targetMod, 'file', $roleError, 1)
        );
    }

    private function _send_json($payload)
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    public function _check_mod($mod, $vid)
    {
        $isJsonOrAjax = Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1) || Req::Post('response_format') === 'json';

        if ($mod === null || $mod === '') {
            $msg = $this->lang['mod_attachment']['errors']['missing_module_name'];
            if ($isJsonOrAjax) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        if ($vid === null || $vid === '') {
            $msg = $this->lang['mod_attachment']['errors']['missing_record_id'];
            if ($isJsonOrAjax) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
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
            $msg = $this->lang['mod_attachment']['errors']['invalid_module'];
            if ($isJsonOrAjax) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $this->mod->getResultOne();
        $this->mod->sync();

        $this->module_lang = Load::lang($mod);
        $roleError = $this->module_lang[$mod]['role_errors']['file'] ?? ($this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied');

        if (!$this->_has_attachment_permission($mod, 'file')) {
            if ($isJsonOrAjax) {
                $this->_send_json(['status' => 'error', 'message' => $roleError]);
                return false;
            }
            exit($roleError);
        }

        $record = [];
        $record_title = '';

        if (intval($vid) > 0 && $mod !== 'mod_attachment') {
            $this->model = Load::model($mod);
            if (!$this->model || $this->model->exists_id($vid) === false) {
                $msg = $this->lang['mod_attachment']['errors']['invalid_record'];
                if ($isJsonOrAjax) {
                    $this->_send_json(['status' => 'error', 'message' => $msg]);
                    return false;
                }
                Helper::redirect($this->site['address'] . $mod . '/list', 1);
                print($msg);
                return false;
            }

            $this->model->getResultOne();
            $this->model->sync();
            $record = $this->model->toArray();
            $record_title = $this->_resolve_record_title($record, $this->mod->mod_value);
            if ($record_title === false) {
                $record_title = '#' . (string) $vid;
            }
        } else {
            $record_title = $this->lang['mod_attachment']['admin']['file_management'] ?? 'Files';
        }

        $this->theme->set('key', $this->mod->mod_key);
        $this->theme->set('value', $this->mod->mod_value);
        $this->theme->set('mod_name', $mod);

        $this->mod_attachment_model = Load::model('mod_attachment');
        $this->mod_attachment_model->where_vid_and_mod_name($vid, $mod)->gR();

        $this->theme->set('d', $record);
        $this->theme->set('record_title', $record_title);
        $this->theme->set('vid', $vid);
        $this->theme->set('active_file_id', isset($record['file_id']) ? intval($record['file_id']) : 0);

        return true;
    }

    public function list($mod = null, $vid = null)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);
        $isAjax = Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1);
        $this->theme->set('is_ajax', $isAjax);

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
            $this->theme->set('mod_name', 'mod_attachment');
            $this->theme->set('vid', 0);
            $this->theme->set('active_file_id', 0);

            $this->db->fetch_array("SELECT id, filename, url, ext, original_name, type, created_at, mod_name, vid, user_id FROM `mod_attachment` ORDER BY id DESC");
            $rows = $this->db->getResult()->toArray();
            if (!empty($rows)) {
                $this->theme->set('files', $rows);
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

        $safeMod = addslashes((string) $mod);
        $safeVid = intval($vid);
        $this->db->fetch_array("SELECT id, filename, url, ext, original_name, type, created_at, mod_name, vid, user_id FROM `mod_attachment` ORDER BY CASE WHEN mod_name = '{$safeMod}' AND vid = {$safeVid} THEN 0 ELSE 1 END, id DESC");
        $rows = $this->db->getResult()->toArray();
        if (!empty($rows)) {
            $this->theme->set('files', $rows);
        }

        $this->theme->tpl('mod_attachment/list');
        return true;
    }

    public function edit($id = null)
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name') ?: 'mod_attachment';
        $wantJson = Req::Post('response_format') === 'json';

        if (!$this->_has_attachment_permission($mod, 'edit')) {
            $msg = $this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied';
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $this->r = Load::model('mod_attachment');
        $id = Req::Post_Safe('id');
        $this->r->where_id($id)->gRo();

        if ($this->r->getSize() === 0) {
            $msg = $this->lang['mod_attachment']['errors']['invalid_file'];
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $newName = trim((string) Req::Post_Safe('name'));
        if ($newName === '') {
            $msg = $this->lang['mod_attachment']['errors']['invalid_file'];
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $this->r->id = $id;
        $this->r->filename = $newName;
        $this->r->update('id');

        if ($wantJson) {
            $this->_send_json([
                'status' => 'success',
                'id'     => intval($id),
                'name'   => $newName
            ]);
            return true;
        }

        echo 'OK';
        return true;
    }

    public function delete()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name') ?: 'mod_attachment';
        $wantJson = Req::Post('response_format') === 'json';

        if (!$this->_has_attachment_permission($mod, 'delete')) {
            $msg = $this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied';
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $file_model = Load::model('mod_attachment');
        $id = intval(Req::Post_Safe('id'));
        $file_model->where_id($id)->limit(1)->gRo();

        if ($file_model->getSize() === 0) {
            $msg = $this->lang['mod_attachment']['errors']['invalid_file'];
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $file_model->sync();
        $targetMod = !empty($file_model->mod_name) ? $file_model->mod_name : $mod;

        $file = Load::mod('file');
        $file->mod = $targetMod;
        $file->url = $file_model->url;

        $deletedFile = $file->_delete();
        $deletedRow = $file_model->delete();

        if ($deletedRow) {
            foreach (['mod_publication', 'mod_news', 'mod_page'] as $refTable) {
                try {
                    $this->db->query("UPDATE `{$refTable}` SET `file_id` = 0 WHERE `file_id` = {$id}");
                } catch (Exception $e) {
                    // Ignore if table or column does not exist
                }
            }

            if ($wantJson) {
                $this->_send_json([
                    'status' => 'success',
                    'id'     => $id
                ]);
                return;
            }

            echo 'OK';
            return;
        }

        $errMsg = $file->error ?: 'Delete error';
        if ($wantJson) {
            $this->_send_json(['status' => 'error', 'message' => $errMsg]);
            return;
        }
        echo $errMsg;
    }

    public function add()
    {
        $vid = Req::is_Post('vid') ? Req::Post_Safe('vid') : Req::Get_Safe('vid');
        $mod = Req::is_Post('mod_name') ? Req::Post_Safe('mod_name') : Req::Get_Safe('mod_name');
        $isAjax = Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1);
        $this->theme->set('is_ajax', $isAjax);

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
        $input_name = Req::Post_Safe('isim') ?: 'file';
        $wantJson = Req::Post('response_format') === 'json';

        if (Req::is_Files($input_name) === false) {
            $msg = $this->lang['mod_attachment']['errors']['upload_field_missing'];
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $vid = Req::Post_Safe('vid');
        if ($vid === '' || $vid === null) {
            $vid = 0;
        }
        $mod = basename(Req::Post_Safe('mod_name') ?: 'mod_publication');

        if (intval($vid) > 0 && $mod !== 'mod_attachment') {
            if ($this->_check_mod($mod, $vid) === false) {
                return false;
            }
        } elseif (!$this->_has_attachment_permission($mod, 'file')) {
            $msg = $this->lang['mod_attachment']['errors']['access_denied'] ?? 'Permission denied';
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $valid_ext = ['txt', 'xls', 'xlsx', 'csv', 'jpg', 'png', 'gif', 'bmp', 'jpeg', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'rar', 'zip', '7z', 'pdf', 'doc', 'docx', 'ods', 'ppt', 'pptx', 'odt', 'epub', 'RAR', 'ZIP', '7Z', 'PDF', 'DOC', 'DOCX', 'ODS', 'PPT', 'PPTX', 'ODT', 'EPUB'];

        if (!Req::is_Files($input_name) || Req::Files_Name($input_name) == '') {
            $msg = $this->lang['mod_attachment']['errors']['uploaded_file_missing'];
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $file = Load::mod('file');
        $file->mod = $mod;
        $file->name = $input_name;

        if (!$file->_upload(['valid_ext' => $valid_ext])) {
            if ($wantJson) {
                $this->_send_json(['status' => 'error', 'message' => $file->error]);
                return false;
            }
            echo $file->error;
            return false;
        }

        $result = $file->result;
        $file_model = Load::model('mod_attachment');
        $cleanName = Req::Clear($result['original_name']);
        $file_model->filename = $cleanName;
        $file_model->url = $result['url'];
        $file_model->ext = $result['ext'];
        $file_model->original_name = $result['original_name'];
        $file_model->type = $result['type'];
        $file_model->mod_name = $mod;
        $file_model->vid = intval($vid);
        $file_model->user_id = $this->session->get('id');

        if ($file_model->save()) {
            $newId = intval($file_model->insert_id());
            if ($newId <= 0) {
                $newId = intval($this->db->insert_id());
            }
            if ($newId <= 0) {
                $this->db->fetch_array("SELECT id FROM `mod_attachment` ORDER BY id DESC LIMIT 1");
                $lastRow = $this->db->getResult()->toArray();
                if (!empty($lastRow[0]['id'])) {
                    $newId = intval($lastRow[0]['id']);
                }
            }

            if ($wantJson) {
                $this->_send_json([
                    'status'        => 'success',
                    'id'            => $newId,
                    'filename'      => $cleanName,
                    'original_name' => $result['original_name'],
                    'name'          => $cleanName ?: $result['original_name'],
                    'url'           => $result['url'],
                    'ext'           => $result['ext'],
                    'mod_name'      => $mod,
                    'vid'           => intval($vid)
                ]);
                return true;
            }

            echo $result['original_name'];
            return true;
        }

        if ($wantJson) {
            $this->_send_json(['status' => 'error', 'message' => 'Database save failed']);
            return false;
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
