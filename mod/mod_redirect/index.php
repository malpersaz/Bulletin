<?php

class mod_redirect_mod extends indexMain
{
    public $mod_redirect_model;

    public function __construct()
    {
        parent::__construct();
        // Load templates lib
        $this->theme = Load::lib('AdminTemplate');
        // Load database lib
        $this->db = Load::lib('Database');
        // Load language files
        $this->lang = Load::lang('mod_redirect');
        $this->theme->lang($this->lang);
        // Session lib
        $this->session = Load::lib('Session');
        // User Model for permission checks
        $this->user = Load::model('mod_user.user');
        $this->ensureInfrastructure();
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
        $this->theme->tpl('mod_redirect/list');
        $this->theme->set('site', $this->site);
        $this->theme->set('redirects', $redirects);
    }

    public function ajax_get_redirects()
    {
        if ($this->user->hasPermission('mod_redirect', 'list', $this->lang['mod_redirect']['role_errors']['list'], 1) === FALSE) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['role_errors']['list']]);
            return;
        }

        $search = trim((string) Req::Get('search'));
        $model = Load::model('mod_redirect');
        
        $allRedirects = $model->order('id', 'desc')->gR()->toArray();
        if (!is_array($allRedirects)) {
            $allRedirects = [];
        }

        $totalCount = count($allRedirects);
        $permanentCount = 0;
        $temporaryCount = 0;

        $filtered = [];
        foreach ($allRedirects as $item) {
            if ((int)($item['status_code'] ?? 301) === 302) {
                $temporaryCount++;
            } else {
                $permanentCount++;
            }

            if ($search !== '') {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $oldLower = mb_strtolower($item['old_url'] ?? '', 'UTF-8');
                $newLower = mb_strtolower($item['new_url'] ?? '', 'UTF-8');
                if (strpos($oldLower, $searchLower) === false && strpos($newLower, $searchLower) === false) {
                    continue;
                }
            }
            $filtered[] = $item;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'redirects' => $filtered,
            'stats' => [
                'total' => $totalCount,
                'permanent' => $permanentCount,
                'temporary' => $temporaryCount
            ]
        ]);
    }

    public function ajax_add_redirect()
    {
        if ($this->user->hasPermission('mod_redirect', 'add', $this->lang['mod_redirect']['role_errors']['add'], 1) === FALSE) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['role_errors']['add']]);
            return;
        }

        $oldUrl = trim((string) Req::Post_Safe('old_url'));
        $newUrl = trim((string) Req::Post_Safe('new_url'));
        $statusCode = (int) Req::Post_Safe('status_code');
        if ($statusCode !== 302) {
            $statusCode = 301;
        }

        if (empty($oldUrl) || empty($newUrl)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['message']['error']['add']]);
            return;
        }

        $model = Load::model('mod_redirect');
        $model->old_url = $oldUrl;
        $model->new_url = $newUrl;
        $model->status_code = $statusCode;

        if ($model->save(TRUE)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_redirect']['message']['success']['add']]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['message']['error']['add']]);
        }
    }

    public function ajax_edit_redirect()
    {
        if ($this->user->hasPermission('mod_redirect', 'edit', $this->lang['mod_redirect']['role_errors']['edit'], 1) === FALSE) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['role_errors']['edit']]);
            return;
        }

        $id = (int) Req::Post_Safe('id');
        $oldUrl = trim((string) Req::Post_Safe('old_url'));
        $newUrl = trim((string) Req::Post_Safe('new_url'));
        $statusCode = (int) Req::Post_Safe('status_code');
        if ($statusCode !== 302) {
            $statusCode = 301;
        }

        if ($id <= 0 || empty($oldUrl) || empty($newUrl)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['errors']['record']]);
            return;
        }

        $model = Load::model('mod_redirect');
        $model->id = $id;
        $model->old_url = $oldUrl;
        $model->new_url = $newUrl;
        $model->status_code = $statusCode;

        if ($model->update('id')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_redirect']['message']['success']['edit']]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_redirect']['message']['error']['edit']]);
        }
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
        $this->theme->tpl('mod_redirect/add');
        $this->theme->set('site', $this->site);
    }

    private function _add()
    {
        $this->val = Load::validator('mod_redirect.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $model = Load::model('mod_redirect');
            $model->old_url = trim((string) Req::Post_Safe('old_url'));
            $model->new_url = trim((string) Req::Post_Safe('new_url'));
            $model->status_code = (int) Req::Post_Safe('status_code');

            if ($model->save(TRUE)) {
                $this->session->set_flash('success_message', $this->lang['mod_redirect']['message']['success']['add']);
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
            $this->theme->tpl('mod_redirect/edit');
            return;
        }

        $redirect = $model->getResultOne()->toArray();

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_redirect']['edit'];
        $this->theme->tpl('mod_redirect/edit');
        $this->theme->set('site', $this->site);
        $this->theme->set('redirect', $redirect);
    }

    private function _edit($id)
    {
        $this->val = Load::validator('mod_redirect.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $model = Load::model('mod_redirect');
            $model->id = $id;
            $model->old_url = trim((string) Req::Post_Safe('old_url'));
            $model->new_url = trim((string) Req::Post_Safe('new_url'));
            $model->status_code = (int) Req::Post_Safe('status_code');

            if ($model->update('id')) {
                $this->session->set_flash('success_message', $this->lang['mod_redirect']['message']['success']['edit']);
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
        if ($id === 0) {
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
                    'message' => $this->lang['mod_redirect']['message']['success']['delete']
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

    private function _error_message($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('errors', [$message]);
    }

    private function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }

    private function ensureInfrastructure()
    {
        try {
            $pdo = $this->db->get_connection();
            if (!$pdo) {
                return;
            }

            $driver = strtolower($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
            if ($driver === 'sqlite') {
                $sql = "CREATE TABLE IF NOT EXISTS `mod_redirect` (
                  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                  `old_url` VARCHAR(250) NOT NULL UNIQUE,
                  `new_url` VARCHAR(250) NOT NULL,
                  `status_code` INTEGER NOT NULL DEFAULT 301,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                );";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS `mod_redirect` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                  `old_url` VARCHAR(250) NOT NULL UNIQUE,
                  `new_url` VARCHAR(250) NOT NULL,
                  `status_code` INT(11) NOT NULL DEFAULT 301,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            }
            $pdo->exec($sql);

            // Ensure module entry in 'mods' table
            $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
            $stmt->execute(['mod_redirect']);
            $modRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$modRow) {
                $nowStr = date('Y-m-d H:i:s');
                try {
                    $insertMod = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`, `installed_at`, `updated_at`) VALUES ('mod_redirect', 'ready', 'y', ?, ?)");
                    $insertMod->execute([$nowStr, $nowStr]);
                    $modId = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    $insertMod2 = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`) VALUES ('mod_redirect', 'ready', 'y')");
                    $insertMod2->execute();
                    $modId = (int)$pdo->lastInsertId();
                }
            } else {
                $modId = (int)$modRow['mod_id'];
            }

            if ($modId <= 0) {
                $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
                $stmt->execute(['mod_redirect']);
                $modIdRow = $stmt->fetch(\PDO::FETCH_ASSOC);
                $modId = (int)($modIdRow['mod_id'] ?? 0);
            }

            if ($modId > 0) {
                $roles = ['list', 'add', 'edit', 'delete'];
                foreach ($roles as $roleName) {
                    $roleStmt = $pdo->prepare("SELECT `role_id` FROM `mod_user_role` WHERE `mod_id` = ? AND `role_name` = ? LIMIT 1");
                    $roleStmt->execute([$modId, $roleName]);
                    $roleRow = $roleStmt->fetch(\PDO::FETCH_ASSOC);

                    if (!$roleRow) {
                        $insertRole = $pdo->prepare("INSERT INTO `mod_user_role` (`mod_id`, `role_name`) VALUES (?, ?)");
                        $insertRole->execute([$modId, $roleName]);
                        $roleId = (int)$pdo->lastInsertId();
                    } else {
                        $roleId = (int)$roleRow['role_id'];
                    }

                    if ($roleId <= 0) {
                        $roleStmt = $pdo->prepare("SELECT `role_id` FROM `mod_user_role` WHERE `mod_id` = ? AND `role_name` = ? LIMIT 1");
                        $roleStmt->execute([$modId, $roleName]);
                        $roleRow = $roleStmt->fetch(\PDO::FETCH_ASSOC);
                        $roleId = (int)($roleRow['role_id'] ?? 0);
                    }

                    if ($roleId > 0) {
                        $permStmt = $pdo->prepare("SELECT `id` FROM `mod_user_perm` WHERE `mod_id` = ? AND `role_id` = ? AND `group_id` = 1 LIMIT 1");
                        $permStmt->execute([$modId, $roleId]);
                        if (!$permStmt->fetch()) {
                            $insertPerm = $pdo->prepare("INSERT INTO `mod_user_perm` (`group_id`, `role_id`, `mod_id`) VALUES (1, ?, ?)");
                            $insertPerm->execute([$roleId, $modId]);
                        }
                    }
                }
            }

            // Sync session permissions safely
            if (isset($_SESSION['id']) || isset($_SESSION['group_id'])) {
                $_SESSION['permissions']['mod_redirect'] = [
                    'list' => true,
                    'add' => true,
                    'edit' => true,
                    'delete' => true
                ];
            }
        } catch (\Throwable $e) {
            // Silently handle infrastructure error
        }
    }
}
