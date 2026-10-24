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
        $this->theme = Load::lib('AdminTemplate');

        $this->apptemplate = Load::lib('TwigTemplate');
        // Load Database Library
        //$this->db = Load::lib('Database');
        // Get site settings
        $this->site = Registry::get('site_config');
        // Call Language File
        $this->lang = Load::lang('mod_user');
        // Send Language File to Theme
        $this->theme->lang($this->lang);
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
            echo 'Success';
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
            echo 'Success';
        }
    }


    // Function that sends mail when account is activated
    private function _management_akfilestime_email_at()
    {
        $email = Load::lib('Email');
        $email->html = True;
        $email->to = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->subject = $this->lang['mod_user']['activation']['membership_activation'];
        $this->apptemplate->set('name', $this->model->name);
        $this->apptemplate->set('last_name', $this->model->last_name);
        $this->apptemplate->set('site', $this->site);
        $email->message = $this->apptemplate->fetch('mod_user/email_admin_activation');
        if ($email->send()) {
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
        $this->apptemplate->set('d', $d);
        $this->_load_login_errors();

        if ($this->_check_login()) {
            return;
        }

        // Redirect to homepage with modal if accessed directly (non-AJAX GET)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$this->_is_ajax_request()) {
            Helper::redirect($this->site['saddress'] . "?login=modal", 0);
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
                $this->_app_message($this->lang['mod_user']['login_success_wait']);

                if ($is_ajax) {
                    $this->_respond_with_json([
                        'success' => true,
                        'message' => $this->lang['mod_user']['ajax']['login_success'],
                        'redirect' => $r
                    ]);
                }

                Helper::jsRedirect($r, 3);
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
            $this->apptemplate->set('errors', $session->get('login_errors'));
            $session->remove('login_errors');
        }
    }

    private function _is_ajax_request()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (!empty($_POST['ajax_request']) && $_POST['ajax_request'] === 'true') ||
            (!empty($_GET['ajax']) && ($_GET['ajax'] === '1' || $_GET['ajax'] === 'true')) ||
            (!empty($_GET['is_ajax']) && ($_GET['is_ajax'] === '1' || $_GET['is_ajax'] === 'true'));
    }

    private function _bind_login_form($username, $password, array $errors)
    {
        $this->apptemplate->set('username', $username);
        $this->apptemplate->set('password', $password);
        $this->apptemplate->set('errors', $errors);
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
        
        return Helper::redirect($r, 1);
    }

    public function forgot_password()
    {
        // If Member, redirect to panel
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');

        // Clear any previous success messages
        $this->apptemplate->set('Success', null);
        $this->apptemplate->set('message', null);

        if ($this->_is_post_request()) {
            $this->_forgot_password();
        }
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->set('captcha_image', $this->captcha->image());
        
        $d['title'] = $this->lang['mod_user']['forgot_password']['forgot_password_title'];
        $this->apptemplate->set('d', $d);

        $this->apptemplate->tpl('mod_user/forgot-password');
    }

    private function _forgot_password()
    {
        $this->val = Load::validator('mod_user.forgot_password');
        $has_errors = $this->val->check();
        if ($has_errors) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->set($key, Req::Post_Safe($key));
            }
            $this->apptemplate->set('errors', $this->val->errors);
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

            $this->_app_message($this->lang['mod_user']['forgot_password']['info_1']);
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
        $email->to = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->subject = $this->lang['mod_user']['password_reset']['title'];
        $this->apptemplate->set('name', $this->model->name);
        $this->apptemplate->set('last_name', $this->model->last_name);
        $this->apptemplate->set('code', $key);
        $this->apptemplate->set('site', $this->site);
        $this->apptemplate->set('ip', $_SERVER['REMOTE_ADDR']);
        $email->message = $this->apptemplate->fetch('mod_user/email_password_reset');
        if ($email->send()) {
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
            $this->apptemplate->set('code', $clean_code);
        }
        if ($this->_is_post_request()) {
            $this->_password_reset();
        }
        $d['title'] = $this->lang['mod_user']['password_reset']['title'];
        $this->apptemplate->set('d', $d);

        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->set('captcha_image', $this->captcha->image());

        $this->apptemplate->tpl('mod_user/password_reset');
    }

    private function _password_reset()
    {
        $this->val = Load::validator('mod_user.password_reset');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->set($key, Req::Post_Safe($key));
            }
            $this->apptemplate->set('errors', $this->val->errors);
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
                $this->_app_message($this->lang['mod_user']['password_reset']['success_message']);
            } else {
                $this->_app_emessage($this->lang['mod_user']['error']['system_error']);
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
        $this->apptemplate->set('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->set('captcha_image', $this->captcha->image());
        $this->apptemplate->tpl('mod_user/resend_activation');
    }

    // Resends Activation Code
    private function _resend_activation()
    {
        $this->val = Load::validator('mod_user.resend_activation');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->set($key, Req::Post_Safe($key));
            }
            $this->apptemplate->set('errors', $this->val->errors);
        } else {
            $this->activation = Load::model('mod_user.activation');
            $this->activation->getemail(Req::Post_Safe('email'))->gRo();
            if ($this->activation->getSize() === 0) {
                $this->_app_emessage($this->lang['mod_user']['resend_activation']['error']['no_activation_code_found']);
                return FALSE;
            }
            $this->activation->sync();
            if ($this->_email_at($this->activation->code)) {
                $this->_app_message($this->lang['mod_user']['resend_activation']['code_sent']);
            } else {
                $this->_app_emessage($this->lang['mod_user']['resend_activation']['error']['invalid_captcha']);
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
        $email->to = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->subject = $this->lang['mod_user']['register']['membership_activation'];
        $this->apptemplate->set('name', $this->model->name);
        $this->apptemplate->set('last_name', $this->model->last_name);
        $this->apptemplate->set('code', $key);
        $this->apptemplate->set('site', $this->site);
        $email->message = $this->apptemplate->fetch('mod_user/email_activation');
        if ($email->send()) {
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
            $this->apptemplate->set('code', Req::Url_Clear($code));
        }
        if (Req::is_Post('activation')) {
            $this->_activation();
        }
        $d['title'] = $this->lang['mod_user']['activation']['membership_activation'];
        $this->apptemplate->set('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->set('captcha_image', $this->captcha->image());

        $this->apptemplate->tpl('mod_user/activation');
    }

    // Membership Activation
    private function _activation()
    {
        $this->val = Load::validator('mod_user.activation');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->set($key, Req::Post_Safe($key));
            }
            $this->apptemplate->set('errors', $this->val->errors);
        } else {
            $this->m = Load::model('mod_user.activation');
            $this->m->Getcode(Req::Post_Safe('code'))->gRo()->sync();
            $this->u = Load::model('mod_user');
            $this->u->Getemail($this->m->email)->gRo()->sync();
            $this->u->status = 'y';
            if ($this->u->update('id')) {
                $this->m->delete();
                $this->_app_message($this->lang['mod_user']['activation']['success_activated']);
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
            $this->_app_emessage($this->lang['mod_user']['register']['registration_stopped']);
            $d['title'] = $this->lang['mod_user']['register']['membership_registration'];
            return FALSE;
        }

        $this->_activation = $d['activation'];
        $this->_group_id = $d['group_id'];

        if (Req::is_Post('register')) {
            $this->_register();
        }

        $d['title'] = $this->lang['mod_user']['register']['membership_registration'];
        $this->apptemplate->set('d', $d);
        $this->_prepare_register_view_data();
        $this->apptemplate->tpl('mod_user/register');
    }

    private function _load_register_flash_messages()
    {
        if ($this->session->get('success_message')) {
            $this->apptemplate->set('success_message', $this->session->get('success_message'));
            $this->session->remove('success_message');
        }

        if ($this->session->get('error_message')) {
            $this->apptemplate->set('error_message', $this->session->get('error_message'));
            $this->session->remove('error_message');
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
        $this->apptemplate->set('js_lang', json_encode($this->_get_register_js_lang()));
        $this->apptemplate->set('country_codes', $this->_get_country_codes());

        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->set('captcha_image', $this->captcha->image());
    }

    private function _get_register_js_lang()
    {
        return [
            'checking' => $this->lang['mod_user']['register']['js']['checking'] ?? '...',
            'username_available' => $this->lang['mod_user']['register']['js']['username_available'] ?? '',
            'username_exists' => $this->lang['mod_user']['register']['js']['username_exists'] ?? '',
            'username_short' => $this->lang['mod_user']['register']['js']['username_short'] ?? '',
            'username_long' => $this->lang['mod_user']['register']['js']['validation_username_long'] ?? '',
            'email_available' => $this->lang['mod_user']['register']['js']['email_available'] ?? '',
            'email_exists' => $this->lang['mod_user']['register']['js']['email_exists'] ?? '',
            'check_failed' => $this->lang['mod_user']['register']['js']['check_failed'] ?? '',
            'register_success' => $this->lang['mod_user']['register']['js']['register_success'] ?? '',
            'errors_title' => $this->lang['mod_user']['register']['js']['errors_title'] ?? '',
            'validation_required' => $this->lang['mod_user']['register']['js']['validation_required'] ?? '',
            'validation_required_name' => $this->lang['mod_user']['register']['js']['validation_required_name'] ?? '',
            'validation_required_surname' => $this->lang['mod_user']['register']['js']['validation_required_surname'] ?? '',
            'validation_required_username' => $this->lang['mod_user']['register']['js']['validation_required_username'] ?? '',
            'validation_username_short' => $this->lang['mod_user']['register']['js']['validation_username_short'] ?? '',
            'validation_username_long' => $this->lang['mod_user']['register']['js']['validation_username_long'] ?? '',
            'validation_required_email' => $this->lang['mod_user']['register']['js']['validation_required_email'] ?? '',
            'validation_invalid_email' => $this->lang['mod_user']['register']['js']['validation_invalid_email'] ?? '',
            'validation_required_password' => $this->lang['mod_user']['register']['js']['validation_required_password'] ?? '',
            'validation_password_short' => $this->lang['mod_user']['register']['js']['validation_password_short'] ?? '',
            'validation_password_long' => $this->lang['mod_user']['register']['js']['validation_password_long'] ?? '',
            'validation_confirm_password' => $this->lang['mod_user']['register']['js']['validation_confirm_password'] ?? '',
            'validation_required_phone' => $this->lang['mod_user']['register']['js']['validation_required_phone'] ?? '',
            'validation_required_captcha' => $this->lang['mod_user']['register']['js']['validation_required_captcha'] ?? '',
            'password_complexity_error' => $this->lang['mod_user']['error']['password_complexity_error'] ?? '',
            'password_match' => $this->lang['mod_user']['password_match']['success'] ?? '',
            'password_mismatch' => $this->lang['mod_user']['password_match']['error'] ?? '',
            'strength_weak' => $this->lang['mod_user']['password_strength']['weak'] ?? '',
            'strength_fair' => $this->lang['mod_user']['password_strength']['fair'] ?? '',
            'strength_good' => $this->lang['mod_user']['password_strength']['good'] ?? '',
            'strength_strong' => $this->lang['mod_user']['password_strength']['strong'] ?? '',
        ];
    }


    private function _register()
    {
        $this->val = Load::validator('mod_user.register');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->set($key, Req::Post_Safe($key));
            }
            $this->apptemplate->set('errors', $this->val->errors);
            return;
        }

        $this->apptemplate->set('Success', True);
        $this->model = $this->_build_registration_model();

        if (!$this->model->save()) {
            $this->apptemplate->set('error_message', $this->lang['mod_user']['database_error']);
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
        return $this->_get_combined_phone();
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
        Helper::jsRedirect($this->site['address'] . 'register?success=1', 0);
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

        $settingsList = $this->settings->all()->toArray();

        foreach ($settingsList as $val) {
            $d[$val['k']] = $val['v'];
        }
        // Let's pull the Member Group
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'parent_id', 'group_id');


        $status = $d['status'];
        $activation = $d['activation'];
        $check_user_login = $d['check_user_login'];
        $this->site['title'] = $this->lang['mod_user']['admin']['management'];
        $this->theme->tpl('mod_user/settings');
        $this->theme->set('site', $this->site);
        $this->theme->set('group', $group);
        $this->theme->set('status', $status);
        $this->theme->set('check_user_login', $check_user_login);
        $this->theme->set('d', $d);
        $this->theme->set('activation', $activation);
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
                        $this->_emessage($this->lang['mod_user']['database_error']);
                        return False;
                    }
                }
            }
        }
        $this->_message($this->lang['mod_user']['admin']['settings_updated']);
        return True;
    }


    public function panel($unused = null)
    {
        if ($this->check() === FALSE) {
            return FALSE;
        }

        if (func_num_args() > 0 && $unused !== null && $unused !== '') {
            Helper::redirect($this->site['saddress'] . 'mod_user/panel', 0);
            return FALSE;
        }

        Run::import('management', '_index');
        $this->_assert_panel_language_keys();
        $this->site['title'] = $this->lang['mod_user']['panel']['panel_title'] ?? 'Control Panel';

        if (!empty($_SESSION['group_id']) && $this->user instanceof user_model) {
            $this->user->_loadPermissions((int)$_SESSION['group_id']);
        }

        $this->theme->set('site', $this->site);
        $this->theme->set('panel_categories', $this->_build_panel_categories());
        $this->theme->tpl('mod_user/panel');
    }

    public function chat($unused = null)
    {
        if ($this->check() === FALSE) {
            return FALSE;
        }

        if (func_num_args() > 0 && $unused !== null && $unused !== '') {
            Helper::redirect($this->site['saddress'] . 'mod_user/chat', 0);
            return FALSE;
        }

        Run::import('management', '_index');
        $this->_assert_panel_language_keys();
        $this->site['title'] = $this->lang['mod_user']['agent']['title'] ?? '';

        // Fetch Gemini configuration via AgentRunner::resolveApiKey()
        require_once(lib_dir . 'AgentRunner.class.php');
        $geminiApiKey = AgentRunner::resolveApiKey();
        $chatCsrf = (string) $this->session->get('agent_chat_csrf');
        if (strlen($chatCsrf) !== 32) {
            $chatCsrf = bin2hex(random_bytes(16));
            $this->session->set('agent_chat_csrf', $chatCsrf);
        }
        $this->theme->set('agent_chat_csrf', $chatCsrf);

        $assetDir = root_dir . 'templates' . DS . 'admin' . DS . 'assets' . DS;
        $assetTime = max(
            (int) @filemtime($assetDir . 'js' . DS . 'mod_user_agent.js'),
            (int) @filemtime($assetDir . 'css' . DS . 'mod_user_agent.css')
        );
        $fallbackVersion = !empty($this->site['version']) ? (string) $this->site['version'] : '1.0.3.79';
        $this->theme->set('agent_asset_version', $assetTime > 0 ? (string) $assetTime : $fallbackVersion);

        $hasGemini = !empty($geminiApiKey);
        $this->theme->set('site', $this->site);
        $this->theme->set('has_gemini', $hasGemini);
        $activeModel = (string) $this->session->get('agent_active_model');
        if (empty($activeModel)) {
            $loggedInUserId = (int) $this->session->get('id');
            $activeModel = AgentRunner::getActiveModel($loggedInUserId);
        }
        $this->theme->set('gemini_model', $activeModel ?: 'gemini-3.8-flash');
        $this->theme->set('agent_chat_url', $this->site['saddress'] . 'mod_user/ajax_agent_chat');
        $this->theme->set('agent_stream_url', $this->site['saddress'] . 'mod_user/ajax_agent_stream');
        $this->theme->set('agent_csrf_url', $this->site['saddress'] . 'mod_user/ajax_agent_csrf');
        $this->theme->tpl('mod_user/chat');
    }

    /**
     * AJAX endpoint to re-sync or retrieve active CSRF token for the agent dialogue
     */
    public function ajax_agent_csrf()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed.'
            ]);
            exit;
        }

        $chatCsrf = (string) $this->session->get('agent_chat_csrf');
        if (strlen($chatCsrf) !== 32) {
            $chatCsrf = bin2hex(random_bytes(16));
            $this->session->set('agent_chat_csrf', $chatCsrf);
        }

        session_write_close();

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'csrf' => $chatCsrf
        ]);
        exit;
    }

    /**
     * AJAX endpoint for AI dialogue powered by Gemini (Synchronous / Fallback)
     */
    public function ajax_agent_chat()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed.'
            ]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode((string) $input, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        // 0.1: CSRF protection
        $csrfToken = (string) ($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? Req::Post_Safe('csrf')));
        $sessionCsrf = (string) $this->session->get('agent_chat_csrf');
        if (empty($sessionCsrf) || empty($csrfToken) || !hash_equals($sessionCsrf, $csrfToken)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'csrf_invalid',
                'error' => $this->lang['mod_user']['agent']['csrf_error'] ?? ''
            ]);
            exit;
        }

        $userId = (int) $this->session->get('id');
        $groupId = (int) $this->session->get('group_id');
        if ($groupId === 0 && isset($_SESSION['group_id'])) {
            $groupId = (int) $_SESSION['group_id'];
        }

        require_once(lib_dir . 'AgentRunner.class.php');

        // 0.2: Dedicated Clear Conversation Handler
        if (!empty($data['clear_history'])) {
            AgentRunner::clearChatHistory($userId);
            AgentRunner::clearPendingAction($userId);
            AgentRunner::deleteState($userId, 'model_pin', 'default');
            session_write_close();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $this->lang['mod_user']['agent']['history_cleared'] ?? ''
            ]);
            exit;
        }

        // 0.3: Cancel Pending Action Handler
        if (!empty($data['cancel_action_id'])) {
            AgentRunner::clearPendingAction($userId);
            session_write_close();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'cancelled' => true,
                'reply' => $this->lang['mod_user']['agent']['action_cancelled'] ?? ''
            ]);
            exit;
        }

        // 0.4: Per-User Chat Rate Limiting
        require_once(lib_dir . 'McpAuditLogger.class.php');
        if (!McpAuditLogger::checkRateLimit('agent_chat', 'user_' . $userId, 30, 'chat_request')) {
            session_write_close();
            header('HTTP/1.1 429 Too Many Requests');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['rate_limit_exceeded'] ?? ''
            ]);
            exit;
        }

        // 0.5: Single-flight concurrency lock with owner token and shutdown hook (§4 A6)
        $lockToken = AgentRunner::acquireLock($userId, 45);
        if (!$lockToken) {
            session_write_close();
            header('HTTP/1.1 429 Too Many Requests');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'concurrent_run',
                'error' => $this->lang['mod_user']['agent']['concurrent_run'] ?? ''
            ]);
            exit;
        }

        register_shutdown_function(function() use ($userId, $lockToken) {
            AgentRunner::releaseLock($userId, $lockToken);
        });

        // 0.6: Deduplication check (§4 A5)
        $requestId = trim((string) ($data['request_id'] ?? ''));
        if ($requestId !== '') {
            $cached = AgentRunner::getRequestResult($userId, $requestId);
            if ($cached !== null) {
                AgentRunner::releaseLock($userId, $lockToken);
                session_write_close();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($cached);
                exit;
            }
        }

        $activeLang = $this->site['lang'] ?? 'en';
        $preferredModel = AgentRunner::getActiveModel($userId) ?: (string) $this->session->get('agent_active_model');
        session_write_close();

        try {
            $runner = new AgentRunner($userId, $groupId, $activeLang, null, 'blocking', $preferredModel ?: null);
            $runner->setLockToken($lockToken);

            // Resume Server-Side Confirmed Action
            if (!empty($data['confirm_action_id'])) {
                $confirmId = (string) $data['confirm_action_id'];
                $pendingAction = AgentRunner::getPendingAction($userId, $confirmId) ?: AgentRunner::getPendingAction($userId);
                if (!is_array($pendingAction) || ($pendingAction['id'] ?? '') !== $confirmId || ($pendingAction['user_id'] ?? 0) !== $userId) {
                    header('HTTP/1.1 400 Bad Request');
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error' => $this->lang['mod_user']['agent']['action_expired'] ?? ''
                    ]);
                    return;
                }

                AgentRunner::clearPendingAction($userId);
                set_time_limit(90);

                McpAuditLogger::log([
                    'caller_type' => 'agent_chat',
                    'caller_id' => 'user_' . $userId,
                    'user_id' => $userId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'tool_name' => 'chat_request',
                    'tokens_used' => 0,
                    'status_code' => 200,
                    'is_error' => 0,
                ]);

                $result = $runner->resumeConfirmation($pendingAction);
                if (!empty($result['success']) && !empty($result['reply'])) {
                    $serverHistory = AgentRunner::getChatHistory($userId);
                    $serverHistory[] = ['role' => 'model', 'text' => $result['reply']];
                    AgentRunner::setChatHistory($userId, $serverHistory);
                    if (!empty($result['model'])) {
                        AgentRunner::setActiveModel($userId, $result['model']);
                    }
                }
                if ($requestId !== '') {
                    AgentRunner::recordRequestId($userId, $requestId, $result);
                }

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($result);
                return;
            }

            // New Prompt Turn
            $prompt = trim((string) ($data['prompt'] ?? Req::Post_Safe('prompt')));
            if ($prompt === '') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => $this->lang['mod_user']['agent']['empty_error'] ?? ''
                ]);
                return;
            }

            set_time_limit(90);
            McpAuditLogger::log([
                'caller_type' => 'agent_chat',
                'caller_id' => 'user_' . $userId,
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'tool_name' => 'chat_request',
                'tokens_used' => 0,
                'status_code' => 200,
                'is_error' => 0,
            ]);

            $serverHistory = AgentRunner::getChatHistory($userId);
            $result = $runner->execute($prompt, $serverHistory, $data);

            if (!empty($result['success']) && !empty($result['reply'])) {
                $serverHistory[] = ['role' => 'user', 'text' => $prompt];
                $serverHistory[] = ['role' => 'model', 'text' => $result['reply']];
                AgentRunner::setChatHistory($userId, $serverHistory);
                if (!empty($result['model'])) {
                    AgentRunner::setActiveModel($userId, $result['model']);
                }
            }
            if ($requestId !== '') {
                AgentRunner::recordRequestId($userId, $requestId, $result);
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            return;
        } finally {
            AgentRunner::releaseLock($userId, $lockToken);
        }
    }

    /**
     * SSE Streaming endpoint for AI dialogue powered by Gemini (§4 Workstream B)
     */
    public function ajax_agent_stream()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed.'
            ]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode((string) $input, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        // CSRF protection
        $csrfToken = (string) ($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? Req::Post_Safe('csrf')));
        $sessionCsrf = (string) $this->session->get('agent_chat_csrf');
        if (empty($sessionCsrf) || empty($csrfToken) || !hash_equals($sessionCsrf, $csrfToken)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'csrf_invalid',
                'error' => $this->lang['mod_user']['agent']['csrf_error'] ?? ''
            ]);
            exit;
        }

        $userId = (int) $this->session->get('id');
        $groupId = (int) $this->session->get('group_id');
        if ($groupId === 0 && isset($_SESSION['group_id'])) {
            $groupId = (int) $_SESSION['group_id'];
        }

        require_once(lib_dir . 'AgentRunner.class.php');

        // Per-User Chat Rate Limiting
        require_once(lib_dir . 'McpAuditLogger.class.php');
        if (!McpAuditLogger::checkRateLimit('agent_chat', 'user_' . $userId, 30, 'chat_request')) {
            session_write_close();
            header('HTTP/1.1 429 Too Many Requests');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['rate_limit_exceeded'] ?? ''
            ]);
            exit;
        }

        // Single-flight concurrency lock with owner token and shutdown hook (§4 A6)
        $lockToken = AgentRunner::acquireLock($userId, 45);
        if (!$lockToken) {
            session_write_close();
            header('HTTP/1.1 429 Too Many Requests');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'concurrent_run',
                'error' => $this->lang['mod_user']['agent']['concurrent_run'] ?? ''
            ]);
            exit;
        }

        register_shutdown_function(function() use ($userId, $lockToken) {
            AgentRunner::releaseLock($userId, $lockToken);
        });

        // Deduplication check (§4 A5)
        $requestId = trim((string) ($data['request_id'] ?? ''));
        if ($requestId !== '') {
            $cached = AgentRunner::getRequestResult($userId, $requestId);
            if ($cached !== null) {
                AgentRunner::releaseLock($userId, $lockToken);
                session_write_close();

                while (ob_get_level()) { @ob_end_flush(); }
                @ini_set('zlib.output_compression', '0');
                ob_implicit_flush(true);
                header('Content-Type: text/event-stream; charset=utf-8');
                header('Cache-Control: no-cache, no-transform');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                echo ": connected\n\n";
                echo "data: " . json_encode(array_merge(['seq' => 1, 'type' => 'done'], $cached), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                flush();
                exit;
            }
        }

        $activeLang = $this->site['lang'] ?? 'en';
        $preferredModel = AgentRunner::getActiveModel($userId) ?: (string) $this->session->get('agent_active_model');
        session_write_close();

        // Prepare SSE stream environment (§4 B1)
        while (ob_get_level()) {
            @ob_end_flush();
        }
        @ini_set('zlib.output_compression', '0');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        ob_implicit_flush(true);
        ignore_user_abort(true);
        set_time_limit(270);

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        echo ": connected\n\n";
        flush();

        $sequenceNumber = 0;
        $eventSink = function (array $event) use (&$sequenceNumber) {
            $sequenceNumber++;
            $payload = json_encode(array_merge(['seq' => $sequenceNumber], $event), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo "data: {$payload}\n\n";
            flush();
        };

        try {
            $runner = new AgentRunner($userId, $groupId, $activeLang, null, 'stream', $preferredModel ?: null);
            $runner->setEventSink($eventSink);
            $runner->setLockToken($lockToken);

            // Resume Confirmation Action
            if (!empty($data['confirm_action_id'])) {
                $confirmId = (string) $data['confirm_action_id'];
                $pendingAction = AgentRunner::getPendingAction($userId, $confirmId) ?: AgentRunner::getPendingAction($userId);
                if (!is_array($pendingAction) || ($pendingAction['id'] ?? '') !== $confirmId || ($pendingAction['user_id'] ?? 0) !== $userId) {
                    $eventSink([
                        'type' => 'error',
                        'error' => $this->lang['mod_user']['agent']['action_expired'] ?? ''
                    ]);
                    return;
                }

                AgentRunner::clearPendingAction($userId);
                McpAuditLogger::log([
                    'caller_type' => 'agent_chat',
                    'caller_id' => 'user_' . $userId,
                    'user_id' => $userId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'tool_name' => 'chat_request',
                    'tokens_used' => 0,
                    'status_code' => 200,
                    'is_error' => 0,
                ]);

                $result = $runner->resumeConfirmation($pendingAction);
                if (!empty($result['success']) && !empty($result['reply'])) {
                    $serverHistory = AgentRunner::getChatHistory($userId);
                    $serverHistory[] = ['role' => 'model', 'text' => $result['reply']];
                    AgentRunner::setChatHistory($userId, $serverHistory);
                    if (!empty($result['model'])) {
                        AgentRunner::setActiveModel($userId, $result['model']);
                    }
                }
                if ($requestId !== '') {
                    AgentRunner::recordRequestId($userId, $requestId, $result);
                }
                return;
            }

            // New Prompt Turn
            $prompt = trim((string) ($data['prompt'] ?? Req::Post_Safe('prompt')));
            if ($prompt === '') {
                $eventSink([
                    'type' => 'error',
                    'error' => $this->lang['mod_user']['agent']['empty_error'] ?? ''
                ]);
                return;
            }

            McpAuditLogger::log([
                'caller_type' => 'agent_chat',
                'caller_id' => 'user_' . $userId,
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'tool_name' => 'chat_request',
                'tokens_used' => 0,
                'status_code' => 200,
                'is_error' => 0,
            ]);

            $serverHistory = AgentRunner::getChatHistory($userId);
            $result = $runner->execute($prompt, $serverHistory, $data);

            if (!empty($result['success']) && !empty($result['reply'])) {
                $serverHistory[] = ['role' => 'user', 'text' => $prompt];
                $serverHistory[] = ['role' => 'model', 'text' => $result['reply']];
                AgentRunner::setChatHistory($userId, $serverHistory);
                if (!empty($result['model'])) {
                    AgentRunner::setActiveModel($userId, $result['model']);
                }
            }
            if ($requestId !== '') {
                AgentRunner::recordRequestId($userId, $requestId, $result);
            }
        } catch (\Throwable $t) {
            $eventSink([
                'type' => 'error',
                'error' => 'Runtime error: ' . $t->getMessage()
            ]);
        } finally {
            AgentRunner::releaseLock($userId, $lockToken);
        }
        exit;
    }

    /**
     * Starts an asynchronous background agent job
     */
    public function ajax_agent_job_start()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed.'
            ]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode((string) $input, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        // CSRF protection
        $csrfToken = (string) ($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? Req::Post_Safe('csrf')));
        $sessionCsrf = (string) $this->session->get('agent_chat_csrf');
        if (empty($sessionCsrf) || empty($csrfToken) || !hash_equals($sessionCsrf, $csrfToken)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'csrf_invalid',
                'error' => $this->lang['mod_user']['agent']['csrf_error'] ?? ''
            ]);
            exit;
        }

        $userId = (int) $this->session->get('id');
        $groupId = (int) $this->session->get('group_id');
        if ($groupId === 0 && isset($_SESSION['group_id'])) {
            $groupId = (int) $_SESSION['group_id'];
        }

        require_once(lib_dir . 'McpAuditLogger.class.php');
        if (!McpAuditLogger::checkRateLimit('agent_chat', 'user_' . $userId, 30, 'chat_request')) {
            session_write_close();
            header('HTTP/1.1 429 Too Many Requests');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['rate_limit_exceeded'] ?? ''
            ]);
            exit;
        }

        $prompt = trim((string) ($data['prompt'] ?? Req::Post_Safe('prompt')));
        if ($prompt === '') {
            session_write_close();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['empty_error'] ?? ''
            ]);
            exit;
        }

        session_write_close();

        McpAuditLogger::log([
            'caller_type' => 'agent_chat',
            'caller_id' => 'user_' . $userId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'tool_name' => 'chat_request',
            'tokens_used' => 0,
            'status_code' => 200,
            'is_error' => 0,
        ]);

        require_once(lib_dir . 'AgentRunner.class.php');
        $lang = $this->site['lang'] ?? 'en';
        $jobId = AgentRunner::createJob($userId, $groupId, $lang, $prompt);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'status' => 'queued',
        ]);
        exit;
    }

    /**
     * Polls the status and result of a background agent job
     */
    public function ajax_agent_job_status()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        $jobId = trim((string) ($_GET['job_id'] ?? ($_POST['job_id'] ?? Req::Post_Safe('job_id'))));
        if ($jobId === '') {
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Missing job_id.'
            ]);
            exit;
        }

        $userId = (int) $this->session->get('id');
        $groupId = (int) $this->session->get('group_id');
        if ($groupId === 0 && isset($_SESSION['group_id'])) {
            $groupId = (int) $_SESSION['group_id'];
        }
        session_write_close();

        require_once(lib_dir . 'AgentRunner.class.php');
        $job = AgentRunner::getJob($jobId);
        if (!$job) {
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Job not found.'
            ]);
            exit;
        }

        // Ownership validation: owner or root admin
        if ((int) $job['user_id'] !== $userId && $groupId !== 1) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Access denied.'
            ]);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'job_id' => $job['job_id'],
            'status' => $job['status'],
            'stop_reason' => $job['stop_reason'] ?? null,
            'current_turn' => (int) ($job['current_turn'] ?? 0),
            'progress_text' => $job['progress_text'] ?? null,
            'result' => $job['result'] ?? null,
            'tokens_used' => (int) ($job['tokens_used'] ?? 0),
            'created_at' => $job['created_at'],
            'updated_at' => $job['updated_at'],
        ]);
        exit;
    }

    /**
     * Cancels an ongoing background agent job
     */
    public function ajax_agent_job_cancel()
    {
        if ($this->user->checklogin() === false) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $this->lang['mod_user']['agent']['unauthorized'] ?? ''
            ]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode((string) $input, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $csrfToken = (string) ($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? Req::Post_Safe('csrf')));
        $sessionCsrf = (string) $this->session->get('agent_chat_csrf');
        if (empty($sessionCsrf) || empty($csrfToken) || !hash_equals($sessionCsrf, $csrfToken)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 'csrf_invalid',
                'error' => $this->lang['mod_user']['agent']['csrf_error'] ?? ''
            ]);
            exit;
        }

        $jobId = trim((string) ($data['job_id'] ?? ''));
        if ($jobId === '') {
            session_write_close();
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Missing job_id.'
            ]);
            exit;
        }

        $userId = (int) $this->session->get('id');
        session_write_close();

        require_once(lib_dir . 'AgentRunner.class.php');
        $cancelled = AgentRunner::cancelJob($jobId, $userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $cancelled,
            'job_id' => $jobId,
            'cancelled' => $cancelled,
        ]);
        exit;
    }

    private function _panel_module($module, $permAction, $title, $icon, array $links, array $extraKeywords = [])
    {
        if (Role::hasPermission($module, $permAction) !== TRUE) {
            return null;
        }

        $labels = $this->lang['mod_user']['panel'];
        $usableLinks = [];
        foreach ($links as $link) {
            if ($link !== null) {
                $usableLinks[] = $link;
            }
        }

        if ($usableLinks === []) {
            return null;
        }

        $searchParts = [$title];
        foreach ($usableLinks as $link) {
            $searchParts[] = $link['label'];
        }
        foreach ($extraKeywords as $keyword) {
            if (!empty($keyword)) {
                $searchParts[] = $keyword;
            }
        }

        return [
            'title' => $title,
            'icon' => $icon,
            'links' => $usableLinks,
            'search' => $this->_panel_search(implode(' ', $searchParts)),
        ];
    }

    private function _panel_link($module, $permAction, $label, $url)
    {
        if (Role::hasPermission($module, $permAction) !== TRUE) {
            return null;
        }

        return ['label' => $label, 'url' => $url];
    }

    private function _panel_action($module, $permAction, $label, $modalAction)
    {
        if (Role::hasPermission($module, $permAction) !== TRUE) {
            return null;
        }

        return ['label' => $label, 'action' => $modalAction];
    }

    private function _panel_search(string $text): string
    {
        return str_replace(["\u{0307}", 'ı'], ['', 'i'], mb_strtolower($text, 'UTF-8'));
    }

    private function _build_panel_categories()
    {
        $management_lang = Load::lang('management');
        $settings_lang = Load::lang('mod_settings');
        
        $seo_lang = Load::lang('mod_seo');
        $pm_lang = Load::lang('mod_user_pm');
        $faq_lang = Load::lang('mod_faq');
        $gallery_lang = Load::lang('mod_gallery');
        $attachment_lang = Load::lang('mod_attachment');
        $menu_lang = Load::lang('mod_menu');
        
        $management = $management_lang['management'];
        $base = $this->site['saddress'];
        $p = $this->lang['mod_user']['panel'];

        $menuSlots = $menu_lang['mod_menu']['slots'] ?? [];
        $headerMenuLabel = $menuSlots['header'] ?? 'Header Menu';
        $footerMenuLabel = $menuSlots['footer'] ?? 'Footer Menu';
        $actionMenuLabel = $menuSlots['action'] ?? 'Action Menu';

        $list = fn ($mod, $perm, $url) => $this->_panel_link($mod, $perm, $p['link_list'], $url);
        $add = fn ($mod, $perm, $url) => $this->_panel_link($mod, $perm, Load::lang($mod)[$mod]['add'] ?? $p['link_add'], $url);
        $cats = fn ($mod, $perm, $url) => $this->_panel_link($mod, $perm, $p['link_categories'], $url);

        $mod = function ($module, $permAction, $title, $icon, array $links, array $extraKeywords = []) {
            return $this->_panel_module($module, $permAction, $title, $icon, $links, $extraKeywords);
        };

        $categories = [
            [
                'label' => $management['content_and_page'],
                'icon' => 'bi bi-rocket-takeoff-fill',
                'modules' => array_filter([
                    $mod('mod_news', 'list', $management['news_management'], 'bi bi-newspaper', [
                        $list('mod_news', 'list', $base . 'mod_news/list'),
                        $add('mod_news', 'add', $base . 'mod_news/add'),
                        $cats('mod_news_category', 'list', $base . 'mod_news_category/list'),
                    ]),
                    $mod('mod_publication', 'list', $management['publication_management'], 'bi bi-journal-richtext', [
                        $list('mod_publication', 'list', $base . 'mod_publication/list'),
                        $add('mod_publication', 'add', $base . 'mod_publication/add'),
                        $cats('mod_publication_category', 'list', $base . 'mod_publication_category/list'),
                    ]),
                    $mod('mod_page', 'list', $management['page_management'], 'bi bi-file-richtext', [
                        $list('mod_page', 'list', $base . 'mod_page/list'),
                        $add('mod_page', 'add', $base . 'mod_page/add'),
                    ]),
                    $mod('mod_document', 'list', $management['file_management'], 'bi bi-folder-fill', [
                        $list('mod_document', 'list', $base . 'mod_document/list'),
                        $add('mod_document', 'add', $base . 'mod_document/add'),
                    ]),
                    $mod('mod_survey', 'list', $management['survey_management'], 'bi bi-ui-checks-grid', [
                        $list('mod_survey', 'list', $base . 'mod_survey/list'),
                        $add('mod_survey', 'add', $base . 'mod_survey/add'),
                    ]),
                    $mod('mod_faq', 'list', $faq_lang['mod_faq']['admin']['faq_management'], 'bi bi-patch-question-fill', [
                        $list('mod_faq', 'list', $base . 'mod_faq/list'),
                    ]),
                    $mod('mod_faq_mod', 'list', Load::lang('mod_faq_mod')['mod_faq_mod']['list'], 'bi bi-patch-question', [
                        $list('mod_faq_mod', 'list', $base . 'mod_faq_mod/list'),
                        $add('mod_faq_mod', 'add', $base . 'mod_faq_mod/add'),
                    ]),
                    $mod('mod_menu', 'list', $management['menu_management'], 'bi bi-list', [
                        $list('mod_menu', 'list', $base . 'mod_menu/list'),
                        $add('mod_menu', 'add', $base . 'mod_menu/add'),
                        $this->_panel_link('mod_menu', 'list', $headerMenuLabel, $base . 'mod_menu/list#header'),
                        $this->_panel_link('mod_menu', 'list', $footerMenuLabel, $base . 'mod_menu/list#footer'),
                        $this->_panel_link('mod_menu', 'list', $actionMenuLabel, $base . 'mod_menu/list#action'),
                    ], ['menu', 'menü', 'pesek', 'pêşek', 'navigasyon', 'navigation', 'navbar', 'header', 'footer', 'ust menu', 'alt menu', 'eylem menusu', 'slot']),
                    $mod('mod_redirect', 'list', $management['redirect_management'], 'bi bi-arrow-return-right', [
                        $list('mod_redirect', 'list', $base . 'mod_redirect/list'),
                    ]),
                ]),
            ],
            [
                'label' => $management['video_and_images'],
                'icon' => 'bi bi-camera-video-fill',
                'modules' => array_filter([
                    $mod('mod_video', 'list', $management['videos'], 'bi bi-camera-reels-fill', [
                        $list('mod_video', 'list', $base . 'mod_video/list'),
                        $add('mod_video', 'add', $base . 'mod_video/add'),
                    ]),
                    $mod('mod_video_embed', 'list', $management['remote_video_category'], 'bi bi-collection-play-fill', [
                        $list('mod_video_embed', 'list', $base . 'mod_video_embed/list'),
                        $add('mod_video_embed', 'add', $base . 'mod_video_embed/add'),
                        $cats('mod_video_embed_category', 'list', $base . 'mod_video_embed_category/list'),
                    ]),
                    $mod('mod_slider', 'list', $management['headline_management'], 'bi bi-card-image', [
                        $list('mod_slider', 'list', $base . 'mod_slider/list'),
                        $add('mod_slider', 'add', $base . 'mod_slider/add'),
                    ]),
                    $mod('mod_headline', 'list', $management['full_headline_management'], 'bi bi-layout-text-window-reverse', [
                        $list('mod_headline', 'list', $base . 'mod_headline/list'),
                        $add('mod_headline', 'add', $base . 'mod_headline/add'),
                    ]),
                    $mod('mod_imagecategories', 'list', $management['image_gallery'], 'bi bi-image-fill', [
                        $list('mod_imagecategories', 'list', $base . 'mod_imagecategories/list'),
                    ]),
                    $mod('mod_gallery_mod', 'list', $management['gallery_modules'], 'bi bi-images', [
                        $list('mod_gallery_mod', 'list', $base . 'mod_gallery_mod/list'),
                    ]),
                    $mod('mod_attachment_mod', 'list', $management['file_modules'], 'bi bi-paperclip', [
                        $list('mod_attachment_mod', 'list', $base . 'mod_attachment_mod/list'),
                    ]),
                    $mod('mod_gallery', 'list', $gallery_lang['mod_gallery']['admin']['title'], 'bi bi-collection-fill', [
                        $list('mod_gallery', 'list', $base . 'mod_gallery/list'),
                    ]),
                    $mod('mod_attachment', 'list', $attachment_lang['mod_attachment']['admin']['file_management'], 'bi bi-file-earmark-zip-fill', [
                        $list('mod_attachment', 'list', $base . 'mod_attachment/list'),
                    ]),
                ]),
            ],
            [
                'label' => $management['announcement_management'],
                'icon' => 'bi bi-megaphone-fill',
                'modules' => array_filter([
                    $mod('mod_announcements', 'list', $management['announcement_management'], 'bi bi-volume-up-fill', [
                        $list('mod_announcements', 'list', $base . 'mod_announcements/list'),
                        $add('mod_announcements', 'add', $base . 'mod_announcements/add'),
                    ]),
                    $mod('mod_popup', 'list', $management['popup_announcements'], 'bi bi-layout-wtf', [
                        $list('mod_popup', 'list', $base . 'mod_popup/list'),
                        $add('mod_popup', 'add', $base . 'mod_popup/add'),
                    ]),
                    $mod('mod_newsletter', 'list', $management['newsletter_management'], 'bi bi-envelope-at-fill', [
                        $list('mod_newsletter', 'list', $base . 'mod_newsletter/list'),
                        $add('mod_newsletter', 'add', $base . 'mod_newsletter/add'),
                    ]),
                ]),
            ],
            [
                'label' => $management['membership'],
                'icon' => 'bi bi-people-fill',
                'modules' => array_filter([
                    $mod('mod_user', 'list', $management['members'], 'bi bi-person-gear', [
                        $list('mod_user', 'list', $base . 'mod_user/list'),
                        $add('mod_user', 'add', $base . 'mod_user/add'),
                    ]),
                    $mod('mod_user_group', 'list', $management['user_groups'], 'bi bi-diagram-2-fill', [
                        $list('mod_user_group', 'list', $base . 'mod_user_group/list'),
                        $add('mod_user_group', 'add', $base . 'mod_user_group/add'),
                    ]),
                    $mod('mod_user', 'acl', $management['user_permission_management_acl'], 'bi bi-shield-lock', [
                        $this->_panel_link('mod_acl', 'list', $p['link_list'], $base . 'mod_acl/list'),
                        $this->_panel_link('mod_user', 'acl', $management['acl'], $base . 'mod_user/acl'),
                    ]),
                    [
                        'title' => $pm_lang['mod_user_pm']['inbox_link_label'],
                        'icon' => 'bi bi-envelope-paper',
                        'links' => array_values(array_filter([
                            $this->_panel_link('mod_user_pm', 'send', $pm_lang['mod_user_pm']['inbox_title'], $base . 'mod_user_pm/inbox'),
                            $this->_panel_link('mod_user_pm', 'send', $pm_lang['mod_user_pm']['sent_title'], $base . 'mod_user_pm/sent'),
                            $this->_panel_link('mod_user_pm', 'send', $pm_lang['mod_user_pm']['new_message_action'], $base . 'mod_user_pm/send'),
                        ])),
                        'search' => $this->_panel_search($pm_lang['mod_user_pm']['inbox_link_label'] . ' ' . $pm_lang['mod_user_pm']['inbox_title'] . ' ' . $pm_lang['mod_user_pm']['sent_title'] . ' ' . $pm_lang['mod_user_pm']['new_message_action'] . ' ' . $pm_lang['mod_user_pm']['message_label']),
                    ],
                    [
                        'title' => $management['profile'],
                        'icon' => 'bi bi-person-circle',
                        'links' => [
                            ['label' => $management['edit_profile_menu'], 'url' => $base . 'mod_user/info'],
                            ['label' => $management['change_password_menu'], 'url' => $base . 'mod_user/change_password'],
                        ],
                        'search' => $this->_panel_search($management['profile'] . ' ' . $management['edit_profile_menu'] . ' ' . $management['change_password_menu']),
                    ],
                ]),
            ],
            [
                'label' => $management['system_tools'],
                'icon' => 'bi bi-gear-fill',
                'modules' => array_filter([
                    $mod('mod_settings', 'edit', $settings_lang['mod_settings']['site_settings_menu_title'], 'bi bi-sliders', [
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['site_profile_title'], $base . 'mod_settings/edit'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['legal_info_title'], $base . 'mod_settings/legal_info'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['cookie_policy_title'], $base . 'mod_settings/cookie_policy'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['working_hours_title'] ?? 'Working Hours & Closures', $base . 'mod_settings/working_hours'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['affiliations_title'] ?? 'Affiliations & Partnerships', $base . 'mod_settings/affiliations'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['social'], $base . 'mod_settings/social_media'),
                        $this->_panel_link('mod_settings', 'edit', $settings_lang['mod_settings']['google_settings'], $base . 'mod_settings/google_settings'),
                        $this->_panel_action('mod_settings', 'edit', $settings_lang['mod_settings']['site_status_menu_title'], 'open-site-status'),
                        $this->_panel_action('mod_settings', 'edit', $settings_lang['mod_settings']['stats_title'], 'open-stats'),
                        $this->_panel_action('mod_settings', 'edit', $settings_lang['mod_settings']['site_settings_menu_title'], 'open-site-settings-menu'),
                        $this->_panel_action('mod_settings', 'edit', $settings_lang['mod_settings']['theme_management_title'], 'open-theme-management'),
                        $this->_panel_action('mod_settings', 'edit', $settings_lang['mod_settings']['logo_icon_management_title'], 'open-logo-icon-management'),
                    ]),
                    $mod('mod_settings', 'edit', $settings_lang['mod_settings']['affiliations_title'] ?? 'Affiliations & Partnerships', 'bi bi-building-check', [
                        $this->_panel_link('mod_settings', 'edit', $p['link_list'], $base . 'mod_settings/affiliations'),
                    ], ['affiliation', 'affiliations', 'kurum', 'baro', 'akreditasyon', 'baglanti', 'sazi', 'hevkari', 'meb', 'bakanlik', 'partner', 'oda', 'birlik', 'adliye', 'adalet']),
                    $mod('mod_settings', 'edit', $settings_lang['mod_settings']['working_hours_title'] ?? 'Working Hours & Closures', 'bi bi-clock-history', [
                        $this->_panel_link('mod_settings', 'edit', $p['link_list'], $base . 'mod_settings/working_hours'),
                    ], ['saat', 'calisma saatleri', 'working hours', 'saeten xebate', 'tatil', 'closures', 'betlane', 'mesai', 'tatiller']),
                    $mod('mod_seo', 'list', $seo_lang['mod_seo']['list'], 'bi bi-graph-up-arrow', [
                        $this->_panel_link('mod_seo', 'list', $p['link_list'], $base . 'mod_seo/index'),
                    ]),
                    $mod('mod_settings', 'edit', $management['language_management_menu'], 'bi bi-translate', [
                        $this->_panel_link('mod_settings', 'edit', $p['link_list'], $base . 'lang/panel'),
                    ]),
                    $mod('mod_mcp', 'list', $management['mcp_api_keys'], 'bi bi-plug-fill', [
                        $list('mod_mcp', 'list', $base . 'mod_mcp/list'),
                    ], ['mcp', 'api', 'key', 'keys', 'mifte', 'anahtar', 'token', 'ai', 'gemini', 'claude', 'antigravity']),
                ]),
            ],
        ];

        foreach ($categories as $index => $category) {
            $category['modules'] = array_values(array_filter($category['modules'], fn ($module) => !empty($module['links'])));
            if ($category['modules'] === []) {
                unset($categories[$index]);
                continue;
            }
            $categories[$index]['modules'] = $category['modules'];
            $categories[$index]['search'] = $this->_panel_search($category['label']);
        }

        return array_values($categories);
    }

    private function _assert_panel_language_keys()
    {
        $management_lang = Load::lang('management');
        $video_embed_lang = Load::lang('mod_video_embed');
        $slider_lang = Load::lang('mod_slider');
        $video_lang = Load::lang('mod_video');
        $gallery_lang = Load::lang('mod_gallery');
        $settings_lang = Load::lang('mod_settings');

        $required_keys = [
            [$this->lang, ['mod_user', 'panel', 'panel_title']],
            [$this->lang, ['mod_user', 'panel', 'home_title']],
            [$this->lang, ['mod_user', 'panel', 'admin_brand']],
            [$this->lang, ['mod_user', 'panel', 'welcome_main']],
            [$this->lang, ['mod_user', 'panel', 'recent_workspace']],
            [$this->lang, ['mod_user', 'panel', 'workspace_nav_media']],
            [$this->lang, ['mod_user', 'panel', 'workspace_nav_more']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_title']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_copy']],
            [$this->lang, ['mod_user', 'panel', 'more_sheet_open_drawer']],
            [$this->lang, ['mod_user', 'panel', 'link_list']],
            [$this->lang, ['mod_user', 'panel', 'link_add']],
            [$this->lang, ['mod_user', 'panel', 'link_categories']],
            [$this->lang, ['mod_user', 'panel', 'clear_search']],
            [$management_lang, ['management', 'membership']],
            [$management_lang, ['management', 'content_workspace']],
            [$management_lang, ['management', 'content_and_page']],
            [$management_lang, ['management', 'content_management']],
            [$management_lang, ['management', 'news_management']],
            [$management_lang, ['management', 'publication_management']],
            [$management_lang, ['management', 'system_tools']],
            [$management_lang, ['management', 'members']],
            [$management_lang, ['management', 'user_groups']],
            [$management_lang, ['management', 'membership_setting']],
            [$management_lang, ['management', 'user_permission_management_acl']],
            [$management_lang, ['management', 'acl']],
            [$management_lang, ['management', 'page_management']],
            [$management_lang, ['management', 'language_management_menu']],
            [$management_lang, ['management', 'mcp_api_keys']],
            [$management_lang, ['management', 'settings']],
            [$management_lang, ['management', 'announcement_management']],
            [$video_embed_lang, ['mod_video_embed', 'list']],
            [$video_lang, ['mod_video', 'video_management']],
            [$gallery_lang, ['mod_gallery', 'admin', 'title']],
            [$settings_lang, ['mod_settings', 'stats_title']],
            [$settings_lang, ['mod_settings', 'site_settings_menu_title']],
            [$settings_lang, ['mod_settings', 'site_profile_title']],
            [$settings_lang, ['mod_settings', 'site_profile_description']],
            [$settings_lang, ['mod_settings', 'legal_info_title']],
            [$settings_lang, ['mod_settings', 'legal_info_description']],
            [$settings_lang, ['mod_settings', 'theme_management_title']],
            [$settings_lang, ['mod_settings', 'theme_select_label']],
            [$settings_lang, ['mod_settings', 'google_settings']],
            [$this->lang, ['mod_user', 'fab', 'add_content']],
            [$this->lang, ['mod_user', 'fab', 'add_page']],
            [$this->lang, ['mod_user', 'fab', 'add_publication']],
            [$this->lang, ['mod_user', 'fab', 'add_video']],
            [$this->lang, ['mod_user', 'fab', 'add_announcement']],
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

        $site = Registry::get('site_config');
        $site['title'] = $error_title;

        $this->theme->lang($management_lang);
        $this->theme->setGlobal('site', $site);
        $this->theme->setGlobal('error_title', $error_title);
        $this->theme->setGlobal('error_headline', $error_title);
        $this->theme->setGlobal('error_heading', $key_path);
        $this->theme->setGlobal('error_description', '');
        $this->theme->setGlobal('hide_navbar', true);
        $this->theme->setGlobal('hide_search', true);
        $this->theme->setGlobal('show_navbar', false);
        $this->theme->setGlobal('show_filter_sheet', false);
        $this->theme->setGlobal('show_extra_sheets', false);
        $this->theme->setGlobal('show_wizards', false);
        $this->theme->tpl('pages/route_error');
        $this->theme->output();
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
        
        $this->theme->set('site', $this->site);
        $this->theme->set('group', $group);
        
        // Pass country codes
        $this->theme->set('country_codes', $this->_get_country_codes());
        $this->theme->set('social_platforms', $this->_get_social_platforms());
        
        // Pass all data variables to template
        foreach($d as $k => $v) {
            $this->theme->set($k, $v);
        }
        
        $this->theme->tpl('mod_user/add');
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
            $this->theme->set('errors', $this->val->errors);
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
                    $this->theme->set('errors', [$image->error]);
                    return FALSE;
                }
            } else {
                $u->image = ''; // Default empty string if no image uploaded
            }

            if ($u->save(true)) {
                if ($this->_save_social_links($u->id, $_POST['social_links'] ?? []) === false) {
                    $db->rollback();
                    $this->theme->set('errors', [$this->lang['mod_user']['database_error']]);
                    return FALSE;
                }

                $db->commit();
                $this->_message($this->lang['mod_user']['admin']['added']);
                $d = [
                    'username' => '', 'password' => '', 'email' => '',
                    'name' => '', 'last_name' => '', 'phone' => '', 'city' => '',
                    'blog_name' => '', 'about' => '', 'status' => 'y',
                    'social_links' => $this->_blank_social_links()
                ];
            } else {
                $db->rollback();
                $this->theme->set('errors', [$this->lang['mod_user']['database_error']]);
            }
        }
    }

    public function ajax_get_info()
    {
        $_GET['ajax'] = '1';
        return $this->info();
    }

    public function ajax_get_change_password()
    {
        $_GET['ajax'] = '1';
        return $this->change_password();
    }

    // Change My Password
    public function change_password()
    {
        // Is Member?
        if ($this->check() === FALSE) {
            return FALSE;
        }

        if (!$this->_is_ajax_request()) {
            Helper::redirect($this->site['saddress'] . 'mod_user/panel?open_modal=change_password');
            return FALSE;
        }

        Run::import('management', '_index');

        if (Req::is_Post('edit')) {
            $this->_change_password();
        }

        $this->site['title'] = $this->lang['mod_user']['profile_menu']['change_password'];
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());
        $this->theme->tpl('mod_user/change_password');
    }


    private function _change_password()
    {
        $this->val = Load::validator('mod_user.change_password');
        if ($this->val->check()) {
            $this->theme->set('errors', $this->val->errors);
        } else {
            $u = Load::model('mod_user');
            $new_password_hash = $this->_hash_password(Req::Post_Safe('new_password'));
            $u->password = $new_password_hash;
            $u->id = $this->session->get('id');
            if ($u->update('id')) {
                $this->session->remove('password');
                $this->session->set('password', $new_password_hash);
                $this->_message($this->lang['mod_user']['password_changed_success']);
            } else {
                $this->theme->set('errors', [$this->lang['mod_user']['database_error']]);
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

        if (!$this->_is_ajax_request()) {
            Helper::redirect($this->site['saddress'] . 'mod_user/panel?open_modal=info');
            return FALSE;
        }

        Run::import('management', '_index');

        if (Req::is_Post('edit')) {
            $this->_info();
        }

        $userId = $this->session->get('id');
        if (empty($userId)) {
            if ($this->_is_ajax_request()) {
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized access', 'redirect' => $this->site['saddress'] . "?login=modal"]);
                exit();
            }
            Helper::redirect($this->site['saddress'] . "?login=modal", 0);
            return FALSE;
        }

        $u = Load::model('mod_user');
        $u->Getid($userId)->gRo();
        $userData = $u->toArray();
        $d = is_array($userData) ? $userData : [];
        $socialData = $this->_load_social_links($userId);
        $d['social_links'] = (is_array($socialData) && isset($socialData['map']) && is_array($socialData['map'])) ? $socialData['map'] : [];
        
        // Use helper to parse phone data
        $this->_prepare_phone_data($d);

        $this->site['title'] = $this->lang['mod_user']['profile_menu']['edit_profile'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->set('is_ajax', $this->_is_ajax_request());
        
        // Pass country codes
        $this->theme->set('country_codes', $this->_get_country_codes());
        $this->theme->set('social_platforms', $this->_get_social_platforms());
        $this->theme->tpl('mod_user/info');
    }

    private function _info()
    {
        $this->val = Load::validator('mod_user.info');
        if ($this->val->check()) {
            $this->theme->set('errors', $this->val->errors);
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
                    $this->_emessage($image->error);
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
                $this->_message($this->lang['mod_user']['info_updated_success']);
            } else {
                $db->rollback();
                $this->theme->set('errors', [$this->lang['mod_user']['database_error']]);
            }
        }
    }

    public function ajax_save_info()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->check() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $this->val = Load::validator('mod_user.info');
        if ($this->val->check()) {
            $errMsgs = is_array($this->val->errors) ? implode(', ', $this->val->errors) : (string)$this->val->errors;
            echo json_encode(['status' => 'error', 'message' => $errMsgs]);
            exit;
        }

        $db = Load::lib('Database');
        $db->start_transaction();
        $u = Load::model('mod_user');
        $u->name = Req::Post_Safe('name');
        $u->last_name = Req::Post_Safe('last_name');
        $u->updated_at = date('Y-m-d H:i:s');
        $u->email = Req::Post_Safe('email');
        $u->phone = $this->_get_combined_phone();
        $u->city = Req::Post_Safe('city');
        $u->blog_name = Req::Post_Safe('blog_name');
        $u->about = Req::Post_Safe('about');
        $u->id = $this->session->get('id');

        if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_user';
            $image->name = 'image';
            $option = [
                '/' => ['width' => 200, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 100, 'height' => NULL, 'option' => 'width']
            ];
            if ($image->_resize($option)) {
                $u->image = $image->url;
                $this->session->set('image', $image->url);
            } else {
                $db->rollback();
                echo json_encode(['status' => 'error', 'message' => $image->error]);
                exit;
            }
        }

        $socialLinks = isset($_POST['social_links']) && is_array($_POST['social_links']) ? $_POST['social_links'] : [];
        if ($u->update('id') && $this->_save_social_links($this->session->get('id'), $socialLinks)) {
            $db->commit();
            $this->session->set('full_name', Text::title($u->name . " " . $u->last_name));
            echo json_encode([
                'status' => 'success',
                'message' => $this->lang['mod_user']['info_updated_success'],
                'full_name' => Text::title($u->name . " " . $u->last_name),
                'image' => $this->session->get('image')
            ]);
            exit;
        } else {
            $db->rollback();
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_user']['database_error']]);
            exit;
        }
    }

    public function ajax_save_change_password()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->check() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $this->val = Load::validator('mod_user.change_password');
        if ($this->val->check()) {
            $errMsgs = is_array($this->val->errors) ? implode(', ', $this->val->errors) : (string)$this->val->errors;
            echo json_encode(['status' => 'error', 'message' => $errMsgs]);
            exit;
        }

        $u = Load::model('mod_user');
        $new_password_hash = $this->_hash_password(Req::Post_Safe('new_password'));
        $u->password = $new_password_hash;
        $u->id = $this->session->get('id');
        if ($u->update('id')) {
            $this->session->remove('password');
            $this->session->set('password', $new_password_hash);
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_user']['password_changed_success']]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_user']['database_error']]);
            exit;
        }
    }

    public function ajax_upload_avatar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->check() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if (!isset($_FILES['image']['name']) || $_FILES['image']['name'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'No image provided']);
            exit;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_user';
        $image->name = 'image';
        $option = [
            '/' => ['width' => 200, 'height' => NULL, 'option' => 'width'],
            '/thumb' => ['width' => 100, 'height' => NULL, 'option' => 'width']
        ];

        if ($image->_resize($option)) {
            $u = Load::model('mod_user');
            $u->id = (int) $this->session->get('id');
            $u->image = $image->url;
            $u->updated_at = date('Y-m-d H:i:s');
            if ($u->update('id')) {
                $this->session->set('image', $image->url);
                echo json_encode([
                    'status' => 'success',
                    'message' => $this->lang['mod_user']['message']['success']['edit'] ?? 'Avatar updated successfully',
                    'image' => $image->url
                ]);
                exit;
            }
        }

        echo json_encode([
            'status' => 'error',
            'message' => $image->error ?: ($this->lang['mod_user']['database_error'] ?? 'Failed to upload avatar')
        ]);
        exit;
    }

    public function delete_image()
    {
        // Is Member?
        if ($this->check() === FALSE) {
            return FALSE;
        }
        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            $id = (int) $this->session->get('id');
        }

        $this->mod_user = Load::model('mod_user');

        if ($this->mod_user->where_id($id)->getResultOne(TRUE, TRUE)->getSize() === 0) {
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $this->lang['mod_user']['error']['user_not_found'] ?? 'User not found'
                ]);
                exit;
            }
            exit(':');
        }

        $d = $this->mod_user->toArray();

        if ((int) $d['id'] !== (int) $this->session->get('id')) {
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $this->lang['mod_user']['error']['permission_error'] ?? 'Permission denied'
                ]);
                exit;
            }
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
            $image->_delete_files($option);
            $this->mod_user->image = '';
            $this->mod_user->update('id');
            $this->session->set('image', '');

            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => $this->lang['mod_user']['remove_image_success'] ?? 'Image removed successfully'
                ]);
                exit;
            }
            echo 'Success';
        } else {
            $this->session->set('image', '');
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => $this->lang['mod_user']['remove_image_success'] ?? 'Image removed successfully'
                ]);
                exit;
            }
            echo 'Success';
        }
    }

    public function list()
    {
        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'list', $this->lang['mod_user']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');

        $totalUsers = 0;
        $activeUsers = 0;
        $pendingUsers = 0;

        try {
            $userTotalModel = Load::model('mod_user.user');
            $totalUsers = (int)$userTotalModel->getCount();

            $userActiveModel = Load::model('mod_user.user');
            $userActiveModel->where_status('y');
            $activeUsers = (int)$userActiveModel->getCount();

            $pendingUsers = max(0, $totalUsers - $activeUsers);
        } catch (Exception $e) {
            // Graceful fallback
        }

        $this->theme->set('total_users', $totalUsers);
        $this->theme->set('active_users', $activeUsers);
        $this->theme->set('pending_users', $pendingUsers);

        $this->site['title'] = $this->lang['mod_user']['admin']['list'];
        $this->theme->set('site', $this->site);
        $this->theme->tpl('mod_user/list');
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
            $colomns[] = 'created_at';
            $colomns[] = 'phone';
            $i->select($colomns);
            $limit = 50;
            $offset = 0;
            $order = $colomns[0];
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

            // Standard filter parameters
            if (Req::is_Post('filter') && count(Req::Post_Safe('filter')) > 0) {
                foreach (Req::Post_Safe('filter') as $k => $v) {
                    if ($v == '') {
                        continue;
                    }

                    $safe_value = Req::Clear($v);

                    if ($k === 'identity') {
                        $full_name = trim(preg_replace('/\s+/', ' ', $safe_value));
                        $wheres[] = "(
                            username LIKE '%" . $safe_value . "%'
                            OR email LIKE '%" . $safe_value . "%'
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

            // Quick search parameter (search[name] or search[value])
            if (Req::is_Post('search')) {
                $searchData = Req::Post_Safe('search');
                $searchVal = '';
                if (is_array($searchData)) {
                    if (!empty($searchData['name'])) {
                        $searchVal = Req::Clear($searchData['name']);
                    } elseif (!empty($searchData['value'])) {
                        $searchVal = Req::Clear($searchData['value']);
                    }
                } elseif (is_string($searchData) && $searchData !== '') {
                    $searchVal = Req::Clear($searchData);
                }

                if ($searchVal !== '') {
                    $full_name = trim(preg_replace('/\s+/', ' ', $searchVal));
                    $wheres[] = "(
                        username LIKE '%" . $searchVal . "%'
                        OR email LIKE '%" . $searchVal . "%'
                        OR name LIKE '%" . $searchVal . "%'
                        OR last_name LIKE '%" . $searchVal . "%'
                        OR CONCAT(TRIM(name), ' ', TRIM(last_name)) LIKE '%" . $full_name . "%'
                    )";
                }
            }

            // Radio / status filter (status = all, y, pending, n, a)
            $radioFilters = [];
            if (Req::is_Post('filter_radio') && is_array(Req::Post_Safe('filter_radio'))) {
                $radioFilters = array_merge($radioFilters, Req::Post_Safe('filter_radio'));
            }
            if (Req::is_Post('search_radio') && is_array(Req::Post_Safe('search_radio'))) {
                $radioFilters = array_merge($radioFilters, Req::Post_Safe('search_radio'));
            }

            foreach ($radioFilters as $k => $v) {
                if ($v === '' || $v === null || $v === 'all') {
                    continue;
                }
                $safeVal = Req::Clear($v);
                if ($k === 'status') {
                    if ($safeVal === 'pending') {
                        $wheres[] = "status IN ('n', 'a')";
                    } elseif (in_array($safeVal, ['y', 'n', 'a'])) {
                        $wheres[] = "status = '" . $safeVal . "'";
                    }
                } elseif (in_array($k, $colomns)) {
                    $wheres[] = $k . " = '" . $safeVal . "'";
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

            // Load group names mapping with localization support
            $groupMap = [];
            try {
                $groupModel = Load::model('mod_user_group');
                $groupModel->select(['group_id', 'name']);
                if ($groupModel->gR() && $groupModel->getSize() > 0) {
                    $groupKeyMap = [
                        'Site Management' => 'site_manager',
                        'Site Manager' => 'site_manager',
                        'Editors' => 'editors',
                        'Editor' => 'editors',
                        'Normal User' => 'normal_member',
                        'Normal Member' => 'normal_member',
                        'Member' => 'normal_member',
                        'Employee' => 'employee',
                        'Authors' => 'authors',
                        'Author' => 'authors',
                        'Writer' => 'authors',
                        'Nivîskar' => 'authors',
                    ];

                    foreach ($groupModel->toArray() as $userGroup) {
                        $rawName = $userGroup['name'];
                        if (isset($groupKeyMap[$rawName]) && !empty($this->lang['mod_user']['groups'][$groupKeyMap[$rawName]])) {
                            $groupMap[$userGroup['group_id']] = $this->lang['mod_user']['groups'][$groupKeyMap[$rawName]];
                        } else {
                            $groupMap[$userGroup['group_id']] = $rawName;
                        }
                    }
                }
            } catch (Exception $ge) {
                // Ignore if group model unavailable
            }

            $status_list = [
                'y' => ['success', $this->lang['mod_user']['grid']['active']],
                'n' => ['danger', $this->lang['mod_user']['grid']['waiting_email_activation']],
                'a' => ['danger', $this->lang['mod_user']['grid']['waiting_admin_activation']],
            ];

            if ($getCount > 0) {
                foreach ($i->toArray() as $content) {
                    $last = "";
                    if (Role::hasPermission('mod_user', 'edit')) {
                        $last .= '<button data-action="edit-user" data-id="' . $content['id'] . '" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_user']['button']['edit'] . ' </button>';
                    }
                    if (Role::hasPermission('mod_user', 'delete')) {
                        $last .= '<button data-action="delete-user" data-id="' . $content['id'] . '" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_user']['button']['delete'] . ' </button>';
                    }

                    // Safe status display
                    $status_html = '';
                    if (isset($content['status']) && isset($status_list[$content['status']])) {
                        $status_html = '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>';
                    } else {
                        $status_html = '<span class="label label-default">' . (isset($content['status']) ? $content['status'] : '') . '</span>';
                    }

                    $groupName = isset($groupMap[$content['group_id']]) ? $groupMap[$content['group_id']] : '';
                    $createdAt = !empty($content['created_at']) ? date('d.m.Y', strtotime($content['created_at'])) : '';
                    $phone = !empty($content['phone']) ? $content['phone'] : '';

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
                        $last,
                        $groupName,
                        $createdAt,
                        $phone,
                        $content['status'] ?? 'n'
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
        echo 'Success';
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
            $this->_emessage($this->lang['mod_user']['error']['user_not_found']);
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
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->set('group', $group);
        
        // Pass country codes
        $this->theme->set('country_codes', $this->_get_country_codes());
        $this->theme->set('social_platforms', $this->_get_social_platforms());
        $this->theme->tpl('mod_user/edit');
    }


    private function _edit($id, $is_ajax = false)
    {

        $this->val = Load::validator('mod_user.edit');

        if ($this->val->check($id)) {
            $this->theme->set('errors', $this->val->errors);
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
                    $this->theme->set('errors', [$image->error]);
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
                $this->_message($this->lang['mod_user']['admin']['updated']);
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
                $this->theme->set('errors', [$this->lang['mod_user']['database_error']]);
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
        $userrole = Load::model('mod_user.mod_user_role'); // Role Model

        $this->site['title'] = $this->lang['mod_user']['admin']['acl'];

        $this->theme->set('site', $this->site);
        $this->theme->tpl('mod_user/acl');

        // Pull All Groups
        $usergroup->all();
        $groups = $usergroup->toArray();
        $this->theme->set('groups', $groups);
        $this->theme->set('gruplar', $groups);

        // Pull Mods
        $mods->order('mod_id', 'DESC')->all('asc');
        $modules = $mods->toArray();
        $this->theme->set('modules', $modules);
        $this->theme->set('modlar', $modules);

        // Pull Roles
        $userrole->all();
        $allRoles = $userrole->toArray();
        $groupedRoles = [];

        // Load Module Language Files
        foreach ($modules as $mod) {
            $modLangDir = mod_dir . $mod['mod_name'] . DS . lang_dir;
            if (is_dir($modLangDir) && !empty(glob($modLangDir . '*.php'))) {
                $this->theme->lang(Load::lang($mod['mod_name']));
            }
            foreach ($allRoles as $role) {
                if ($role['mod_id'] === $mod['mod_id']) {
                    $groupedRoles[$role['mod_id']][] = $role;
                }
            }
        }

        $permissions = Load::model('mod_user.mod_user_perm');
        $permissions->order('mod_id', 'DESC')->all();
        $this->theme->set('permissions', $permissions->toArray());
        $this->theme->set('permler', $permissions->toArray());
        $this->theme->set('roles', $groupedRoles);
        $this->theme->set('roller', $groupedRoles);
    }


    private function _acl()
    {
        Load::libfile('Database');
        $perm = Load::model('mod_user.mod_user_perm'); // Permissions Model
        $db = new Database();
        $list = Req::Post_Safe('list');

        // Start Transaction
        $db->start_transaction();
        // Truncate Table
        $db->truncate("mod_user_perm");

        if (is_array($list)) {
            foreach ($list as $groupId => $modulePerms) {
                foreach ($modulePerms as $modId => $roles) {
                    foreach ($roles as $roleId => $val) {
                        $perm->group_id = $groupId;
                        $perm->role_id = $roleId;
                        $perm->mod_id = $modId;
                        if ($perm->save() === False) {
                            $db->rollback();
                            $this->_emessage($this->lang['mod_user']['database_error']);
                            return;
                        }
                    }
                }
            }
        }
        // Commit and Apply
        $db->commit();
        // Update administrator permissions in session
        $this->user->_loadPermissions($this->session->get('group_id'));
        $this->_message($this->lang['mod_user']['admin']['permissions_updated']);
    }

    /**
     * AJAX endpoint to toggle a single permission or a batch of permissions for a module group
     */
    public function toggle_permission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Method Not Allowed'
            ], 405);
            return;
        }

        // Check ACL permission
        if ($this->user->hasPermission('mod_user', 'acl', $this->lang['mod_user']['role_errors']['acl'] ?? '', 1) === False) {
            $this->_respond_with_json([
                'success' => false,
                'message' => $this->lang['mod_user']['role_errors']['acl'] ?? 'Unauthorized'
            ], 403);
            return;
        }

        $groupId = (int) Req::Post_Safe('group_id');
        $modId = (int) Req::Post_Safe('mod_id');
        $roleId = Req::Post_Safe('role_id');
        $state = Req::Post_Safe('state');
        $isChecked = ($state === '1' || $state === 'true' || $state === true || $state === 1);

        if ($groupId <= 0 || $modId <= 0) {
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Invalid module or group ID'
            ], 400);
            return;
        }

        $db = Load::lib('Database');

        if (is_array($roleId)) {
            // Batch toggle for multiple roles in this module and group
            $roleIds = array_map('intval', $roleId);
            $roleIds = array_values(array_filter($roleIds, fn($id) => $id > 0));
            if (empty($roleIds)) {
                $this->_respond_with_json([
                    'success' => false,
                    'message' => 'No valid role IDs specified'
                ], 400);
                return;
            }

            $db->start_transaction();
            $inList = implode(',', $roleIds);
            $db->query("DELETE FROM `mod_user_perm` WHERE `group_id` = {$groupId} AND `mod_id` = {$modId} AND `role_id` IN ({$inList})");

            if ($isChecked) {
                foreach ($roleIds as $rId) {
                    $db->query("INSERT INTO `mod_user_perm` (`group_id`, `mod_id`, `role_id`) VALUES ({$groupId}, {$modId}, {$rId})");
                }
            }
            $db->commit();
        } elseif ($roleId !== null && (int)$roleId > 0) {
            $rId = (int) $roleId;
            $db->start_transaction();
            $db->query("DELETE FROM `mod_user_perm` WHERE `group_id` = {$groupId} AND `mod_id` = {$modId} AND `role_id` = {$rId}");

            if ($isChecked) {
                $db->query("INSERT INTO `mod_user_perm` (`group_id`, `mod_id`, `role_id`) VALUES ({$groupId}, {$modId}, {$rId})");
            }
            $db->commit();
        } else {
            $this->_respond_with_json([
                'success' => false,
                'message' => 'Invalid role ID'
            ], 400);
            return;
        }

        // Synchronize permissions for currently logged-in user if their group was updated
        if ((int)$this->session->get('group_id') === $groupId) {
            $this->user->_loadPermissions($groupId);
        }

        $this->_respond_with_json([
            'success' => true,
            'message' => $this->lang['mod_user']['admin']['permissions_updated'] ?? 'Permissions updated'
        ]);
    }


    // Let's check if Logged In or Not
    private function _check_login()
    {
        // Is Logged In?
        if ($this->user->checklogin() === True) {
            Helper::redirect($this->site['saddress'] . "mod_user/panel", 2);
            return TRUE;
        }
        return FALSE;
    }

    // If Member Not Logged In, redirect to login
    public function check()
    {
        if ($this->user->checklogin() === False) {
            $redirectUrl = $this->site['saddress'] . "?login=modal&session_expired=1";
            if ($this->_is_ajax_request()) {
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'authenticated' => false,
                    'code' => 'session_expired',
                    'message' => 'Unauthorized access',
                    'redirect' => $redirectUrl
                ]);
                exit();
            }
            // Redirect to home with modal trigger instead of static login page
            Helper::redirect($redirectUrl, 0);
            return FALSE;
        }

        return TRUE;
    }

    // AJAX session liveness ping: 200 JSON when logged in, 401 JSON when not
    public function session_check()
    {
        if ($this->user->checklogin() === False) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'authenticated' => false,
                'code' => 'session_expired',
                'message' => 'Unauthorized access',
                'redirect' => $this->site['saddress'] . "?login=modal&session_expired=1"
            ]);
            exit();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'authenticated' => true,
            'user_id' => (int) $this->session->get('id'),
            'username' => (string) $this->session->get('username')
        ]);
        exit();
    }

    // Generic Message
    private function _message($message)
    {
        $this->theme->set('Success', True);
        $this->theme->set('message', $message);
    }

    // Negative Message (Admin)
    private function _emessage($message)
    {
        $this->theme->set('errors', (array)$message);
    }

    // Positive Message (App)
    private function _app_message($message)
    {
        $this->apptemplate->set('Success', True);
        $this->apptemplate->set('message', $message);
    }

    // Negative Message (App)
    private function _app_emessage($message)
    {
        $this->apptemplate->set('errors', (array)$message);
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
                Helper::redirect($this->site['saddress'], 0);
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
                Helper::redirect($this->site['saddress'], 0);
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
                Helper::redirect($this->site['saddress'], 0);
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
                Helper::redirect($this->site['saddress'], 0);
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
                Helper::redirect($this->site['saddress'], 0);
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
