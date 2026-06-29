<?php

/* Akm Software
 * @2010
 * Membership Module
 * mod/mod_user/index.php
 */

class mod_user_mod extends indexMain
{
    public $apptemplate;
    public $captcha;
    public $password_reset_model;
    public $password_reset;
    public $activation;
    public $m;
    public $u;
    public $settings;
    public $_activation;
    public $_group_id;
    public $mod_user;
    public $grid;
    public $i;
    public $b;
    function __construct()
    {
        parent::__construct();
        // Call the Theme Library
        $this->tema = Load::lib('AdminTemplate');

        $this->apptemplate = Load::lib('TwigTemplate');
        // Load Database Library
        //$this->db = Load::lib('Database');
        // Get site settings
        $this->site = Kayit::getir('site_config');
        // Call Language File
        $this->lang = Load::lang('mod_user');
        // Send Language File to Theme
        $this->tema->lang($this->lang);
        $this->apptemplate->lang($this->lang);
        // User Model
        $this->user = Load::model('mod_user.user');
        // Session Library
        $this->session = Load::lib('Session');
    }

    // Activates Account
    public function status()
    {
        // Is there Edit Permission?
        if ($this->user->hasPermission('mod_user', 'edit', $this->lang['mod_user']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $ids = Req::Post_Safe('query');
        return $this->_status($ids);
    }

    // Activates Account
    private function _status($ids = NULL)
    {
        // Load mod_user Model
        $this->model = Load::model('mod_user');
        if (is_array($ids)) {
            foreach ($ids as $s) {
                $this->model->Getid($s)->getResultOne(TRUE, TRUE)->sync();
                if ($this->model->status != 'y') {
                    $this->model->id = $s;
                    $status = $this->model->status;
                    $this->model->status = 'y';
                    if ($this->model->update('id') && $status == 'a') {
                        $this->_management_akfilestime_email_at();
                    }
                }
            }
            echo 'Oke';
        }
    }



    // Deactivates Account
    public function deactivate()
    {
        // Is there Edit Permission?
        if ($this->user->hasPermission('mod_user', 'edit', $this->lang['mod_user']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $ids = Req::Post_Safe('query');
        return $this->_deactivate($ids);
    }

    // Deactivates Account
    private function _deactivate($ids = NULL)
    {
        // Load mod_user Model
        $this->model = Load::model('mod_user');
        if (is_array($ids)) {
            foreach ($ids as $s) {
                $this->model->Getid($s)->getResultOne(TRUE, TRUE)->sync();
                if ($this->model->status == 'y') {
                    $this->model->id = $s;
                    $this->model->status = 'n';
                    $this->model->update('id');
                }
            }
            echo 'Oke';
        }
    }


    // Function that sends mail when account is activated
    private function _management_akfilestime_email_at()
    {
        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->konu = $this->lang['mod_user']['activation']['membership_activation'];
        $this->apptemplate->veri('name', $this->model->name);
        $this->apptemplate->veri('last_name', $this->model->last_name);
        $this->apptemplate->veri('site', $this->site);
        $email->mesaj = $this->apptemplate->fetch('mod_user/email_admin_activation');
        if ($email->gonder()) {
            return True;
        }
        return False;
    }

    // Login Page
    public function login()
    {
        ob_start();

        Run::import('index', '_index');
        $d['title'] = $this->lang['mod_user']['admin_panel'];
        $this->apptemplate->veri('d', $d);
        $this->_load_login_errors();

        if ($this->_check_login()) {
            return;
        }

        // Redirect to homepage with modal if accessed directly (non-AJAX GET)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$this->_is_ajax_request()) {
            Helper::yonlendir($this->site['saddress'] . "?login=modal", 0);
            return;
        }

        $ref = isset($_SERVER['HTTP_REFERER']) ? Req::Clear($_SERVER['HTTP_REFERER']) : '';
        $r = $ref != '' ? $ref : $this->site['saddress'] . 'mod_user/panel';

        if (isset($_POST['login']) || Req::is_Post('login')) {
            $username = Req::Post_Safe('username');
            $password = Req::Post_Safe('password');
            $is_ajax = $this->_is_ajax_request();

            $this->val = Load::validator('mod_user.login');
            if ($this->val->check()) {
                $this->_log_login_debug("Login attempt for: {$username} (AJAX: " . ($is_ajax ? 'Y' : 'N') . ")");
                $errors = array_merge($this->val->errors, $this->user->errors ?? []);

                if ($is_ajax) {
                    $this->_respond_with_json([
                        'success' => false,
                        'message' => $this->lang['mod_user']['ajax']['validation_error'],
                        'errors' => $errors
                    ], 400);
                }

                $this->_bind_login_form($username, $password, $errors);
                $this->apptemplate->tpl('mod_user/login');
                return;
            }

            if ($this->user->login($username, $password)) {
                $this->_appmesaj($this->lang['mod_user']['login_success_wait']);

                if ($is_ajax) {
                    $this->_respond_with_json([
                        'success' => true,
                        'message' => $this->lang['mod_user']['ajax']['login_success'],
                        'redirect' => $r
                    ]);
                }

                Helper::yonlen($r, 3);
                return;
            }

            $errors = $this->user->errors ?? [$this->lang['mod_user']['ajax']['invalid_credentials']];
            if ($is_ajax) {
                $this->_respond_with_json([
                    'success' => false,
                    'message' => $this->lang['mod_user']['ajax']['login_error'],
                    'errors' => $errors
                ], 401);
            }

            $this->_bind_login_form($username, $password, $errors);
            $this->apptemplate->tpl('mod_user/login');
            return;
        }

        $this->apptemplate->tpl('mod_user/login');
    }

    private function _load_login_errors()
    {
        $session = Load::lib('Session');
        if ($session->has('login_errors')) {
            $this->apptemplate->veri('errors', $session->get('login_errors'));
            $session->remove('login_errors');
        }
    }

    private function _is_ajax_request()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (!empty($_POST['ajax_request']) && $_POST['ajax_request'] === 'true');
    }

    private function _bind_login_form($username, $password, array $errors)
    {
        $this->apptemplate->veri('username', $username);
        $this->apptemplate->veri('password', $password);
        $this->apptemplate->veri('errors', $errors);
    }

    private function _respond_with_json(array $payload, $status_code = 200)
    {
        $discarded_output = $this->_flush_output_buffers();
        if ($discarded_output !== '') {
            $this->_log_login_debug("Discarded output: '" . bin2hex($discarded_output) . "'");
        }

        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        http_response_code($status_code);

        $response = json_encode($payload);
        if ($response !== false) {
            $this->_log_login_debug("JSON response: " . $response);
            echo $response;
        } else {
            echo '{"success":false,"message":"JSON encoding failed"}';
        }

        session_write_close();
        exit;
    }

    private function _flush_output_buffers()
    {
        $discarded_output = '';
        while (ob_get_level() > 0) {
            $discarded_output .= (string) ob_get_clean();
        }

        return $discarded_output;
    }

    private function _log_login_debug($message)
    {
        @file_put_contents(
            root_dir . 'logs/login_debug.log',
            "[" . date('Y-m-d H:i:s') . "] " . $message . "\n",
            FILE_APPEND
        );
    }

    // Logout Page
    public function logout($sid = NULL)
    {
        // Get sid value from Session
        $session_sid = $this->session->get('sid');

        // Check if sid comes from URL, otherwise use from session
        if ($sid !== NULL) {
            // Does the sid from URL match the session sid?
            if ($sid !== $session_sid) {
                exit($this->lang['mod_user']['ajax']['ssid_invalid']);
            }
            // Logout with sid from URL
            $this->user->logout($sid);
        } else {
            // Logout with sid from session
            $this->user->logout($session_sid);
        }

        $r = $this->site['saddress'];
        
        return Helper::yonlendir($r, 1);
    }

    public function forgot_password()
    {
        // If Member, redirect to panel
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');

        // Clear any previous success messages
        $this->apptemplate->veri('oke', null);
        $this->apptemplate->veri('mesaj', null);

        if ($this->_is_post_request()) {
            $this->_forgot_password();
        }
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());
        
        $d['title'] = $this->lang['mod_user']['forgot_password']['forgot_password_title'];
        $this->apptemplate->veri('d', $d);

        $this->apptemplate->tpl('mod_user/forgot-password');
    }

    private function _forgot_password()
    {
        $this->val = Load::validator('mod_user.forgot_password');
        $has_errors = $this->val->check();
        if ($has_errors) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $email = Req::Post_Safe('email');
            $this->model = Load::model('mod_user');
            $this->model->Getemail($email)->getResultOne(TRUE, TRUE);

            if ($this->model->getSize() !== 0) {
                $this->model->sync();
                if (!$this->_semail_at()) {
                    $this->_log_password_reset('Password reset email could not be sent for: ' . $email);
                }
            }

            $this->_appmesaj($this->lang['mod_user']['forgot_password']['info_1']);
        }
    }

