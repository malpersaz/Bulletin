<?php

class user_model extends ORM
{
    public $session;
    public $lang;
    public $theme;
    public $site;
    /**
     * @var mixed[]
     */
    public $errors;
    public $error;
    public $_table = "mod_user";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $group_id;
    public $status;
    public $name;
    public $last_name;
    public $phone;
    public $updated_at;
    public $created_at;
    public $username;
    public $password;
    public $email;
    public $sid;
    public $ip;
    public $image;
    public $city;
    public $blog_name;
    public $group;


    public function __construct()
    {
        parent::__construct();
        // Load session library
        $this->session = Load::lib('Session');
        // Load language file
        $this->lang = Load::lang('mod_user');
        // Theme
        $this->theme = Load::lib('AdminTemplate');
        // Site
        $this->site = Registry::get('site_config');
    }

    // Login
    public function login($username, $password)
    {
        $usermodel = Load::model('mod_user');
        $usermodel->select(['id', 'username', 'password', 'group_id', 'status', 'email', 'name', 'last_name', 'image', 'sid'])->where_username($username)->limit(1);
        $usermodel->getResultOne(TRUE, TRUE);
        if ($usermodel->getSize() === 0) {
            $usermodel = Load::model('mod_user');
            $usermodel->select(['id', 'username', 'password', 'group_id', 'status', 'email', 'name', 'last_name', 'image', 'sid'])->where_email($username)->limit(1);
            $usermodel->getResultOne(TRUE, TRUE);
        }
        // If user exists
        if ($usermodel->getSize() !== 0) {
            // Sync data
            $usermodel->sync();
            if (!$this->_password_matches($password, $usermodel->password)) {
                $this->_log_failed_login($username);
                return False;
            }

            $this->_upgrade_password_hash($usermodel, $password);
            // If user status is 'y' (yes/active)
            if ($usermodel->status === 'y') {
                // If user IP protection is enabled (only specific IP can login)
                if ($usermodel->ip != '' && $usermodel->ip != $_SERVER['REMOTE_ADDR']) {
                    $this->errors[] = $this->lang['mod_user']['error']['ip_protection_error'];
                    return False;
                }
                $settings = Load::model('mod_user.mod_user_settings');

                $settings->where_k('check_user_login')->limit(1);
                $settings->getResultOne(TRUE, TRUE)->sync();
                // If user has a SID
                if ($settings->v === '2' && $usermodel->sid != '') {
                    $ses = Load::model('mod_user.sessions');
                    $ses->where_id($usermodel->sid)->limit(1);
                    $ses->getResultOne(TRUE, TRUE);
                    // Check if Session ID matches the one in sessions table
                    if ($ses->getSize() != 0 && $usermodel->sid != $this->session->get('sid')) {
                        $this->errors[] = $this->lang['mod_user']['error']['multi_login_error'];
                        return False;
                    }
                }
                // Create sessions
                $this->session->set('username', $usermodel->username);
                $this->session->set('password', $usermodel->password);
                $this->session->set('group_id', $usermodel->group_id);
                $this->session->set('image', $usermodel->image);
                $this->session->set('id', $usermodel->id);
                $this->session->set('email', $usermodel->email);
                $this->session->set('full_name', Text::title($usermodel->name . " " . $usermodel->last_name));

                // Load user roles
                $this->_loadPermissions($this->session->get('group_id'));
                // Set unread messages as cookies
                $this->_load_unread_pm_size();
                // Log successful login
                $this->_login_log($username);
                // Unset object
                unset($usermodel);

                return TRUE;
            }
            // User status is not 'y', return error
            $this->errors[] = $this->lang['mod_user']['error']['account_not_active'];
            return False;
        }
        $this->_log_failed_login($username);
        return False;
    }

    private function _password_matches($plain_password, $stored_password)
    {
        if (!is_string($plain_password) || $plain_password === '') {
            return False;
        }

        $stored_password = is_string($stored_password) ? $stored_password : '';
        if ($stored_password === '') {
            return False;
        }

        if (function_exists('password_get_info') && function_exists('password_verify')) {
            $info = password_get_info($stored_password);
            if (!empty($info['algo'])) {
                return password_verify($plain_password, $stored_password);
            }
        }

        $legacy_hash = md5($plain_password);
        if (function_exists('hash_equals')) {
            return hash_equals((string)$stored_password, $legacy_hash);
        }

        return (string)$stored_password === $legacy_hash;
    }

    private function _upgrade_password_hash($usermodel, $plain_password)
    {
        if (!function_exists('password_hash') || !function_exists('password_get_info')) {
            return;
        }

        $stored_password = is_string($usermodel->password) ? $usermodel->password : '';
        if ($stored_password === '') {
            return;
        }

        $info = password_get_info($stored_password);
        if (!empty($info['algo'])) {
            return;
        }

        $usermodel->password = password_hash($plain_password, PASSWORD_DEFAULT);
        $usermodel->update('id');
    }

    // Set unread PMs as cookies
    private function _load_unread_pm_size()
    {
        $pm = Load::model('mod_user_pm.inbox');
        $size = $pm->select('id')->where_reader_id_and_status($this->session->get('id'), 'n')->getCount();
        if ($size !== 0) {
            if (!headers_sent()) {
                setcookie("okunmamis", $size, ['expires' => time() + 20]);
            }
            $this->session->set('okunmamis', $size);
        }
        unset($size);
        unset($pm);
    }

    // Log failed login
    private function _log_failed_login($username = NULL)
    {
        $log = Load::model('mod_user.log');
        $log->username = $username;
        $log->password = '';
        $log->ip = $_SERVER['REMOTE_ADDR'];
        $log->error = 'y';
        $log->save();
        $this->errors[] = $this->lang['mod_user']['error']['invalid_credentials'];
        unset($log);
    }

