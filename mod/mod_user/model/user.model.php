<?php

class user_model extends ORM
{
    public $session;
    public $lang;
    public $tema;
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
    public $grup;


    public function __construct()
    {
        parent::__construct();
        // Load session library
        $this->session = Load::lib('Session');
        // Load language file
        $this->lang = Load::lang('mod_user');
        // Theme
        $this->tema = Load::lib('AdminTemplate');
    }

    // Login
    public function login($username, $password)
    {
        $usermodel = Load::model('mod_user');
        $usermodel->select(['id', 'username', 'password', 'group_id', 'status', 'email', 'name', 'last_name', 'image', 'sid'])->where_username_and_password($username, md5($password))->limit(1);
        $usermodel->getResultOne(TRUE, TRUE);
        // If user exists
        if ($usermodel->getSize() !== 0) {
            // Sync data
            $usermodel->sync();
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
        $this->_hata_log($username, $password);
        return False;
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
    private function _hata_log($username = NULL, $password = NULL)
    {
        $log = Load::model('mod_user.log');
        $log->username = $username;
        $log->password = $password;
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
			SELECT r.roll_name,m.mod_name
			FROM `mod_user_perm` AS p
			JOIN `mod_user_roll` AS r
			JOIN `mods` AS m ON r.roll_id = p.roll_id
			AND m.mod_id = p.mod_id
			AND p.group_id = '" . $group_id . "'";

        // Fetch array without building
        $db->fetch_array($sql);
        // Add roles to session
        $this->_setPermissions($db->toArray());
        unset($db);
    }

    // Set roles as session
    private function _setPermissions($roll)
    {
        if (is_array($roll)) {
            $val = [];
            foreach ($roll as $rol) {
                $val[$rol['mod_name']][$rol['roll_name']] = True;
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
    public function isAuthorized($mod, $id, $mesaj, $nasil = NULl)
    {
        if ($id != $this->session->get('id')) {
            if (Roll::hasPermission($mod, 'moderate') === False) {
                $this->error = $mesaj;
                $this->hatamesaj($nasil);
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
     * @param string $mesaj
     * @param mixed $nasil
     */
    public function checkPermission($mod, $roll, $id, $mesaj, $nasil = NULl)
    {
        if ($id != $this->session->get('id')) {
            if (Roll::hasPermission($mod, $roll) === False) {
                $this->error = $mesaj;
                $this->hatamesaj($nasil);
                return False;
            }
            return True;
        }
        return True;
    }

    // Check for permission
    public function hasPermission($mod, $permission, $mesaj, $nasil = NULL)
    {
        if (Roll::hasPermission($mod, $permission) === False) {
            $this->error = $mesaj;
            $this->hatamesaj($nasil);
            return False;
        }
        return True;
    }

    // Return error message
    private function hatamesaj($nasil = NUll)
    {
        if ($nasil == '1') {
            $this->_hata();
        } elseif ($nasil == '2') {
            $this->_emesaj();
        } elseif ($nasil == '3') {
            echo json_encode(['error' => true, 'mesaj' => $this->error]);
        } else {
            echo "<h4>" . $this->lang['mod_user']['error']['permission_error'] . "</h4><br/>" . $this->error;
        }
    }

    // Render management/unauthorized.twig and show error
    private function _hata()
    {
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user']['error']['permission_error'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('hatamesaj', $this->error);
        $this->tema->tpl('management/unauthorized');
    }
}
