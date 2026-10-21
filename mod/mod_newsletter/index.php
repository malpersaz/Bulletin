<?php
/**
 * Amed Framework (AKM Bulletin CMS)
 * Newsletter Module Controller
 * mod/mod_newsletter/index.php
 */

class mod_newsletter_mod extends BaseModuleController
{
    public function __construct()
    {
        parent::__construct('mod_newsletter');
        $this->ensureInfrastructure();
    }

    /**
     * Self-healing infrastructure check (Tables, Mods, Roles, and Admin Permissions)
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
                $sql = "CREATE TABLE IF NOT EXISTS `mod_newsletter` (
                  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                  `email` VARCHAR(191) NOT NULL UNIQUE,
                  `lang` VARCHAR(10) NOT NULL DEFAULT 'ku',
                  `source` VARCHAR(50) NOT NULL DEFAULT 'offline_launch',
                  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                  `ip_address` VARCHAR(45) NULL,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                );";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS `mod_newsletter` (
                  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  `email` VARCHAR(191) NOT NULL UNIQUE,
                  `lang` VARCHAR(10) NOT NULL DEFAULT 'ku',
                  `source` VARCHAR(50) NOT NULL DEFAULT 'offline_launch',
                  `status` ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
                  `ip_address` VARCHAR(45) NULL,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  INDEX `idx_email` (`email`),
                  INDEX `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            }
            $pdo->exec($sql);

            // Ensure module entry in 'mods' table
            $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
            $stmt->execute(['mod_newsletter']);
            $modRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$modRow) {
                $nowStr = date('Y-m-d H:i:s');
                try {
                    $insertMod = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`, `installed_at`, `updated_at`) VALUES ('mod_newsletter', 'ready', 'y', ?, ?)");
                    $insertMod->execute([$nowStr, $nowStr]);
                    $modId = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    $insertMod2 = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`) VALUES ('mod_newsletter', 'ready', 'y')");
                    $insertMod2->execute();
                    $modId = (int)$pdo->lastInsertId();
                }
            } else {
                $modId = (int)$modRow['mod_id'];
            }

            if ($modId > 0) {
                $roles = ['list', 'delete'];
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
            'email|bold',
            'lang|badge',
            'source|badge',
            'created_at|datetime',
            'status|badge'
        ];
    }

    /**
     * Public AJAX endpoint for subscription (Offline page & Footer Newsletter form)
     */
    public function ajax_subscribe()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $email = trim((string)Req::Request('email', ''));
        $rawLang = Req::Request('lang', isset($this->site['lang']) ? $this->site['lang'] : 'ku');
        $langCode = trim((string)($rawLang !== null && $rawLang !== '' ? $rawLang : (isset($this->site['lang']) ? $this->site['lang'] : 'ku')));
        if (empty($langCode)) {
            $langCode = isset($this->site['lang']) ? $this->site['lang'] : 'ku';
        }
        $source = trim((string)Req::Request('source', 'offline_launch'));
        if (empty($source)) {
            $source = 'offline_launch';
        }