    private function _semail_at()
    {
        // Replace any previous reset code so each request creates a fresh token.
        $this->password_reset_model = Load::model('mod_user.password_reset');
        $this->password_reset_model->getemail($this->model->email)->getResultOne(TRUE, TRUE);
        $key = $this->_generate_password_reset_code();

        if ($this->password_reset_model->getSize() !== 0) {
            $this->password_reset_model->sync();
            $this->password_reset_model->code = $key;
            $this->password_reset_model->created_at = date('Y-m-d H:i:s');
            $stored = $this->password_reset_model->update('email');
        } else {
            $this->password_reset_model->email = $this->model->email;
            $this->password_reset_model->code = $key;
            $this->password_reset_model->created_at = date('Y-m-d H:i:s');
            $stored = $this->password_reset_model->save();
        }

        if (!$stored) {
            return FALSE;
        }

        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->konu = $this->lang['mod_user']['password_reset']['title'];
        $this->apptemplate->veri('name', $this->model->name);
        $this->apptemplate->veri('last_name', $this->model->last_name);
        $this->apptemplate->veri('code', $key);
        $this->apptemplate->veri('site', $this->site);
        $this->apptemplate->veri('ip', $_SERVER['REMOTE_ADDR']);
        $email->mesaj = $this->apptemplate->fetch('mod_user/email_password_reset');
        if ($email->gonder()) {
            return TRUE;
        }
        return FALSE;
    }