    // Log successful login
    private function _login_log($username)
    {
        $log = Load::model('mod_user.log');
        $log->username = $username;
        $log->password = '**********';
        $log->ip = $_SERVER['REMOTE_ADDR'];
        $log->error = 'n';
        $log->created_at = date('Y-m-d H:i:s');
        $log->save();
        unset($log);
    }

    // Load permissions and roles
    public function _loadPermissions($group_id)
    {
        $db = Load::lib('Database');
        $sql = "
			SELECT `mod_user_role`.role_name, `mods`.mod_name
			FROM `mod_user_perm`
			JOIN `mod_user_role` ON `mod_user_role`.role_id = `mod_user_perm`.role_id AND `mod_user_role`.mod_id = `mod_user_perm`.mod_id
			JOIN `mods` ON `mods`.mod_id = `mod_user_perm`.mod_id
			WHERE `mod_user_perm`.group_id = '" . (int)$group_id . "'";

        // Fetch array without building
        $db->fetch_array($sql);
        // Add roles to session
        $this->_setPermissions($db->toArray());
        unset($db);
    }

    private function _setPermissions($roles)
    {
        $val = [];
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (!empty($role['role_name']) && !empty($role['mod_name'])) {
                    $val[$role['mod_name']][$role['role_name']] = True;
                }
            }
        }
        if ($this->session->has('permissions')) {
            $this->session->remove('permissions');
        }
        if (is_array($val)) {
            $this->session->set('permissions', $val);
        }
    }

    // Check if user is logged in
    public function checklogin()
    {
        if ($this->session->has('id') === False) {
            return False;
        }

        $userId = (int) $this->session->get('id');
        $sessionSid = (string) $this->session->get('sid');

        if ($userId <= 0) {
            $this->session->clear();
            return False;
        }

        $db = Load::lib('Database');
        $db->fetch_array("SELECT id, sid, status, image FROM `mod_user` WHERE `id` = " . $userId . " AND `status` = 'y' LIMIT 1");
        if ($db->getSize() === 0) {
            $this->session->clear();
            return False;
        }

        $user = $db->getResult()->toArray()[0];
        if (!empty($sessionSid) && !empty($user['sid']) && $user['sid'] !== $sessionSid) {
            $this->session->clear();
            return False;
        }

        if (isset($user['image']) && $user['image'] !== $this->session->get('image')) {
            $this->session->set('image', $user['image']);
        }

        return TRUE;
    }

    // Logout
    public function logout($sid)
    {
        // If user is logged in
        if ($this->session->has('id')) {
            $user = Load::model('mod_user');
            $user->getsid($sid)->select('id,sid')->limit(1)->getResultOne(TRUE, TRUE);
            if ($user->getSize() != 0) {
                // Sync data
                $user->sync();
                // Clear SID
                $user->sid = '';
                $user->update();
            }
            unset($user);
        }
        return $this->session->clear();
    }

    // Check for moderator permission
    public function isAuthorized($mod, $id, $message, $mode = null)
    {
        if ($id != $this->session->get('id')) {
            if (Role::hasPermission($mod, 'moderate') === False) {
                $this->error = $message;
                $this->_handleErrorMessage($mode);
                return False;
            }
            return True;
        }
        return True;
    }

    /**
     * @param string $mod
     * @param string $roll
     * @param int $id
     * @param string $message
     * @param mixed $mode
     */
    public function checkPermission($mod, $roll, $id, $message, $mode = null)
    {
        if ($id != $this->session->get('id')) {
            if (Role::hasPermission($mod, $roll) === False) {
                $this->error = $message;
                $this->_handleErrorMessage($mode);
                return False;
            }
            return True;
        }
        return True;
    }

    // Check for permission
    public function hasPermission($mod, $permission, $message, $mode = null)
    {
        if (Role::hasPermission($mod, $permission) === False) {
            if ($this->session->has('group_id')) {
                $this->_loadPermissions($this->session->get('group_id'));
                if (Role::hasPermission($mod, $permission) === True) {
                    return True;
                }
            }
            $this->error = $message;
            $this->_handleErrorMessage($mode);
            return False;
        }
        return True;
    }

    // Return error message
    private function _handleErrorMessage($mode = null)
    {
        if ($mode === 0 || $mode === '0' || $mode === false || $mode === 'silent') {
            return;
        }

        if ($this->checklogin() === False) {
            $isAjax = Req::is_Ajax()
                || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            $targetUrl = (!empty($this->site['saddress']) ? $this->site['saddress'] : $this->site['address']) . '?login=modal&session_expired=1';

            if ($isAjax) {
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'code' => 'session_expired',
                    'message' => 'Session expired or unauthorized',
                    'redirect' => $targetUrl
                ]);
                exit();
            }

            Helper::redirect($targetUrl, 0);
            return;
        }

        if ($mode == '1') {
            $this->_renderUnauthorized();
        } elseif ($mode == '2') {
            $this->_renderFlashError();
        } elseif ($mode == '3') {
            echo json_encode(['error' => true, 'message' => $this->error]);
        } else {
            echo "<h4>" . $this->lang['mod_user']['error']['permission_error'] . "</h4><br/>" . $this->error;
        }
    }

    // Render management/unauthorized.twig and show error
    private function _renderUnauthorized()
    {
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user']['error']['permission_error'];
        $this->theme->set('site', $this->site);
        $this->theme->set('error_message', $this->error);
        $this->theme->tpl('management/unauthorized');
    }

    private function _renderFlashError()
    {
        // Flash error handler
        if (isset($this->theme)) {
            $this->theme->set('error_message', $this->error);
        }
    }
}