        // Load language dictionary for responses
        $langData = isset($this->lang['mod_newsletter']) ? $this->lang['mod_newsletter'] : [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalidMsg = isset($langData['invalid_email']) 
                ? $langData['invalid_email'] 
                : 'Ji kerema xwe e-maileke derbasdar binivîsin.';
            echo json_encode([
                'success' => false,
                'message' => $invalidMsg
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo = $this->db->get_connection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT `id`, `status` FROM `mod_newsletter` WHERE `email` = ? LIMIT 1");
            $stmt->execute([$email]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing record with active status, current language and source
                $updateStmt = $pdo->prepare("UPDATE `mod_newsletter` SET `status` = 'active', `lang` = ?, `source` = ? WHERE `id` = ?");
                $updateStmt->execute([$langCode, $source, $existing['id']]);
                
                $alreadyMsg = isset($langData['already_subscribed']) 
                    ? $langData['already_subscribed'] 
                    : 'Navnîşana e-maila we jixwe tomarkirî ye.';
                echo json_encode([
                    'success' => true,
                    'already' => true,
                    'message' => $alreadyMsg
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Insert new subscriber record
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $nowStr = date('Y-m-d H:i:s');
            $insertStmt = $pdo->prepare("INSERT INTO `mod_newsletter` (`email`, `lang`, `source`, `status`, `ip_address`, `created_at`, `updated_at`) VALUES (?, ?, ?, 'active', ?, ?, ?)");
            $insertStmt->execute([$email, $langCode, $source, $ip, $nowStr, $nowStr]);

            $successMsg = isset($langData['subscribe_success']) 
                ? $langData['subscribe_success'] 
                : 'Spas! Navnîşana e-maila we bi serkeftî hat tomarkirin.';
            echo json_encode([
                'success' => true,
                'already' => false,
                'message' => $successMsg
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Throwable $e) {
            $errorMsg = isset($langData['system_error']) 
                ? $langData['system_error'] 
                : 'Di dema tomarkirinê de tiştek şaş çû.';
            echo json_encode([
                'success' => false,
                'message' => $errorMsg,
                'error_detail' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Admin AJAX Endpoint: Fetch subscriber list & summary statistics
     */
    public function ajax_get_subscribers()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->user->hasPermission('mod_newsletter', 'list', $this->getRoleErrorMessage('list'), 1) === false) {
            echo json_encode([
                'status' => 'error',
                'message' => isset($this->lang['mod_newsletter']['role_errors']['list']) ? $this->lang['mod_newsletter']['role_errors']['list'] : 'Permission denied'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo = $this->db->get_connection();
            $search = trim(Req::Request('search', ''));
            $status = trim(Req::Request('status', 'all'));
            $page = max(1, (int)Req::Request('page', 1));
            $limit = max(1, min(100, (int)Req::Request('limit', 50)));
            $offset = ($page - 1) * $limit;

            // Fetch overall statistics
            $totalStmt = $pdo->query("SELECT COUNT(*) FROM `mod_newsletter`");
            $totalCount = (int)$totalStmt->fetchColumn();

            $activeStmt = $pdo->query("SELECT COUNT(*) FROM `mod_newsletter` WHERE `status` = 'active'");
            $activeCount = (int)$activeStmt->fetchColumn();

            $unsubStmt = $pdo->query("SELECT COUNT(*) FROM `mod_newsletter` WHERE `status` = 'unsubscribed'");
            $unsubCount = (int)$unsubStmt->fetchColumn();

            // Build query for subscriber list
            $whereClause = [];
            $params = [];

            if (!empty($search)) {
                $whereClause[] = "`email` LIKE ?";
                $params[] = '%' . $search . '%';
            }

            if ($status === 'active' || $status === 'unsubscribed') {
                $whereClause[] = "`status` = ?";
                $params[] = $status;
            }

            $sqlWhere = $whereClause ? ' WHERE ' . implode(' AND ', $whereClause) : '';
            
            // Count filtered rows
            $filteredStmt = $pdo->prepare("SELECT COUNT(*) FROM `mod_newsletter`" . $sqlWhere);
            $filteredStmt->execute($params);
            $filteredCount = (int)$filteredStmt->fetchColumn();

            // Fetch subscribers page
            $listSql = "SELECT `id`, `email`, `lang`, `source`, `status`, `created_at` FROM `mod_newsletter`" . $sqlWhere . " ORDER BY `id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            $listStmt = $pdo->prepare($listSql);
            $listStmt->execute($params);
            $subscribers = $listStmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'stats' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'unsubscribed' => $unsubCount,
                    'filtered' => $filteredCount
                ],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($filteredCount / $limit),
                'subscribers' => $subscribers
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Admin AJAX Endpoint: Direct Add Subscriber
     */
    public function ajax_add_subscriber_admin()
    {
        header('Content-Type: application/json; charset=utf-8');

        $email = trim(Req::Request('email', ''));
        $langCode = trim(Req::Request('lang', isset($this->site['lang']) ? $this->site['lang'] : 'ku'));
        $source = 'admin_panel';

        $langData = isset($this->lang['mod_newsletter']) ? $this->lang['mod_newsletter'] : [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalidMsg = isset($langData['invalid_email']) ? $langData['invalid_email'] : 'Invalid email address.';
            echo json_encode(['status' => 'error', 'message' => $invalidMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo = $this->db->get_connection();
            $stmt = $pdo->prepare("SELECT `id`, `status` FROM `mod_newsletter` WHERE `email` = ? LIMIT 1");
            $stmt->execute([$email]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['status'] === 'unsubscribed') {
                    $update = $pdo->prepare("UPDATE `mod_newsletter` SET `status` = 'active', `lang` = ? WHERE `id` = ?");
                    $update->execute([$langCode, $existing['id']]);
                }
                $alreadyMsg = isset($langData['already_subscribed']) ? $langData['already_subscribed'] : 'Email is already registered.';
                echo json_encode(['status' => 'success', 'already' => true, 'message' => $alreadyMsg], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $nowStr = date('Y-m-d H:i:s');
            $insert = $pdo->prepare("INSERT INTO `mod_newsletter` (`email`, `lang`, `source`, `status`, `ip_address`, `created_at`, `updated_at`) VALUES (?, ?, ?, 'active', ?, ?, ?)");
            $insert->execute([$email, $langCode, $source, $ip, $nowStr, $nowStr]);

            $successMsg = isset($langData['message']['success']['add']) ? $langData['message']['success']['add'] : 'Subscriber added successfully.';
            echo json_encode(['status' => 'success', 'message' => $successMsg], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Admin AJAX Endpoint: Delete Subscriber
     */
    public function ajax_delete_subscriber()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->user->hasPermission('mod_newsletter', 'delete', $this->getRoleErrorMessage('delete'), 1) === false) {
            echo json_encode([
                'status' => 'error',
                'message' => isset($this->lang['mod_newsletter']['role_errors']['delete']) ? $this->lang['mod_newsletter']['role_errors']['delete'] : 'Permission denied'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)Req::Request('id', 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo = $this->db->get_connection();
            $stmt = $pdo->prepare("DELETE FROM `mod_newsletter` WHERE `id` = ?");
            $stmt->execute([$id]);

            $deleteMsg = isset($this->lang['mod_newsletter']['message']['success']['delete']) 
                ? $this->lang['mod_newsletter']['message']['success']['delete'] 
                : 'Subscriber deleted successfully.';

            echo json_encode(['status' => 'success', 'message' => $deleteMsg], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Admin AJAX Endpoint: Export Active Subscribers to CSV
     */
    public function ajax_export_csv()
    {
        if ($this->user->hasPermission('mod_newsletter', 'list', $this->getRoleErrorMessage('list'), 1) === false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Permission denied'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo = $this->db->get_connection();
            $stmt = $pdo->query("SELECT `id`, `email`, `lang`, `source`, `status`, `created_at` FROM `mod_newsletter` ORDER BY `id` DESC");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $filename = 'newsletter_subscribers_' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'E-Mail', 'Language', 'Source', 'Status', 'Subscribed At'], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['email'],
                    $row['lang'],
                    $row['source'],
                    $row['status'],
                    $row['created_at']
                ], ',', '"', '');
            }

            fclose($output);
            exit;

        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

