<?php
/**
 * Amed Framework
 * MCP API Key Management Controller
 * mod/mod_mcp/index.php
 */
class mod_mcp_mod extends BaseModuleController
{
    private const KEY_PREFIX = 'amcp_';

    public function __construct()
    {
        parent::__construct('mod_mcp');
        $this->ensureInfrastructure();
    }

    /**
     * Self-healing infrastructure check (Table, Module registry, Roles, Admin permissions)
     */
    private function ensureInfrastructure()
    {
        try {
            $pdo = $this->db->get_connection();
            if (!$pdo) {
                return;
            }

            $driver = strtolower($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
            if ($driver === 'sqlite') {
                $sql = "CREATE TABLE IF NOT EXISTS `mod_mcp_api_key` (
                  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                  `name` VARCHAR(100) NOT NULL,
                  `api_key_hash` VARCHAR(64) NOT NULL UNIQUE,
                  `key_prefix` VARCHAR(16) NOT NULL,
                  `group_id` INTEGER NOT NULL DEFAULT 1,
                  `user_id` INTEGER NOT NULL DEFAULT 1,
                  `status` TEXT NOT NULL DEFAULT 'y' CHECK(status IN ('y', 'n')),
                  `last_used_at` DATETIME NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NULL
                );";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS `mod_mcp_api_key` (
                  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  `name` VARCHAR(100) NOT NULL,
                  `api_key_hash` VARCHAR(64) NOT NULL UNIQUE,
                  `key_prefix` VARCHAR(16) NOT NULL,
                  `group_id` INT UNSIGNED NOT NULL DEFAULT 1,
                  `user_id` INT UNSIGNED NOT NULL DEFAULT 1,
                  `status` ENUM('y', 'n') NOT NULL DEFAULT 'y',
                  `last_used_at` DATETIME NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NULL,
                  INDEX `idx_group` (`group_id`),
                  INDEX `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            }
            $pdo->exec($sql);

            // Ensure module entry in 'mods' table
            $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
            $stmt->execute(['mod_mcp']);
            $modRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$modRow) {
                $nowStr = date('Y-m-d H:i:s');
                try {
                    $insertMod = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`, `installed_at`, `updated_at`) VALUES ('mod_mcp', 'ready', 'y', ?, ?)");
                    $insertMod->execute([$nowStr, $nowStr]);
                    $modId = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    $insertMod2 = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`) VALUES ('mod_mcp', 'ready', 'y')");
                    $insertMod2->execute();
                    $modId = (int)$pdo->lastInsertId();
                }
            } else {
                $modId = (int)$modRow['mod_id'];
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
        } catch (\Throwable $e) {
            // Silently swallow infrastructure errors
        }
    }

    /**
     * Configure Datatable columns for Admin Panel
     */
    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'key_prefix|badge',
            'status|badge',
            'last_used_at|datetime',
            'created_at|datetime'
        ];
    }

    public function index()
    {
        $this->list();
    }

    /**
     * Renders the API key management list
     */
    public function list()
    {
        if ($this->user->hasPermission('mod_mcp', 'list', $this->getRoleErrorMessage('list'), 1) === false) {
            return false;
        }

        $this->db->fetch_array(
            "SELECT k.`id`, k.`name`, k.`key_prefix`, k.`group_id`, k.`status`, k.`last_used_at`, k.`created_at`,
                    g.`name` AS group_name, u.`username` AS creator_username
             FROM `mod_mcp_api_key` k
             LEFT JOIN `mod_user_group` g ON g.`group_id` = k.`group_id`
             LEFT JOIN `mod_user` u ON u.`id` = k.`user_id`
             ORDER BY k.`id` DESC"
        );
        $keys = $this->db->getResult()->toArray();
        $this->view->set('mcp_keys', is_array($keys) ? $keys : []);

        $this->db->fetch_array("SELECT `group_id`, `name` FROM `mod_user_group` ORDER BY `group_id` ASC");
        $groups = $this->db->getResult()->toArray();
        $this->view->set('mcp_groups', is_array($groups) ? $groups : []);

        $site = Registry::get('site_config');
        $this->view->set('mcp_endpoint', rtrim(isset($site['address']) ? $site['address'] : '', '/') . '/mcp.php');

        Load::libfile('McpOAuth');
        McpOAuth::ensureTables();
        $this->db->fetch_array(
            "SELECT c.`id`, c.`client_id`, c.`client_name`, c.`redirect_uris`, c.`status`, c.`created_at`,
                    (SELECT COUNT(*) FROM `mod_mcp_oauth_token` t WHERE t.`client_id` = c.`client_id` AND t.`status` = 'y') AS active_tokens,
                    (SELECT MAX(t.`last_used_at`) FROM `mod_mcp_oauth_token` t WHERE t.`client_id` = c.`client_id`) AS last_used_at
             FROM `mod_mcp_oauth_client` c
             ORDER BY c.`id` DESC"
        );
        $clients = $this->db->getResult()->toArray();
        $this->view->set('mcp_oauth_clients', is_array($clients) ? $clients : []);
        Run::import('management', '_index');
        $this->view->tpl('admin/mod_mcp/list');
    }

    /**
     * Creates a new API key and reveals the plaintext value exactly once
     */
    public function create()
    {
        if ($this->user->hasPermission('mod_mcp', 'add', $this->getRoleErrorMessage('add'), 1) === false) {
            return false;
        }

        if (!Req::is_Post('create')) {
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $isAjax = $this->isAjaxRequest();
        $name = trim((string) Req::Post_Safe('name'));
        $groupId = (int) Req::Post_Safe('group_id');

        $errors = [];

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = $this->lang['mod_mcp']['errors']['invalid_name'];
        }

        if ($groupId <= 0 || $this->groupExists($groupId) === false) {
            $errors[] = $this->lang['mod_mcp']['errors']['invalid_group'];
        }

        if (!empty($errors)) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => implode(' ', $errors),
                    'errors' => $errors
                ]);
            }
            $this->_error_message(implode(' ', $errors));
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $plaintextKey = self::KEY_PREFIX . bin2hex(random_bytes(24));

        $model = Load::model('mod_mcp.mod_mcp_api_key');
        $model->name = $name;
        $model->api_key_hash = hash('sha256', $plaintextKey);
        $model->key_prefix = substr($plaintextKey, 0, 12);
        $model->group_id = $groupId;
        $model->user_id = (int) $this->session->get('id');
        $model->status = 'y';
        $model->created_at = date('Y-m-d H:i:s');

        if (!$model->save(true)) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => $this->lang['mod_mcp']['message']['error']['add']
                ]);
            }
            $this->_error_message($this->lang['mod_mcp']['message']['error']['add']);
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        if ($isAjax) {
            $this->jsonResponse([
                'status' => 'success',
                'message' => $this->lang['mod_mcp']['message']['success']['add'],
                'api_key' => $plaintextKey,
                'key_id' => (int) $model->id,
                'key_prefix' => $model->key_prefix
            ]);
            return true;
        }

        // Non-AJAX fallback: flash the plaintext key for the immediate list render
        $_SESSION['flash']['mcp_new_key'] = $plaintextKey;
        $_SESSION['flash']['mcp_new_key_id'] = (int) $model->id;
        $this->_success_message($this->lang['mod_mcp']['message']['success']['add']);
        $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
        return true;
    }

    /**
     * Deletes (revokes) an API key permanently
     */
    public function delete()
    {
        if ($this->user->hasPermission('mod_mcp', 'delete', $this->getRoleErrorMessage('delete'), 1) === false) {
            return false;
        }

        if (!Req::is_Post('delete')) {
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $isAjax = $this->isAjaxRequest();
        $id = (int) Req::Post_Safe('id');

        $model = Load::model('mod_mcp.mod_mcp_api_key');
        $model->where_id($id)->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => $this->lang['mod_mcp']['errors']['record']
                ]);
                return false;
            }
            $this->_error_message($this->lang['mod_mcp']['errors']['record']);
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $model->sync();
        $deleted = $model->delete();

        if ($isAjax) {
            $this->jsonResponse([
                'status' => $deleted ? 'success' : 'error',
                'message' => $deleted ? $this->lang['mod_mcp']['message']['success']['delete'] : $this->lang['mod_mcp']['message']['error']['delete']
            ]);
            return (bool) $deleted;
        }

        if ($deleted) {
            $this->_success_message($this->lang['mod_mcp']['message']['success']['delete']);
        } else {
            $this->_error_message($this->lang['mod_mcp']['message']['error']['delete']);
        }
        $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
        return (bool) $deleted;
    }

    /**
     * Toggles an API key between active and revoked states
     */
    public function toggle()
    {
        if ($this->user->hasPermission('mod_mcp', 'edit', $this->getRoleErrorMessage('edit'), 1) === false) {
            return false;
        }

        if (!Req::is_Post('toggle')) {
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $isAjax = $this->isAjaxRequest();
        $id = (int) Req::Post_Safe('id');

        $model = Load::model('mod_mcp.mod_mcp_api_key');
        $model->where_id($id)->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => $this->lang['mod_mcp']['errors']['record']
                ]);
                return false;
            }
            $this->_error_message($this->lang['mod_mcp']['errors']['record']);
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $model->sync();
        $model->status = ($model->status === 'y') ? 'n' : 'y';
        $model->updated_at = date('Y-m-d H:i:s');
        $updated = $model->update('id');

        if ($isAjax) {
            $this->jsonResponse([
                'status' => $updated ? 'success' : 'error',
                'message' => $updated ? $this->lang['mod_mcp']['message']['success']['edit'] : $this->lang['mod_mcp']['message']['error']['edit'],
                'new_status' => $model->status
            ]);
            return (bool) $updated;
        }

        if ($updated) {
            $this->_success_message($this->lang['mod_mcp']['message']['success']['edit']);
        } else {
            $this->_error_message($this->lang['mod_mcp']['message']['error']['edit']);
        }
        $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
        return (bool) $updated;
    }
    /**
     * Toggles an OAuth client's active state; deactivation revokes its live tokens
     */
    public function oauthToggle()
    {
        if ($this->user->hasPermission('mod_mcp', 'edit', $this->getRoleErrorMessage('edit'), 1) === false) {
            return false;
        }

        if (!Req::is_Post('oauth_toggle')) {
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $isAjax = $this->isAjaxRequest();
        $id = (int) Req::Post_Safe('id');

        $model = Load::model('mod_mcp.mod_mcp_oauth_client');
        $model->where_id($id)->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => $this->lang['mod_mcp']['errors']['record']
                ]);
                return false;
            }
            $this->_error_message($this->lang['mod_mcp']['errors']['record']);
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $model->sync();
        $model->status = ($model->status === 'y') ? 'n' : 'y';
        $model->updated_at = date('Y-m-d H:i:s');
        $updated = $model->update('id');

        if ($model->status === 'n') {
            // Deactivating a client must immediately cut its live tokens
            $this->db->query("UPDATE `mod_mcp_oauth_token` SET `status` = 'n' WHERE `client_id` = '" . addslashes((string) $model->client_id) . "'");
        }

        if ($isAjax) {
            $this->jsonResponse([
                'status' => $updated ? 'success' : 'error',
                'message' => $updated ? $this->lang['mod_mcp']['message']['success']['edit'] : $this->lang['mod_mcp']['message']['error']['edit'],
                'new_status' => $model->status
            ]);
            return (bool) $updated;
        }

        if ($updated) {
            $this->_success_message($this->lang['mod_mcp']['message']['success']['edit']);
        } else {
            $this->_error_message($this->lang['mod_mcp']['message']['error']['edit']);
        }
        $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
        return (bool) $updated;
    }

    /**
     * Deletes an OAuth client together with its codes and tokens
     */
    public function oauthDelete()
    {
        if ($this->user->hasPermission('mod_mcp', 'delete', $this->getRoleErrorMessage('delete'), 1) === false) {
            return false;
        }

        if (!Req::is_Post('oauth_delete')) {
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $isAjax = $this->isAjaxRequest();
        $id = (int) Req::Post_Safe('id');

        $model = Load::model('mod_mcp.mod_mcp_oauth_client');
        $model->where_id($id)->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => $this->lang['mod_mcp']['errors']['record']
                ]);
                return false;
            }
            $this->_error_message($this->lang['mod_mcp']['errors']['record']);
            $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
            return false;
        }

        $model->sync();
        $clientId = addslashes((string) $model->client_id);
        $this->db->query("DELETE FROM `mod_mcp_oauth_token` WHERE `client_id` = '{$clientId}'");
        $this->db->query("DELETE FROM `mod_mcp_oauth_code` WHERE `client_id` = '{$clientId}'");
        $deleted = $model->delete();

        if ($isAjax) {
            $this->jsonResponse([
                'status' => $deleted ? 'success' : 'error',
                'message' => $deleted ? $this->lang['mod_mcp']['message']['success']['delete'] : $this->lang['mod_mcp']['message']['error']['delete']
            ]);
            return (bool) $deleted;
        }

        if ($deleted) {
            $this->_success_message($this->lang['mod_mcp']['message']['success']['delete']);
        } else {
            $this->_error_message($this->lang['mod_mcp']['message']['error']['delete']);
        }
        $this->_redirect_now($this->site['saddress'] . 'mod_mcp/list');
        return (bool) $deleted;
    }

    private function groupExists($groupId)
    {
        $this->db->fetch_array("SELECT `group_id` FROM `mod_user_group` WHERE `group_id` = " . (int) $groupId . " LIMIT 1");
        return $this->db->getSize() !== 0;
    }
}
