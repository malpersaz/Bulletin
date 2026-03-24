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
        $key = Helper::randomkey(20);
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
        // Detect AJAX requests but allow normal GET access to the login page.
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                   (isset($_POST['ajax_request']) && ($_POST['ajax_request'] === 'true' || $_POST['ajax_request'] === '1'));

        // Start buffering immediately to catch any spurious output
        ob_start();

        Run::import('index', '_index');
        $d['title'] = $this->lang['mod_user']['admin_panel'];
        $this->apptemplate->veri('d', $d);
        //$this->apptemplate->veri('site', $this->site);

        // Check for stored login errors from previous AJAX attempt
        $session = Load::lib('Session');
        if ($session->varmi('login_errors')) {
            $this->apptemplate->veri('errors', $session->getir('login_errors'));
            $session->sil('login_errors'); // Clear after use
        }

        $this->apptemplate->tpl('mod_user/login');

        // Is Logged In?
        if ($this->_check_login()) {
            return;
        }

        // Get Referer
        $ref = isset($_SERVER['HTTP_REFERER']) ? Req::Clear($_SERVER['HTTP_REFERER']) : '';
        // If ref is empty, redirect to site homepage.
        $r = $ref != '' ? $ref : $this->site['sadres'];
        if (isset($_POST['login']) || Req::is_Post('login')) {
            $username = Req::Post_Safe('username');
            $password = Req::Post_Safe('password');

            // Enhanced AJAX detection
            $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                       strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                       (!empty($_POST['ajax_request']) && $_POST['ajax_request'] === 'true');

            $this->val = Load::validator('mod_user.login');

            // Validation failed
            if ($this->val->check()) {
                @file_put_contents(root_dir . 'logs/login_debug.log', "[" . date('Y-m-d H:i:s') . "] Login attempt for: " . Req::Post_Safe('username') . " (AJAX: " . ($is_ajax ? 'Y' : 'N') . ")\n", FILE_APPEND);
                $errors = array_merge($this->val->errors, $this->user->errors ?? []);

                if ($is_ajax) {
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $this->lang['mod_user']['ajax']['validation_error'],
                        'errors' => $errors
                    ]);
                    session_write_close();
                    exit;
                } else {
                    // Normal form submission with validation errors
                    $this->apptemplate->veri('username', $username);
                    $this->apptemplate->veri('password', $password);
                    $this->apptemplate->veri('errors', $errors);
                    $this->apptemplate->tpl('mod_user/login');
                    return;
                }
            }

            // Attempt login
            if ($this->user->login($username, $password)) {
                $this->_appmesaj($this->lang['mod_user']['login_success_wait']);

                if ($is_ajax) {
                    while (ob_get_level() > 0) {
                        $c = ob_get_clean();
                        if ($c) @file_put_contents(root_dir . 'logs/login_debug.log', "  Discarded: '" . bin2hex($c) . "'\n", FILE_APPEND);
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    $response = json_encode([
                        'success' => true,
                        'message' => $this->lang['mod_user']['ajax']['login_success'],
                        'redirect' => $r
                    ]);
                    @file_put_contents(root_dir . 'logs/login_debug.log', "  Success Response: " . $response . "\n", FILE_APPEND);
                    echo $response;
                    session_write_close();
                    exit;
                } else {
                    // Normal redirect for regular form submission
                    Helper::yonlen($r, 3);
                }
            } else {
                // Login failed - collect all errors
                $errors = $this->user->errors ?? [$this->lang['mod_user']['ajax']['invalid_credentials']];

                if ($is_ajax) {
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => $this->lang['mod_user']['ajax']['login_error'],
                        'errors' => $errors
                    ]);
                    session_write_close();
                    exit;
                } else {
                    // Normal error handling for regular form submission
                    $this->apptemplate->veri('username', $username);
                    $this->apptemplate->veri('password', $password);
                    $this->apptemplate->veri('errors', $errors);
                    $this->apptemplate->tpl('mod_user/login');
                }
            }
        }
    }

    // Logout Page
    public function logout($sid = NULL)
    {
        // Get sid value from Session
        $session_sid = $this->session->getir('sid');

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

        // Always redirect to homepage after logout
        $r = $this->site['adres'];
        
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

        if (Req::is_Post('forgot_password')) {
            $this->_forgot_password();
        }
        $d['title'] = $this->lang['mod_user']['forgot_password']['invalid_email'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->captcha->generate(); // Create new captcha
        $captcha_url = $this->captcha->image();
        $this->apptemplate->veri('captcha_image', $captcha_url);

        $this->apptemplate->tpl('mod_user/forgot-password');
    }

    private function _forgot_password()
    {
        $this->val = Load::validator('mod_user.forgot_password');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->model = Load::model('mod_user');
            $this->model->Getemail(Req::Post_Safe('email'))->getResultOne(TRUE, TRUE)->sync();
            if ($this->_semail_at()) {
                $this->_appmesaj($this->lang['mod_user']['forgot_password']['title']);
            } else {
                $this->_appmesaj($this->lang['mod_user']['forgot_password']['error']['email_not_registered']);
            }
        }
    }

    private function _semail_at()
    {
        // Has Key Been Created Before
        $this->password_reset_model = Load::model('mod_user.password_reset');
        $this->password_reset_model->getemail($this->model->email)->getResultOne(TRUE, TRUE);
        if ($this->password_reset_model->getSize() !== 0) {
            $this->password_reset_model->sync();
            $key = $this->password_reset_model->code;
        } else {
            $key = Helper::randomkey(20);
            $this->password_reset_model->email = $this->model->email;
            $this->password_reset_model->code = $key;
            $this->password_reset_model->save();
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


    public function password_reset($code = NULL)
    {
        Run::import('index', '_index');
        if ($code != 'NULL' and strlen($code) == 20) {
            $this->apptemplate->veri('code', Req::Url_Clear($code));
        }
        if (Req::is_Post('password_reset')) {
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

            $pass = Helper::randomkey(16);
            $this->model->password = md5($pass);


            if ($this->_email_pass($pass)) {
                if ($this->model->update('id')) {
                    $this->_appmesaj($this->lang['mod_user']['password_reset']['password_required']);
                }
            } else {
                $this->_appmesaj($this->lang['mod_user']['password_reset']['error']['passwords_mismatch']);
            }
        }
    }

    private function _email_pass($pass)
    {
        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = [$this->model->email => $this->model->name . " " . $this->model->last_name];
        $email->konu = $this->lang['mod_user']['register']['your_new_password'];
        $this->apptemplate->veri('name', $this->model->name);
        $this->apptemplate->veri('last_name', $this->model->last_name);
        $this->apptemplate->veri('password', $pass);
        $this->apptemplate->veri('site', $this->site);
        $this->apptemplate->veri('ip', $_SERVER['REMOTE_ADDR']);
        $email->mesaj = $this->apptemplate->fetch('mod_user/email_new_password');
        if ($email->gonder()) {
            $this->password_reset->delete();
            return True;
        }
        return False;
    }

    // Resends Activation Code
    public function re_activation()
    {
        Run::import('index', '_index');
        if (Req::is_Post('re_activation')) {
            $this->_re_activation();
        }
        $d['title'] = $this->lang['mod_user']['re_activation']['activation_code_label'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());
        $this->apptemplate->tpl('mod_user/re_activation');
    }

    // Resends Activation Code
    private function _re_activation()
    {
        $this->val = Load::validator('mod_user.re_activation');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->activation = Load::model('mod_user.activation');
            $this->activation->getemail(Req::Post_Safe('email'))->gRo();
            if ($this->activation->getSize() === 0) {
                $this->_appemesaj($this->lang['mod_user']['re_activation']['error']['no_activation_code_found']);
                return FALSE;
            }
            $this->activation->sync();
            if ($this->_email_at($this->activation->code)) {
                $this->_appmesaj($this->lang['mod_user']['re_activation']['code_sent']);
            } else {
                $this->_appemesaj($this->lang['mod_user']['re_activation']['error']['invalid_captcha']);
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
    public function aktivasyon($code = NULL)
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

        // Is Logged In?
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');

        // Check for session messages
        if ($this->session->getir('success_message')) {
            $this->apptemplate->veri('success_message', $this->session->getir('success_message'));

        }
        if ($this->session->getir('error_message')) {
            $this->apptemplate->veri('error_message', $this->session->getir('error_message'));

        }

        // Pass language strings to JavaScript with null coalescing for missing language files
        $js_lang = [
            'checking' => $this->lang['mod_user']['register']['js']['checking'] ?? '⏳ Checking...',
            'username_available' => $this->lang['mod_user']['register']['js']['username_available'] ?? '✅ Username available',
            'username_exists' => $this->lang['mod_user']['register']['js']['username_exists'] ?? '❌ This username is already taken',
            'username_short' => $this->lang['mod_user']['register']['js']['username_short'] ?? 'Username must be at least 2 characters',
            'email_available' => $this->lang['mod_user']['register']['js']['email_available'] ?? '✅ Email available',
            'email_exists' => $this->lang['mod_user']['register']['js']['email_exists'] ?? '❌ This email is already registered',
            'check_failed' => $this->lang['mod_user']['register']['js']['check_failed'] ?? '⚠️ Check could not be performed',
            'register_success' => $this->lang['mod_user']['register']['js']['register_success'] ?? 'Your membership has been successfully created! You can log in.',
            'errors_title' => $this->lang['mod_user']['register']['js']['errors_title'] ?? 'Errors:',
            // Validation messages with null coalescing for missing language files
            'validation_required' => $this->lang['mod_user']['register']['js']['validation_required'] ?? 'This field is required',
            'validation_required_name' => $this->lang['mod_user']['register']['js']['validation_required_name'] ?? 'Please enter your name',
            'validation_required_surname' => $this->lang['mod_user']['register']['js']['validation_required_surname'] ?? 'Please enter your surname',
            'validation_required_username' => $this->lang['mod_user']['register']['js']['validation_required_username'] ?? 'Please enter a username',
            'validation_username_short' => $this->lang['mod_user']['register']['js']['validation_username_short'] ?? 'Username must be at least 2 characters',
            'validation_username_long' => $this->lang['mod_user']['register']['js']['validation_username_long'] ?? 'Username must not exceed 20 characters',
            'validation_required_email' => $this->lang['mod_user']['register']['js']['validation_required_email'] ?? 'Please enter your email address',
            'validation_invalid_email' => $this->lang['mod_user']['register']['js']['validation_invalid_email'] ?? 'Please enter a valid email address',
            'validation_required_password' => $this->lang['mod_user']['register']['js']['validation_required_password'] ?? 'Please enter a password',
            'validation_password_short' => $this->lang['mod_user']['register']['js']['validation_password_short'] ?? 'Password must be at least 6 characters',
            'validation_password_long' => $this->lang['mod_user']['register']['js']['validation_password_long'] ?? 'Password must not exceed 20 characters',
            'validation_confirm_password' => $this->lang['mod_user']['register']['js']['validation_confirm_password'] ?? 'Please confirm your password',
            'validation_required_phone' => $this->lang['mod_user']['register']['js']['validation_required_phone'] ?? 'Please enter your phone number',
            'validation_required_captcha' => $this->lang['mod_user']['register']['js']['validation_required_captcha'] ?? 'Please enter the security code'
        ];
        $this->apptemplate->veri('js_lang', json_encode($js_lang));

        // Pass country codes to template
        $this->apptemplate->veri('country_codes', $this->_get_country_codes());

        $this->apptemplate->tpl('mod_user/register');
        //echo  date("Y-m-d H:i:s",time());
        $this->settings = Load::model('mod_user.mod_user_settings');
        $this->settings->all();

        foreach ($this->settings->toArray() as $val) {
            $d[$val['k']] = $val['v'];
        }
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
        //unset ($d);
        $d['title'] = $this->lang['mod_user']['register']['membership_registration'];
        //$this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->image());
        // $this->apptemplate->tpl('mod_user/kayit');
    }


    private function _register()
    {
        $this->val = Load::validator('mod_user.register');
        if ($this->val->check()) {
            // Validation failed - show errors
            // error_log("VALIDATION FAILED: " . json_encode($this->val->errors));
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('errors', $this->val->errors);
        } else {
            $this->apptemplate->veri('oke', True);

            $this->model = Load::model('mod_user');
            $this->model->name = Text::title(Req::Post_Safe('name'));
            $this->model->last_name = Text::title(Req::Post_Safe('last_name'));
            $this->model->username = Req::Post_Safe('username');
            $this->model->email = Req::Post_Safe('email');
            $this->model->password = md5(Req::Post_Safe('password'));
            $country_code = $_POST['country_code']; // Get raw value to preserve + sign
            $phone = Req::Post_Safe('phone');

            // Combine country code and phone number (preserve + sign)
            if ($country_code && $phone) {
                $this->model->phone = $country_code . ' ' . $phone;
            } else {
                $this->model->phone = $phone; // Fallback to just phone number
            }

            $this->model->ip = $_SERVER['REMOTE_ADDR'];
            $this->model->group_id = $this->_group_id;
            if ($this->_activation == 1) {
                $this->model->status = 'n';
            } elseif ($this->_activation == 2) {
                $this->model->status = 'a';
            } else {
                $this->model->status = 'y';
            }
            $this->model->updated_at = date('Y-m-d H:i:s'); // Auto-set update date
            $this->model->created_at = date('Y-m-d H:i:s'); // Auto-set registration date
            $this->model->image = ''; // Default empty string for image
            $this->model->city = ''; // Default empty string for city (yer)
            $this->model->blog_name = ''; // Default empty string for blog_name
            $this->model->about = ''; // Default empty string for about
            if ($this->model->save()) {
                if ($this->_activation == 1) {
                    if ($this->_email_at()) {
                        $this->session->ac('success_message', $this->lang['mod_user']['register']['registration_success_activation']);
                    } else {
                        $this->session->ac('error_message', $this->lang['mod_user']['register']['error']['activation_email_error']);
                    }
                } elseif ($this->_activation == 2) {
                    $this->session->ac('success_message', $this->lang['mod_user']['register']['registration_success_admin']);
                } else {
                    $this->session->ac('success_message', $this->lang['mod_user']['register']['registration_success']);
                }
                // Redirect to same page with success parameter
                Helper::yonlen($this->site['adres'] . 'register?success=1', 0);
                session_write_close();
                exit;
            } else {
                // exit($this->model->db->sql_error());
                $this->apptemplate->veri('error_message', 'Database Error: ' . $this->model->db->sql_error());
            }
        }
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
                        print_r($this->settings);
                        $this->_emesaj($this->settings->db->sql_error());
                        return False;
                    }
                }
            }
        }
        $this->_mesaj($this->lang['mod_user']['admin']['settings_updated']);
        return True;
    }


    public function panel()
    {
        if ($this->check() === FALSE) {
            return FALSE;
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user']['user_panel'];

        $this->tema->veri('site', $this->site);
        $this->tema->veri('panel_actions', $this->_build_panel_actions());
        //print_r($_COOKIE);
        $this->tema->tpl('mod_user/panel');
    }

    private function _build_panel_actions()
    {
        $management_lang = Load::lang('management');
        $video_embed_lang = Load::lang('mod_video_embed');
        $slider_lang = Load::lang('mod_slider');

        $quick_access = $this->lang['mod_user']['panel']['quick_access'];
        $definitions = [
            [
                'module' => 'mod_content',
                'permission' => 'list',
                'href' => $this->site['sadres'] . 'mod_content/list',
                'icon' => 'bi bi-file-earmark-text-fill',
                'title' => $management_lang['management']['content_management'],
                'meta' => $quick_access,
            ],
            [
                'module' => 'mod_video_embed',
                'permission' => 'list',
                'href' => $this->site['sadres'] . 'mod_video_embed/list',
                'icon' => 'bi bi-youtube',
                'title' => $video_embed_lang['mod_video_embed']['list'],
                'meta' => $quick_access,
            ],
            [
                'module' => 'mod_slider',
                'permission' => 'list',
                'href' => $this->site['sadres'] . 'mod_slider/list',
                'icon' => 'bi bi-images',
                'title' => $slider_lang['mod_slider']['list'],
                'meta' => $quick_access,
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
            'blog_name' => '', 'about' => '', 'status' => 'y'
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

        if ($this->val->check()) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
            $u = Load::model('mod_user');
            $u->username = Req::Post_Safe('username');
            $u->password = md5(Req::Post_Safe('password'));
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

            if ($u->save()) {
                $this->_mesaj($this->lang['mod_user']['admin']['added']);
                // Clear form data on success
                $d = array_fill_keys(array_keys($d), '');
            } else {
                $this->tema->veri('errors', print_r($u, TRUE));
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
            $u->password = md5(Req::Post_Safe('new_password'));
            $u->id = $this->session->getir('id');
            if ($u->update('id')) {
                $this->session->kapat('password');
                $this->session->ac('password', md5(Req::Post_Safe('new_password')));
                $this->_mesaj($this->lang['mod_user']['password_changed_success']);
            } else {
                $this->tema->veri('errors', print_r($u, TRUE));
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
        $u->Getid($this->session->getir('id'))->gRo();
        $d = $u->toArray();
        
        // Use helper to parse phone data
        $this->_prepare_phone_data($d);

        $this->site['title'] = $this->lang['mod_user']['info_change'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        
        // Pass country codes
        $this->tema->veri('country_codes', $this->_get_country_codes());
        $this->tema->tpl('mod_user/info');
    }

    private function _info()
    {
        $this->val = Load::validator('mod_user.info');
        if ($this->val->check()) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
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
            $u->id = $this->session->getir('id');


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
                    $this->session->ac('image', $image->url);
                } else {
                    $this->_emesaj($image->error);
                    return FALSE;
                }
            } else {
                if (isset($u->image)) {
                    unset($u->image);
                }
            }
            $u->update('id');
            $this->session->ac('full_name', Text::title($u->name . " " . $u->last_name));
            $this->_mesaj($this->lang['mod_user']['info_updated_success']);
        }
    }

    public function imagedelete()
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

        if ($d['id'] !== $this->session->getir('id')) {
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
                $this->session->ac('image', '');
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
                    if (in_array($k, $colomns) && $v != '') {
                        $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
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
                        $status_html = '<span class="label label-default">' . ($content['status'] ?? 'Unknown') . '</span>';
                    }

                    $data[] = [
                        '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                        $content['id'],
                        $content['username'],
                        $content['email'],
                        $content['name'], // Corrected from 'name' to 'name'
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

        // Is there Listing Permission?
        if ($this->user->hasPermission('mod_user', 'edit', $this->lang['mod_user']['role_errors']['edit'], 1) === False) {
            return False;
        }
        Run::import('management', '_index');

        $this->i = Load::model('mod_user');
        $id = Req::Url_Clear($id);

        if ($this->i->exists_id($id) === False) {
            $this->_emesaj($this->lang['mod_user']['error']['user_not_found']);
            return;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }


        $d = $this->i->getResultOne()->toArray();
        $d['username'] = Req::is_Post('username') ? Req::Post('username') : $d['username'];
        $d['email'] = Req::is_Post('email') ? Req::Post('email') : $d['email'];
        
        // Prepare phone data
        $this->_prepare_phone_data($d);

        // Let's pull the Member Group
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'parent_id', 'group_id');

        $this->site['title'] = $this->lang['mod_user']['admin']['edit_area'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        //print_r($group);
        $this->tema->veri('group', $group);
        
        // Pass country codes
        $this->tema->veri('country_codes', $this->_get_country_codes());
        $this->tema->tpl('mod_user/edit');
    }


    private function _edit($id)
    {

        $this->val = Load::validator('mod_user.edit');

        if ($this->val->check($id)) {
            $this->tema->veri('errors', $this->val->errors);
        } else {
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
                $u->password = md5(Req::Post_Safe('password'));
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
                    return FALSE;
                }
            }

            if ($u->update('id')) {
                $this->_mesaj($this->lang['mod_user']['admin']['updated']);
            } else {
                $this->tema->veri('errors', ['Database Error: ' . $u->db->sql_error()]);
            }
        }
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
                        $this->_emesaj(print_r($db, TRUE));
                    }
                }
            }
        }
        // If there is no problem, Commit and Apply
        $db->commit();
        // Update administrator Roles
        $this->user->_roll($this->session->getir('group_id'));
        $this->_mesaj($this->lang['mod_user']['admin']['permissions_updated']);
    }


    // Let's check if Logged In or Not
    private function _check_login()
    {
        // Is Logged In?
        if ($this->user->checklogin() === True) {
            Helper::yonlendir($this->site['sadres'], 2);
            return TRUE;
        }
        return FALSE;
    }

    // If Member Not Logged In, redirect to login
    public function check()
    {
        if ($this->user->checklogin() === False) {
            Helper::yonlendir($this->site['adres'] . "mod_user/login", 1);
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

    // For Javascript Validator
    public function uyevarmi()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs[2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs[2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }

    // For Javascript Validator
    public function usernamecheck()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs[2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs[2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }

    // For Javascript Validator
    public function emailcheck()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = [];
        $arrayToJs[0] = $id;
        $arrayToJs[1] = $error;
        if ($this->b->exists_email($val) === False) {
            $arrayToJs[2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs[2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }

    // Real-time username validation for registration
    public function checkUsernameAvailability()
    {
        $username = Req::Post_Safe('username');

        if (empty($username) || strlen($username) < 2) {
            echo json_encode([
                'available' => false,
                'message' => 'Kullanıcı adı en az 2 karakter olmalıdır'
            ]);
            return;
        }

        // Check real username availability
        $this->model = Load::model('mod_user');
        $isAvailable = !$this->model->exists_username($username);

        echo json_encode([
            'available' => $isAvailable,
            'message' => $isAvailable ?
                $this->lang['mod_user']['register']['js']['username_available'] :
                $this->lang['mod_user']['register']['js']['username_exists']
            ]);
    }

    // Real-time username validation for login step-by-step
    public function checkUserExistence()
    {
        $username = Req::Post_Safe('username');

        if (empty($username)) {
            echo json_encode([
                'exists' => false,
                'message' => $this->lang['mod_user']['error']['specify_username']
            ]);
            return;
        }

        $this->model = Load::model('mod_user');
        $user = $this->model->exists_username($username);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        if ($user) {
            echo json_encode([
                'exists' => true,
                'username' => $username,
                'message' => 'Success'
            ]);
        } else {
            echo json_encode([
                'exists' => false,
                'message' => $this->lang['mod_user']['error']['username_not_found']
            ]);
        }
        session_write_close();
        exit;
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
            $image_data = $captcha->image();

            // If data URL comes, decode it
            if (strpos($image_data, 'data:image/png;base64,') === 0) {
                echo base64_decode(substr($image_data, 22));
            } else {
                // If file path comes, read the file
                if (file_exists($image_data)) {
                    echo file_get_contents($image_data);
                } else {
                    die('Captcha file not found');
                }
            }
        } catch (Exception $e) {
            die('Captcha error: ' . $e->getMessage());
        }
        exit; // Prevent HTML output
    }
}