    private function _generate_password_reset_code()
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(20));
            } catch (Exception $e) {
                $this->_log_password_reset('Secure token generation failed: ' . $e->getMessage());
            }
        }

        return Helper::randomkey(40);
    }

    private function _hash_password($password)
    {
        return function_exists('password_hash')
            ? password_hash($password, PASSWORD_DEFAULT)
            : md5($password);
    }

    private function _is_post_request()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }


    public function password_reset($code = NULL)
    {
        Run::import('index', '_index');
        $clean_code = is_string($code) ? Req::Url_Clear($code) : '';
        if ($clean_code !== 'NULL' && preg_match('/^[A-Za-z0-9]{20,80}$/', $clean_code)) {
            $this->apptemplate->veri('code', $clean_code);
        }
        if ($this->_is_post_request()) {
            $this->_password_reset();
        }
        $d['title'] = $this->lang['mod_user']['password_reset']['title'];
        $this->apptemplate->veri('d', $d);

        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());

        $this->apptemplate->tpl('mod_user/password_reset');
    }

    private function _password_reset()
    {
        $this->val = Load::validator('mod_user.password_reset');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->password_reset = Load::model('mod_user.password_reset');
            $this->password_reset->Getcode(Req::Post_Safe('code'))->gro()->sync();

            $this->model = Load::model('mod_user');
            $this->model->Getemail($this->password_reset->email)->gro()->sync();

            if ($this->model->email === '') {
                exit($this->lang['mod_user']['ajax']['membership_deleted']);
            }

            $new_password = Req::Post_Safe('new_password');
            $this->model->password = $this->_hash_password($new_password);

            if ($this->model->update('id')) {
                $this->password_reset->delete();
                $this->_appmesaj($this->lang['mod_user']['password_reset']['success_message']);
            } else {
                $this->_appemesaj($this->lang['mod_user']['error']['system_error']);
            }
        }
    }

    // Resends Activation Code
    public function resend_activation()
    {
        Run::import('index', '_index');
        if (Req::is_Post('resend_activation')) {
            $this->_resend_activation();
        }
        $d['title'] = $this->lang['mod_user']['resend_activation']['activation_code_label'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());
        $this->apptemplate->tpl('mod_user/resend_activation');
    }

    // Resends Activation Code
    private function _resend_activation()
    {
        $this->val = Load::validator('mod_user.resend_activation');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->activation = Load::model('mod_user.activation');
            $this->activation->getemail(Req::Post_Safe('email'))->gRo();
            if ($this->activation->getSize() === 0) {
                $this->_appemesaj($this->lang['mod_user']['resend_activation']['error']['no_activation_code_found']);
                return FALSE;
            }
            $this->activation->sync();
            if ($this->_email_at($this->activation->code)) {
                $this->_appmesaj($this->lang['mod_user']['resend_activation']['code_sent']);
            } else {
                $this->_appemesaj($this->lang['mod_user']['resend_activation']['error']['invalid_captcha']);
            }
        }
    }

    // Send Mail For Activation Code
    private function _email_at($code = NULL)
    {
        $key = $code !== NULL ? $code : Helper::randomkey(20);

        $this->model = Load::model('mod_user');
        $this->model->select('name,last_name,email')->Getemail(Req::Post_Safe('email'))->gRo()->sync();

        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->konu = $this->lang['mod_user']['register']['membership_activation'];
        $this->apptemplate->veri('name', $this->model->name);
        $this->apptemplate->veri('last_name', $this->model->last_name);
        $this->apptemplate->veri('code', $key);
        $this->apptemplate->veri('site', $this->site);
        $email->mesaj = $this->apptemplate->fetch('mod_user/email_activation');
        if ($email->gonder()) {
            if ($code === NULL) {
                $this->activation = Load::model('mod_user.activation');
                $this->activation->email = $this->model->email;
                $this->activation->code = $key;
                $this->activation->save();
            }
            return TRUE;
            /*$this->ak = Load::model('mod_user.activation');
            $this->ak->delete_email($this->model->email);
            $this->ak->email = $this->model->email;
            $this->ak->code = $key;
            if ($this->ak->save()) {
                return True;
            } else {
                return False;
            }*/
        } else {
            return False;
        }
    }

    // Membership Activation
    public function activation($code = NULL)
    {
        Run::import('index', '_index');
        if ($code != 'NULL' and strlen($code) == 20) {
            $this->apptemplate->veri('code', Req::Url_Clear($code));
        }
        if (Req::is_Post('activation')) {
            $this->_activation();
        }
        $d['title'] = $this->lang['mod_user']['activation']['membership_activation'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());

        $this->apptemplate->tpl('mod_user/activation');
    }

    // Membership Activation
    private function _activation()
    {
        $this->val = Load::validator('mod_user.activation');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->m = Load::model('mod_user.activation');
            $this->m->Getcode(Req::Post_Safe('code'))->gRo()->sync();
            $this->u = Load::model('mod_user');
            $this->u->Getemail($this->m->email)->gRo()->sync();
            $this->u->status = 'y';
            if ($this->u->update('id')) {
                $this->m->delete();
                $this->_appmesaj($this->lang['mod_user']['activation']['success_activated']);
            }
        }
    }

    // Membership Registration
    public function register()
    {
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');
        $this->_load_register_flash_messages();
        $d = $this->_load_register_settings();

        if ($d['status'] == '0') {
            $this->_appemesaj($this->lang['mod_user']['register']['registration_stopped']);
            $d['title'] = $this->lang['mod_user']['register']['membership_registration'];
            return FALSE;
        }

        $this->_activation = $d['activation'];
        $this->_group_id = $d['group_id'];

        if (Req::is_Post('register')) {
            $this->_register();
        }

        $d['title'] = $this->lang['mod_user']['register']['membership_registration'];
        $this->apptemplate->veri('d', $d);
        $this->_prepare_register_view_data();
        $this->apptemplate->tpl('mod_user/register');
    }

    private function _load_register_flash_messages()
    {
        if ($this->session->get('success_message')) {
            $this->apptemplate->veri('success_message', $this->session->get('success_message'));
        }

        if ($this->session->get('error_message')) {
            $this->apptemplate->veri('error_message', $this->session->get('error_message'));
        }
    }

    private function _load_register_settings()
    {
        $this->settings = Load::model('mod_user.mod_user_settings');
        $this->settings->all();

        $settings = [];
        foreach ($this->settings->toArray() as $val) {
            $settings[$val['k']] = $val['v'];
        }

        return $settings;
    }

    private function _prepare_register_view_data()
    {
        $this->apptemplate->veri('js_lang', json_encode($this->_get_register_js_lang()));
        $this->apptemplate->veri('country_codes', $this->_get_country_codes());

        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());
    }

    private function _get_register_js_lang()
    {
        return [
            'checking' => $this->lang['mod_user']['register']['js']['checking'],
            'username_available' => $this->lang['mod_user']['register']['js']['username_available'],
            'username_exists' => $this->lang['mod_user']['register']['js']['username_exists'],
            'username_short' => $this->lang['mod_user']['register']['js']['username_short'],
            'email_available' => $this->lang['mod_user']['register']['js']['email_available'],
            'email_exists' => $this->lang['mod_user']['register']['js']['email_exists'],
            'check_failed' => $this->lang['mod_user']['register']['js']['check_failed'],
            'register_success' => $this->lang['mod_user']['register']['js']['register_success'],
            'errors_title' => $this->lang['mod_user']['register']['js']['errors_title'],
            'validation_required' => $this->lang['mod_user']['register']['js']['validation_required'],
            'validation_required_name' => $this->lang['mod_user']['register']['js']['validation_required_name'],
            'validation_required_surname' => $this->lang['mod_user']['register']['js']['validation_required_surname'],
            'validation_required_username' => $this->lang['mod_user']['register']['js']['validation_required_username'],
            'validation_username_short' => $this->lang['mod_user']['register']['js']['validation_username_short'],
            'validation_username_long' => $this->lang['mod_user']['register']['js']['validation_username_long'],
            'validation_required_email' => $this->lang['mod_user']['register']['js']['validation_required_email'],
            'validation_invalid_email' => $this->lang['mod_user']['register']['js']['validation_invalid_email'],
            'validation_required_password' => $this->lang['mod_user']['register']['js']['validation_required_password'],
            'validation_password_short' => $this->lang['mod_user']['register']['js']['validation_password_short'],
            'validation_password_long' => $this->lang['mod_user']['register']['js']['validation_password_long'],
            'validation_confirm_password' => $this->lang['mod_user']['register']['js']['validation_confirm_password'],
            'validation_required_phone' => $this->lang['mod_user']['register']['js']['validation_required_phone'],
            'validation_required_captcha' => $this->lang['mod_user']['register']['js']['validation_required_captcha'],
            'password_complexity_error' => $this->lang['mod_user']['error']['password_complexity_error']
        ];
    }


    private function _register()
    {
        $this->val = Load::validator('mod_user.register');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
            return;
        }

        $this->apptemplate->veri('oke', True);
        $this->model = $this->_build_registration_model();

        if (!$this->model->save()) {
            $this->apptemplate->veri('error_message', $this->lang['mod_user']['database_error']);
            return;
        }

        $this->_queue_registration_flash_message();
        $this->_redirect_after_register();
    }

    private function _build_registration_model()
    {
        $model = Load::model('mod_user');
        $model->name = Text::title(Req::Post_Safe('name'));
        $model->last_name = Text::title(Req::Post_Safe('last_name'));
        $model->username = Req::Post_Safe('username');
        $model->email = Req::Post_Safe('email');
        $model->password = $this->_hash_password(Req::Post_Safe('password'));
        $model->phone = $this->_build_registration_phone();
        $model->ip = $_SERVER['REMOTE_ADDR'];
        $model->group_id = $this->_group_id;
        $model->status = $this->_resolve_registration_status();
        $model->updated_at = date('Y-m-d H:i:s');
        $model->created_at = date('Y-m-d H:i:s');
        $model->image = '';
        $model->city = '';
        $model->blog_name = '';
        $model->about = '';

        return $model;
    }

    private function _build_registration_phone()
    {
        $country_code = isset($_POST['country_code']) ? $_POST['country_code'] : '';
        $phone = Req::Post_Safe('phone');

        if ($country_code && $phone) {
            return $country_code . ' ' . $phone;
        }

        return $phone;
    }

    private function _resolve_registration_status()
    {
        if ($this->_activation == 1) {
            return 'n';
        }

        if ($this->_activation == 2) {
            return 'a';
        }

        return 'y';
    }

    private function _queue_registration_flash_message()
    {
        if ($this->_activation == 1) {
            if ($this->_email_at()) {
                $this->session->set('success_message', $this->lang['mod_user']['register']['registration_success_activation']);
            } else {
                $this->session->set('error_message', $this->lang['mod_user']['register']['error']['activation_email_error']);
            }
            return;
        }

        if ($this->_activation == 2) {
            $this->session->set('success_message', $this->lang['mod_user']['register']['registration_success_admin']);
            return;
        }

        $this->session->set('success_message', $this->lang['mod_user']['register']['registration_success']);
    }

    private function _redirect_after_register()
    {
        Helper::yonlen($this->site['address'] . 'register?success=1', 0);
        session_write_close();
        exit;
    }

    public function settings()
    {

        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'settings', $this->lang['mod_user']['role_errors']['settings'], 1) === False) {
            return False;
        }
        Run::import('management', '_index');

        if (Req::is_Post('edit')) {
            $this->_settings($_POST);
        }
        $this->settings = Load::model('mod_user.mod_user_settings');

        $sonuc = $this->settings->all()->toArray();

        foreach ($sonuc as $val) {
            $d[$val['k']] = $val['v'];
        }
        // Let's pull the Member Group
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'parent_id', 'group_id');


        $status = $d['status'];
        $activation = $d['activation'];
        $check_user_login = $d['check_user_login'];
        $this->site['title'] = $this->lang['mod_user']['admin']['management'];
        $this->tema->tpl('mod_user/settings');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('group', $group);
        $this->tema->veri('status', $status);
        $this->tema->veri('check_user_login', $check_user_login);
        $this->tema->veri('d', $d);
        $this->tema->veri('activation', $activation);
    }

    private function _settings($array)
    {
        $array = Req::Clear($array);
        $this->settings = Load::model('mod_user.mod_user_settings');
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($key != 'edit') {
                    $this->settings->k = $key;
                    $this->settings->v = $val;
                    if ($this->settings->update('k') === False) {
                        $this->_emesaj($this->lang['mod_user']['database_error']);
                        return False;
                    }
                }
            }
        }
        $this->_mesaj($this->lang['mod_user']['admin']['settings_updated']);
        return True;
    }


    public function panel($unused = null)
    {
        if ($this->check() === FALSE) {
            return FALSE;
        }

        if (func_num_args() > 0 && $unused !== null && $unused !== '') {
            Helper::yonlendir($this->site['saddress'] . 'mod_user/panel', 0);
            return FALSE;
        }

        Run::import('management', '_index');
        $this->_assert_panel_language_keys();
        $this->site['title'] = $this->lang['mod_user']['user_panel'];

        $this->tema->veri('site', $this->site);
        $this->tema->veri('panel_actions', $this->_build_panel_actions());
        $this->tema->tpl('mod_user/panel');
    }

    private function _build_panel_actions()
    {
        $management_lang = Load::lang('management');
        $video_embed_lang = Load::lang('mod_video_embed');
        $slider_lang = Load::lang('mod_slider');
        $video_lang = Load::lang('mod_video');
        $gallery_lang = Load::lang('mod_gallery');
        $redirect_lang = Load::lang('mod_redirect');

        $definitions = [
            [
                'module' => 'mod_content',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_content/list',
                'icon' => 'bi bi-file-earmark-text-fill',
                'title' => $management_lang['management']['content_management'],
            ],
            [
                'module' => 'mod_page',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_page/list',
                'icon' => 'bi bi-file-richtext',
                'title' => $management_lang['management']['page_management'],
            ],
            [
                'module' => 'mod_news',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_news/list',
                'icon' => 'bi bi-newspaper',
                'title' => $management_lang['management']['news_management'],
            ],
            [
                'module' => 'mod_announcements',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_announcements/list',
                'icon' => 'bi bi-megaphone-fill',
                'title' => $management_lang['management']['announcement_management'],
            ],
            [
                'module' => 'mod_publication',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_publication/list',
                'icon' => 'bi bi-journal-richtext',
                'title' => $management_lang['management']['publication_management'],
            ],
            [
                'module' => 'mod_video_embed',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_video_embed/list',
                'icon' => 'bi bi-youtube',
                'title' => $video_embed_lang['mod_video_embed']['list'],
            ],
            [
                'module' => 'mod_video',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_video/list',
                'icon' => 'bi bi-video',
                'title' => $video_lang['mod_video']['video_management'],
            ],
            [
                'module' => 'mod_gallery',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_gallery/list',
                'icon' => 'bi bi-images',
                'title' => $gallery_lang['mod_gallery']['admin']['title'],
            ],
            [
                'module' => 'mod_slider',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_slider/list',
                'icon' => 'bi bi-stars',
                'title' => $slider_lang['mod_slider']['list'],
            ],
            [
                'module' => 'mod_product',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_product/list',
                'icon' => 'bi bi-box-seam-fill',
                'title' => $management_lang['management']['products'],
            ],
            [
                'module' => 'mod_service',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_service/list',
                'icon' => 'bi bi-briefcase-fill',
                'title' => $management_lang['management']['services'],
            ],
            [
                'module' => 'mod_reference',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_reference/list',
                'icon' => 'bi bi-award',
                'title' => $management_lang['management']['references'],
            ],
            [
                'module' => 'mod_client',
                'permission' => 'list',
                'href' => $this->site['saddress'] . 'mod_client/list',
                'icon' => 'bi bi-people-fill',
                'title' => $management_lang['management']['clients'],
            ],
        ];

        $actions = [];
        foreach ($definitions as $definition) {
            if (isset($definition['module'], $definition['permission']) &&
                Roll::hasPermission($definition['module'], $definition['permission']) !== TRUE) {
                continue;
            }

            $actions[] = $definition;
        }

        return $actions;
    }

    private function _assert_panel_language_keys()
    {
        $management_lang = Load::lang('management');
        $video_embed_lang = Load::lang('mod_video_embed');
        $slider_lang = Load::lang('mod_slider');
        $video_lang = Load::lang('mod_video');
        $gallery_lang = Load::lang('mod_gallery');

        $required_keys = [
            [$this->lang, ['mod_user', 'panel', 'panel_title']],
            [$this->lang, ['mod_user', 'panel', 'home_title']],
            [$this->lang, ['mod_user', 'panel', 'admin_brand']],
            [$this->lang, ['mod_user', 'panel', 'welcome_main']],
            [$this->lang, ['mod_user', 'panel', 'recent_workspace']],
            [$this->lang, ['mod_user', 'panel', 'workspace_nav_media']],
            [$this->lang, ['mod_user', 'panel', 'workspace_nav_products']],
            [$this->lang, ['mod_user', 'panel', 'workspace_nav_more']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_title']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_copy']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_open_drawer']],
            [$management_lang, ['management', 'uyelik']],
            [$management_lang, ['management', 'content_workspace']],
            [$management_lang, ['management', 'content_and_page']],
            [$management_lang, ['management', 'content_management']],
            [$management_lang, ['management', 'news_management']],
            [$management_lang, ['management', 'publication_management']],
            [$management_lang, ['management', 'products']],
            [$management_lang, ['management', 'services']],
            [$management_lang, ['management', 'product_categories']],
            [$management_lang, ['management', 'service_categories']],
            [$management_lang, ['management', 'clients']],
            [$management_lang, ['management', 'references']],
            [$management_lang, ['management', 'system_tools']],
            [$management_lang, ['management', 'uyeler']],
            [$management_lang, ['management', 'uye_gruplari']],
            [$management_lang, ['management', 'membership_setting']],
            [$management_lang, ['management', 'uye_permission_yonetimi_acl']],
            [$management_lang, ['management', 'acl']],
            [$management_lang, ['management', 'page_management']],
            [$management_lang, ['management', 'language_management_menu']],
            [$management_lang, ['management', 'settings']],
            [$management_lang, ['management', 'announcement_management']],
            [$video_embed_lang, ['mod_video_embed', 'list']],
            [$video_lang, ['mod_video', 'video_management']],
            [$gallery_lang, ['mod_gallery', 'admin', 'title']],
            [$slider_lang, ['mod_slider', 'list']],
        ];

        foreach ($required_keys as $required_key) {
            $this->_require_panel_language_value($required_key[0], $required_key[1]);
        }
    }

    private function _require_panel_language_value($dictionary, array $path)
    {
        $value = $dictionary;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                $this->_render_panel_language_error(implode('.', $path));
            }
            $value = $value[$segment];
        }

        if (!is_string($value) || trim($value) === '') {
            $this->_render_panel_language_error(implode('.', $path));
        }

        return $value;
    }

    private function _render_panel_language_error($key_path)
    {
        error_log('mod_user_panel_missing_language_key: ' . $key_path);

        header("HTTP/1.1 500 Internal Server Error");

        $management_lang = Load::lang('management');
        $management_copy = isset($management_lang['management']) && is_array($management_lang['management'])
            ? $management_lang['management']
            : [];

        $error_title = isset($management_copy['route_error_page_title']) && is_string($management_copy['route_error_page_title']) && $management_copy['route_error_page_title'] !== ''
            ? $management_copy['route_error_page_title']
            : 'Administration Error';

        $site = Kayit::getir('site_config');
        $site['title'] = $error_title;

        $this->tema->lang($management_lang);
        $this->tema->setGlobal('site', $site);
        $this->tema->setGlobal('error_title', $error_title);
        $this->tema->setGlobal('error_headline', $error_title);
        $this->tema->setGlobal('error_heading', $key_path);
        $this->tema->setGlobal('error_description', '');
        $this->tema->setGlobal('hide_navbar', true);
        $this->tema->setGlobal('hide_search', true);
        $this->tema->setGlobal('show_fab', false);
        $this->tema->setGlobal('show_sidebar', false);
        $this->tema->setGlobal('show_navbar', false);
        $this->tema->setGlobal('show_filter_sheet', false);
        $this->tema->setGlobal('show_extra_sheets', false);
        $this->tema->setGlobal('show_wizards', false);
        $this->tema->tpl('pages/route_error');
        $this->tema->output();
        exit();
    }

    private function _get_social_platform_map()
    {
        return [
            'twitter' => ['key' => 'twitter', 'label' => 'X / Twitter', 'icon_class' => 'bi bi-twitter-x', 'display_order' => 1],
            'facebook' => ['key' => 'facebook', 'label' => 'Facebook', 'icon_class' => 'bi bi-facebook', 'display_order' => 2],
            'instagram' => ['key' => 'instagram', 'label' => 'Instagram', 'icon_class' => 'bi bi-instagram', 'display_order' => 3],
            'linkedin' => ['key' => 'linkedin', 'label' => 'LinkedIn', 'icon_class' => 'bi bi-linkedin', 'display_order' => 4],
            'youtube' => ['key' => 'youtube', 'label' => 'YouTube', 'icon_class' => 'bi bi-youtube', 'display_order' => 5],
            'spotify' => ['key' => 'spotify', 'label' => 'Spotify', 'icon_class' => 'bi bi-spotify', 'display_order' => 6],
            'tiktok' => ['key' => 'tiktok', 'label' => 'TikTok', 'icon_class' => 'bi bi-tiktok', 'display_order' => 7],
        ];
    }

    private function _get_social_platforms()
    {
        return array_values($this->_get_social_platform_map());
    }

    private function _blank_social_links()
    {
        $blank = [];
        foreach ($this->_get_social_platform_map() as $platform => $meta) {
            $blank[$platform] = '';
        }

        return $blank;
    }

    private function _extract_social_links($input)
    {
        $links = $this->_blank_social_links();
        if (!is_array($input)) {
            return $links;
        }

        foreach ($this->_get_social_platform_map() as $platform => $meta) {
            if (isset($input[$platform])) {
                $links[$platform] = trim((string) $input[$platform]);
            }
        }

        return $links;
    }

    private function _normalize_social_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) !== 1) {
            $url = 'https://' . ltrim($url, '/');
        }

        return filter_var($url, FILTER_SANITIZE_URL);
    }

    private function _save_social_links($userId, $input)
    {
        $userId = intval($userId);
        $db = Load::lib('Database');
        $db->query("DELETE FROM `mod_user_social` WHERE `user_id` = " . $userId);

        $links = $this->_extract_social_links($input);
        $social = Load::model('mod_user.mod_user_social');
        foreach ($this->_get_social_platform_map() as $platform => $meta) {
            $url = $this->_normalize_social_url($links[$platform]);
            if ($url === '') {
                continue;
            }

            $social->flush();
            $social->user_id = $userId;
            $social->platform = $platform;
            $social->url = $url;
            $social->icon_class = $meta['icon_class'];
            $social->display_order = $meta['display_order'];
            $social->is_active = 'y';

            if ($social->save() === false) {
                return false;
            }
        }

        return true;
    }

    private function _load_social_links($userId)
    {
        $payload = [
            'map' => $this->_blank_social_links(),
            'items' => [],
        ];

        if (intval($userId) === 0) {
            return $payload;
        }

        $social = Load::model('mod_user.mod_user_social');
        $social->flush();
        $social->where_user_id_and_is_active(intval($userId), 'y')->order('display_order', 'ASC')->gR();

        if ($social->getSize() === 0) {
            return $payload;
        }

        $platformMap = $this->_get_social_platform_map();
        foreach ($social->toArray() as $row) {
            if (!isset($platformMap[$row['platform']])) {
                continue;
            }

            $payload['map'][$row['platform']] = $row['url'];
            $payload['items'][] = array_merge($row, [
                'label' => $platformMap[$row['platform']]['label'],
            ]);
        }

        return $payload;
    }

    // Helper: Prepare phone data for view
    private function _prepare_phone_data(&$d)
    {
        // Send the full phone number (+90544...), JavaScript will extract the country code.
        $d['phone'] = isset($d['phone']) ? $d['phone'] : '';
        $d['country_code'] = ''; // No longer needed
    }

    // Helper: Get combined phone from POST
    private function _get_combined_phone()
    {
        $country_code = isset($_POST['country_code']) ? $_POST['country_code'] : '';
        $phone = Req::Post_Safe('phone');
        
        if ($country_code && $phone) {
            // Remove spaces for E.164 standard format
            return $country_code . $phone;
        }
        return $phone;
    }

    // Helper: Get list of country codes
    private function _get_country_codes()
    {
        return Load::lib('Country')->get_all();
    }

    public function add()
    {
        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'add', $this->lang['mod_user']['role_errors']['add'], 1) === False) {
            return False;
        }
        Run::import('management', '_index');
        
        // Initialize data array for template
        $d = [
            'username' => '', 'password' => '', 'email' => '', 
            'name' => '', 'last_name' => '', 'phone' => '', 'city' => '', 
            'blog_name' => '', 'about' => '', 'status' => 'y',
            'social_links' => $this->_blank_social_links()
        ];

        if (Req::is_Post('add')) {
            $this->_add($d);
        }
        
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'parent_id', 'group_id');
        $this->site['title'] = $this->lang['mod_user']['admin']['add_area'];
        
        // Prepare phone data for display (whether initial or after error)
        $this->_prepare_phone_data($d);
        
        $this->tema->veri('site', $this->site);
        $this->tema->veri('group', $group);
        
        // Pass country codes
        $this->tema->veri('country_codes', $this->_get_country_codes());
        $this->tema->veri('social_platforms', $this->_get_social_platforms());
        
        // Pass all data variables to template
        foreach($d as $k => $v) {
            $this->tema->veri($k, $v);
        }
        
        $this->tema->tpl('mod_user/add');
    }

    private function _add(&$d)
    {
        $this->val = Load::validator('mod_user');
        
        // Update $d with posted values so form retains data on error
        foreach($_POST as $key => $val) {
            if(array_key_exists($key, $d)) {
                $d[$key] = Req::Post_Safe($key);
            }
        }
        $d['social_links'] = $this->_extract_social_links($_POST['social_links'] ?? []);

        if ($this->val->check()) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $db = Load::lib('Database');
            $db->start_transaction();
            $u = Load::model('mod_user');
            $u->username = Req::Post_Safe('username');
            $u->password = $this->_hash_password(Req::Post_Safe('password'));
            $u->email = Req::Post_Safe('email');
            $u->group_id = Req::Post_Safe('group_id');
            $u->name = Text::title(Req::Post_Safe('name'));
            $u->last_name = Text::title(Req::Post_Safe('last_name'));
            
            // Use helper for phone
            $u->phone = $this->_get_combined_phone();

            // Automatic date assignments
            $u->updated_at = date('Y-m-d H:i:s');
            $u->created_at = date('Y-m-d H:i:s');
            
            $u->ip = $_SERVER['REMOTE_ADDR']; // Set IP address

            // Optional fields with default values to prevent DB errors
            $u->city = (Req::Post_Safe('city')) ? Req::Post_Safe('city') : '';
            $u->about = (Req::Post_Safe('about')) ? Req::Post_Safe('about') : '';
            $u->blog_name = (Req::Post_Safe('blog_name')) ? Req::Post_Safe('blog_name') : '';
            $u->status = (Req::Post_Safe('status')) ? Req::Post_Safe('status') : 'y';
            
            // Image Upload Logic
            if (isset($_FILES['image']['name']) and $_FILES['image']['name'] != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_user';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width'
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ]
                ];
                if ($image->_resize($option)) {
                    $u->image = $image->url;
                } else {
                    $this->tema->veri('errors', [$image->error]);
                    return FALSE;
                }
            } else {
                $u->image = ''; // Default empty string if no image uploaded
            }

            if ($u->save(true)) {
                if ($this->_save_social_links($u->id, $_POST['social_links'] ?? []) === false) {
                    $db->rollback();
                    $this->tema->veri('errors', [$this->lang['mod_user']['database_error']]);
                    return FALSE;
                }

                $db->commit();
                $this->_mesaj($this->lang['mod_user']['admin']['added']);
                $d = [
                    'username' => '', 'password' => '', 'email' => '',
                    'name' => '', 'last_name' => '', 'phone' => '', 'city' => '',
                    'blog_name' => '', 'about' => '', 'status' => 'y',
                    'social_links' => $this->_blank_social_links()
                ];
            } else {
                $db->rollback();
                $this->tema->veri('errors', [$this->lang['mod_user']['database_error']]);
            }
        }
    }

    // Change My Password
    public function change_password()
    {
        // Is Member?
        if ($this->check() === FALSE) {
            return FALSE;
        }

        Run::import('management', '_index');

        if (Req::is_Post('edit')) {
            $this->_change_password();
        }

        $this->site['title'] = $this->lang['mod_user']['password_change'];
        $this->tema->veri('site', $this->site);
        $this->tema->tpl('mod_user/change_password');
    }


    private function _change_password()
    {
        $this->val = Load::validator('mod_user.change_password');
        if ($this->val->check()) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $u = Load::model('mod_user');
            $new_password_hash = $this->_hash_password(Req::Post_Safe('new_password'));
            $u->password = $new_password_hash;
            $u->id = $this->session->get('id');
            if ($u->update('id')) {
                $this->session->remove('password');
                $this->session->set('password', $new_password_hash);
                $this->_mesaj($this->lang['mod_user']['password_changed_success']);
            } else {
                $this->tema->veri('errors', [$this->lang['mod_user']['database_error']]);
            }
        }
    }

    // Update Info
    public function info()
    {
        // Is Member?
        if ($this->check() === FALSE) {
            return FALSE;
        }

        Run::import('management', '_index');

        if (Req::is_Post('edit')) {
            $this->_info();
        }

        $u = Load::model('mod_user');
        $u->Getid($this->session->get('id'))->gRo();
        $d = $u->toArray();
        $d['social_links'] = $this->_load_social_links($this->session->get('id'))['map'];
        
        // Use helper to parse phone data
        $this->_prepare_phone_data($d);

        $this->site['title'] = $this->lang['mod_user']['info_change'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        
        // Pass country codes
        $this->tema->veri('country_codes', $this->_get_country_codes());
        $this->tema->veri('social_platforms', $this->_get_social_platforms());
        $this->tema->tpl('mod_user/info');
    }

    private function _info()
    {
        $this->val = Load::validator('mod_user.info');
        if ($this->val->check()) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $db = Load::lib('Database');
            $db->start_transaction();
            $u = Load::model('mod_user');
            $u->name = Req::Post_Safe('name');
            $u->last_name = Req::Post_Safe('last_name');
            $u->updated_at = date('Y-m-d H:i:s'); // Automatically set update time
            $u->email = Req::Post_Safe('email');
            
            // Use helper for phone
            $u->phone = $this->_get_combined_phone();
            
            $u->city = Req::Post_Safe('city');
            $u->blog_name = Req::Post_Safe('blog_name');
            $u->about = Req::Post_Safe('about');
            $u->id = $this->session->get('id');


            // If Image exists
            if (isset($_FILES['image']['name']) and $_FILES['image']['name'] != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_user';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width'
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ]
                ];
                if ($image->_resize($option)) {
                    $u->image = $image->url;
                    $this->session->set('image', $image->url);
                } else {
                    $this->_emesaj($image->error);
                    return FALSE;
                }
            } else {
                if (isset($u->image)) {
                    unset($u->image);
                }
            }
            if ($u->update('id') && $this->_save_social_links($this->session->get('id'), $_POST['social_links'] ?? [])) {
                $db->commit();
                $this->session->set('full_name', Text::title($u->name . " " . $u->last_name));
                $this->_mesaj($this->lang['mod_user']['info_updated_success']);
            } else {
                $db->rollback();
                $this->tema->veri('errors', [$this->lang['mod_user']['database_error']]);
            }
        }
    }

    public function delete_image()
    {
        // Is Member?
        if ($this->check() === FALSE) {
            return FALSE;
        }
        $id = intval(Req::Post_Safe('id'));

        $this->mod_user = Load::model('mod_user');

        if ($this->mod_user->where_id($id)->getResultOne(TRUE, TRUE)->getSize() === 0) {
            exit(':');
            return FALSE;
        }


        $d = $this->mod_user->toArray();

        if ($d['id'] !== $this->session->get('id')) {
            exit('::');
        }

        $this->mod_user->sync();

        if ($this->mod_user->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_user';
            $image->url = $this->mod_user->image;
            $option = [
                '/' =>
                [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 100,
                    'height' => NULL,
                    'option' => 'width',
                ]
            ];
            if ($image->_delete_files($option)) {
                $this->mod_user->image = '';
                $this->mod_user->update('id');
                $this->session->set('image', '');
            echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    public function list()
    {

        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'list', $this->lang['mod_user']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_user']['admin']['list'];
        $this->tema->veri('site', $this->site);
        $this->tema->tpl('mod_user/list');
    }


    function xml()
    {
        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'list', $this->lang['mod_user']['role_errors']['list'], 1) === False) {
            return False;
        }
        $this->grid = Load::model('mod_user.usergrid');
        return $this->grid->xml();
    }



    public function datatable_json()
    {
        try {
            $i = Load::model('mod_user.user');

            $colomns = [];
            $colomns[] = 'id';
            $colomns[] = 'username';
            $colomns[] = 'email';
            $colomns[] = 'name';
            $colomns[] = 'last_name';
            $colomns[] = 'status';
            $colomns[] = 'group_id';
            $colomns[] = 'image';
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

            if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
                foreach (Req::Post_Safe('arama') as $k => $v) {
                    if ($v == '') {
                        continue;
                    }

                    $safe_value = Req::Clear($v);

                    if ($k === 'identity') {
                        $full_name = trim(preg_replace('/\s+/', ' ', $safe_value));
                        $wheres[] = "(
                            username LIKE '%" . $safe_value . "%'
                            OR name LIKE '%" . $safe_value . "%'
                            OR last_name LIKE '%" . $safe_value . "%'
                            OR CONCAT(TRIM(name), ' ', TRIM(last_name)) LIKE '%" . $full_name . "%'
                        )";
                        continue;
                    }

                    if (in_array($k, $colomns)) {
                        $wheres[] = $k . " LIKE '%" . $safe_value . "%'";
                    }
                }
            }

            if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
                foreach (Req::Post_Safe('arama_radio') as $k => $v) {
                    if (in_array($k, $colomns) && $v != '') {
                        $wheres[] = $k . "='" . Req::Clear($v) . "'";
                    }
                }
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


            $status_list = [
                'y' => ['success', $this->lang['mod_user']['grid']['active']],
                'n' => ['danger', $this->lang['mod_user']['grid']['waiting_email_activation']],
                'a' => ['danger', $this->lang['mod_user']['grid']['waiting_admin_activation']],
            ];


            if ($getCount > 0) {
                foreach ($i->toArray() as $content) {

                    $last = "";
                    if (Roll::hasPermission('mod_user', 'edit')) {
                        $last .= '<button onclick="mod_user_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_user']['button']['edit'] . ' </button>';
                    }
                    if (Roll::hasPermission('mod_user', 'delete')) {
                        $last .= '<button onclick="mod_user_table_ajax.akm.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_user']['button']['delete'] . ' </button>';
                    }

                    // Safe status display
                    $status_html = '';
                    if (isset($content['status']) && isset($status_list[$content['status']])) {
                        $status_html = '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>';
                    } else {
                        $status_html = '<span class="label label-default">' . (isset($content['status']) ? $content['status'] : '') . '</span>';
                    }

                    $data[] = [
                        '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                        $content['id'],
                        $content['username'],
                        $content['email'],
                        $content['name'],
                        $content['last_name'],
                        $status_html,
                        $content['group_id'],
                        $content['image'],
                        $last
                    ];
                }
            }
            
            // Use Load::model instead of new user_model() for consistency and safety
            $ii = Load::model('mod_user.user');
            
            // Clear output buffer to ensure clean JSON
            if (ob_get_length()) ob_clean();
            
            header('Content-Type: application/json');
            echo json_encode([
                'data' => $data,
                'recordsFiltered' => $getCount,
                'recordsTotal' => $ii->getCount(),
            ]);
        } catch (Exception $e) {
            // Return error as JSON
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Server Error: ' . $e->getMessage(),
                'data' => [],
                'recordsFiltered' => 0,
                'recordsTotal' => 0
            ]);
        }
    }




    function delete()
    {
        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'delete', $this->lang['mod_user']['role_errors']['delete']) === False) {
            return False;
        }
        if (Req::is_Post('delete')) {
            return $this->_delete(Req::Post_Safe('delete'));
        }
    }

    public function _delete($delete)
    {
        $this->i = Load::model('mod_user');
        foreach ($delete as $s) {
            $social = Load::model('mod_user.mod_user_social');
            $social->flush();
            $social->where_user_id($s)->gR();
            if ($social->getSize() !== 0) {
                foreach ($social->toArray() as $socialRow) {
                    $cleanup = Load::model('mod_user.mod_user_social');
                    $cleanup->flush();
                    $cleanup->id = $socialRow['id'];
                    $cleanup->delete();
                }
            }

            $this->i->getid($s)->gRo()->sync()->delete();

            if ($this->i->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_user';
                $image->url = $this->i->image;
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ]
                ];
                if ($image->_delete_files($option) === FALSE) {
                    echo $image->error;
                }
            }
        }
        echo 'Oke';
    }

    public function edit($id = NULL)
    {
        $is_ajax = $this->_is_ajax_request();
        if ($is_ajax && Req::is_Post('edit')) {
            ob_start();
        }

        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'edit', $this->lang['mod_user']['role_errors']['edit'], 1) === False) {
            return False;
        }
        Run::import('management', '_index');

        $this->i = Load::model('mod_user');
        $id = Req::Url_Clear($id);

        if ($this->i->exists_id($id) === False) {
            if ($is_ajax) {
                $this->_respond_with_json([
                    'success' => false,
                    'message' => $this->lang['mod_user']['ajax']['error'],
                    'errors' => [$this->lang['mod_user']['error']['user_not_found']],
                ], 404);
            }
            $this->_emesaj($this->lang['mod_user']['error']['user_not_found']);
            return;
        }

        if (Req::is_Post('edit')) {
            $result = $this->_edit($id, $is_ajax);
            if ($is_ajax) {
                $this->_respond_with_json($result['payload'], $result['status']);
            }
        }


        $d = $this->i->getResultOne()->toArray();
        $d['username'] = Req::is_Post('username') ? Req::Post('username') : $d['username'];
        $d['email'] = Req::is_Post('email') ? Req::Post('email') : $d['email'];
        $d['social_links'] = $this->_load_social_links($id)['map'];
        
        // Prepare phone data
        $this->_prepare_phone_data($d);

        // Let's pull the Member Group
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'parent_id', 'group_id');

        $this->site['title'] = $this->lang['mod_user']['admin']['edit_area'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->veri('group', $group);
        
        // Pass country codes
        $this->tema->veri('country_codes', $this->_get_country_codes());
        $this->tema->veri('social_platforms', $this->_get_social_platforms());
        $this->tema->tpl('mod_user/edit');
    }


    private function _edit($id, $is_ajax = false)
    {

        $this->val = Load::validator('mod_user.edit');

        if ($this->val->check($id)) {
            $this->tema->veri('errors', $this->val->errors);
            if ($is_ajax) {
                return [
                    'status' => 422,
                    'payload' => [
                        'success' => false,
                        'message' => $this->lang['mod_user']['ajax']['validation_error'],
                        'errors' => array_values((array) $this->val->errors),
                    ],
                ];
            }
        } else {
            $db = Load::lib('Database');
            $db->start_transaction();
            $u = Load::model('mod_user');
            $u->id = $id;
            $u->username = Req::Post_Safe('username');
            $u->email = Req::Post_Safe('email');
            $u->group_id = Req::Post_Safe('group_id');
            $u->name = Text::title(Req::Post_Safe('name'));
            $u->last_name = Text::title(Req::Post_Safe('last_name'));
            
            // Use helper for phone
            $u->phone = $this->_get_combined_phone();
            
            $u->updated_at = date('Y-m-d H:i:s'); // Automatically set update time
            if (Req::Post_Safe('password') != '') {
                $u->password = $this->_hash_password(Req::Post_Safe('password'));
            }

            // Additional fields
            $u->status = Req::Post_Safe('status');
            $u->city = Req::Post_Safe('city');
            $u->blog_name = Req::Post_Safe('blog_name');
            $u->about = Req::Post_Safe('about');

            // Image Upload Logic
            if (isset($_FILES['image']['name']) and $_FILES['image']['name'] != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_user';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width'
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ]
                ];
                if ($image->_resize($option)) {
                    $u->image = $image->url;
                } else {
                    $this->tema->veri('errors', [$image->error]);
                    if ($is_ajax) {
                        return [
                            'status' => 422,
                            'payload' => [
                                'success' => false,
                                'message' => $this->lang['mod_user']['ajax']['validation_error'],
                                'errors' => [$image->error],
                            ],
                        ];
                    }
                    return FALSE;
                }
            }

            if ($u->update('id') && $this->_save_social_links($id, $_POST['social_links'] ?? [])) {
                $db->commit();
                $this->_mesaj($this->lang['mod_user']['admin']['updated']);
                if ($is_ajax) {
                    return [
                        'status' => 200,
                        'payload' => [
                            'success' => true,
                            'message' => $this->lang['mod_user']['admin']['updated'],
                            'redirect' => $this->site['saddress'] . 'mod_user/list',
                        ],
                    ];
                }
            } else {
                $db->rollback();
                $this->tema->veri('errors', [$this->lang['mod_user']['database_error']]);
                if ($is_ajax) {
                    return [
                        'status' => 500,
                        'payload' => [
                            'success' => false,
                            'message' => $this->lang['mod_user']['ajax']['error'],
                            'errors' => [$this->lang['mod_user']['database_error']],
                        ],
                    ];
                }
            }
        }

        return [
            'status' => 200,
            'payload' => [
                'success' => false,
                'message' => $this->lang['mod_user']['ajax']['error'],
                'errors' => [],
            ],
        ];
    }


    public function acl()
    {

        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'acl', $this->lang['mod_user']['role_errors']['acl'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');


        if (Req::is_Post('gonder')) {

            $this->_acl();
        }

        Load::modelfile('mod_user.mod_user_perm'); // Permissions Model
        $usergroup = Load::model('mod_user_group'); // Group Model
        $mods = Load::model('mods'); // Mod Model
        $userroll = Load::model('mod_user.mod_user_roll'); // Role Model

        $this->site['title'] = $this->lang['mod_user']['admin']['acl'];

        $this->tema->veri('site', $this->site);
        $this->tema->tpl('mod_user/acl');


        // Pull All Groups
        $usergroup->all();
        $gruplar = $usergroup->toArray();
        // Send Groups to Theme
        $this->tema->veri('gruplar', $gruplar);


        // Pull Mods
        $mods->order('mod_id', 'DESC')->all('asc');


        $modlar = $mods->toArray();
        $this->tema->veri('modlar', $modlar);

        // Pull Roles
        $userroll->all();
        $roller = $userroll->toArray();


        // Load Module Language Files
        foreach ($modlar as $mod) {
            $this->tema->lang(Load::lang($mod['mod_name']));
            // Configure Modules and Roles
            foreach ($roller as $roll) {
                if ($roll['mod_id'] === $mod['mod_id']) {
                    $sroll[$roll['mod_id']][] = $roll;
                }
            }
        }

        $permler = Load::model('mod_user.mod_user_perm');
        $permler->order('mod_id', 'DESC')->all();
        $this->tema->veri('permler', $permler->toArray());

        $this->tema->veri('roller', $sroll);
    }


    private function _acl()
    {
        Load::libfile('Database');
        $perm = Load::model('mod_user.mod_user_perm'); // Permissions Model
        $db = new Database();
        $y = Req::Post_Safe('list');

        // Start Transaction
        $db->start_transaction();
        // Truncate Table
        $db->truncate("mod_user_perm");

        foreach ($y as $d => $g) {
            foreach ($g as $f => $m) {
                foreach ($m as $k => $u) {

                    $perm->group_id = $d;
                    $perm->roll_id = $k;
                    $perm->mod_id = $f;
                    if ($perm->save() === False) {
                        // If any error occurs, let's Rollback everything.
                        $db->rollback();
                        $this->_emesaj($this->lang['mod_user']['database_error']);
                    }
                }
            }
        }
        // If there is no problem, Commit and Apply
        $db->commit();
        // Update administrator Roles
        $this->user->_roll($this->session->get('group_id'));
        $this->_mesaj($this->lang['mod_user']['admin']['permissions_updated']);
    }


    // Let's check if Logged In or Not
    private function _check_login()
    {
        // Is Logged In?
        if ($this->user->checklogin() === True) {
            Helper::yonlendir($this->site['saddress'] . "mod_user/panel", 2);
            return TRUE;
        }
        return FALSE;
    }

    // If Member Not Logged In, redirect to login
    public function check()
    {
        if ($this->user->checklogin() === False) {
            // Redirect to home with modal trigger instead of static login page
            Helper::yonlendir($this->site['saddress'] . "?login=modal", 0);
            return FALSE;
        }
        return TRUE;
    }

    // Generic Message
    private function _mesaj($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Negative Message (Admin)
    private function _emesaj($mesaj)
    {
        $this->tema->veri('errors', (array)$mesaj);
    }

    // Positive Message (App)
    private function _appmesaj($mesaj)
    {
        $this->apptemplate->veri('oke', True);
        $this->apptemplate->veri('mesaj', $mesaj);
    }

    // Negative Message (App)
    private function _appemesaj($mesaj)
    {
        $this->apptemplate->veri('errors', (array)$mesaj);
    }

    private function _log_password_reset($message)
    {
        @file_put_contents(
            root_dir . 'logs/password_reset.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND
        );
    }

    // For Javascript Validator
    public function check_user_exists()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->_is_ajax_request()) {
                Helper::yonlendir($this->site['saddress'], 0);
                return;
            }
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs[2] = "false";
        } else {
            $arrayToJs[2] = "true";
        }
        $this->_respond_with_json(['jsonValidateReturn' => $arrayToJs]);
    }

    // For Javascript Validator
    public function check_username()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->_is_ajax_request()) {
                Helper::yonlendir($this->site['saddress'], 0);
                return;
            }
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs[2] = "true";
        } else {
            $arrayToJs[2] = "false";
        }
        $this->_respond_with_json(['jsonValidateReturn' => $arrayToJs]);
    }

    // For Javascript Validator
    public function check_email()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->_is_ajax_request()) {
                Helper::yonlendir($this->site['saddress'], 0);
                return;
            }
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_email($val) === False) {
            $arrayToJs[2] = "true";
        } else {
            $arrayToJs[2] = "false";
        }
        $this->_respond_with_json(['jsonValidateReturn' => $arrayToJs]);
    }

    // Real-time username validation for registration
    public function checkUsernameAvailability()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->_is_ajax_request()) {
                Helper::yonlendir($this->site['saddress'], 0);
                return;
            }
            $this->_respond_with_json([
                'available' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $username = Req::Post_Safe('username');

        if (empty($username) || strlen($username) < 2) {
            $this->_respond_with_json([
                'available' => false,
                'message' => $this->lang['mod_user']['register']['js']['username_short']
            ], 400);
        }

        // Check real username availability
        $this->model = Load::model('mod_user');
        $isAvailable = !$this->model->exists_username($username);

        $this->_respond_with_json([
            'available' => $isAvailable,
            'message' => $isAvailable ?
                $this->lang['mod_user']['register']['js']['username_available'] :
                $this->lang['mod_user']['register']['js']['username_exists']
        ]);
    }

    // Real-time username validation for login step-by-step
    public function checkUserExistence()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->_is_ajax_request()) {
                Helper::yonlendir($this->site['saddress'], 0);
                return;
            }
            $this->_respond_with_json([
                'exists' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $username = Req::Post_Safe('username');

        if (empty($username)) {
            $this->_respond_with_json([
                'exists' => false,
                'message' => $this->lang['mod_user']['error']['specify_username']
            ], 400);
        }

        $this->model = Load::model('mod_user');
        $user = $this->model->exists_username($username);

        if ($user) {
            $this->_respond_with_json([
                'exists' => true,
                'username' => $username,
                'message' => 'Success'
            ]);
        } else {
            $this->_respond_with_json([
                'exists' => false,
                'message' => $this->lang['mod_user']['error']['username_not_found']
            ]);
        }
    }

    // Captcha method
    public function captcha()
    {
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            $captcha = Load::lib('Captcha');
            $captcha->generate();
            $image_data = $captcha->image();

            if (strpos($image_data, 'data:image/png;base64,') === 0) {
                echo base64_decode(substr($image_data, 22));
            } else {
                if (file_exists($image_data)) {
                    echo file_get_contents($image_data);
                } else {
                    http_response_code(404);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
        }
        exit;
    }
}
