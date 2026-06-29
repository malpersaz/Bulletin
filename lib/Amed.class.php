<?php

class AmedException extends Exception
{
    public function notcall()
    {
        echo $this->getMessage() . " method cannot be executed";
    }
}

class Amed
{
    private $method;
    private $class;
    private $ext;
    private $site;
    private $url;

    private function hasConfiguredDatabase()
    {
        $database = Kayit::getir('database');
        $dbConfig = Kayit::getir('db_config');

        if (!is_string($database) || $database === '') {
            return false;
        }

        if (!is_array($dbConfig) || !isset($dbConfig[$database]) || !is_array($dbConfig[$database])) {
            return false;
        }

        return isset($dbConfig[$database]['adapter'], $dbConfig[$database]['conf']) && is_array($dbConfig[$database]['conf']);
    }

    private function getBootstrapLanguageState()
    {
        $bootstrap = Kayit::getir('i18n_bootstrap');
        $fallback = array(
            'default_language' => 'ku',
            'active_codes' => array('ku'),
        );

        if (!is_array($bootstrap)) {
            return $fallback;
        }

        $activeCodes = array();
        if (isset($bootstrap['active_langs']) && is_array($bootstrap['active_langs'])) {
            $activeCodes = array_values(array_unique(array_filter($bootstrap['active_langs'], 'is_string')));
        }

        if (empty($activeCodes)) {
            $activeCodes = $fallback['active_codes'];
        }

        $defaultLanguage = isset($bootstrap['default_lang']) && is_string($bootstrap['default_lang']) && in_array($bootstrap['default_lang'], $activeCodes, TRUE)
            ? $bootstrap['default_lang']
            : $activeCodes[0];

        return array(
            'default_language' => $defaultLanguage,
            'active_codes' => $activeCodes,
        );
    }

    private function buildLanguageRegistryState()
    {
        $bootstrapState = $this->getBootstrapLanguageState();
        $fallbackCodes = $bootstrapState['active_codes'];
        $defaultLanguage = $bootstrapState['default_language'];
        $state = array(
            'active_codes' => $fallbackCodes,
            'default_language' => $defaultLanguage,
            'available_languages' => array(),
        );

        if (!$this->hasConfiguredDatabase()) {
            return $state;
        }

        try {
            $langModel = Load::model('lang');
            $langModel->all();
            if ($langModel->getSize() === 0) {
                return $state;
            }

            $rows = $langModel->toArray();
            $hasStatusColumn = array_key_exists('status', $rows[0]);
            $hasDefaultColumn = array_key_exists('is_default', $rows[0]);
            $activeCodes = array();
            $availableLanguages = array();

            foreach ($rows as $row) {
                $isActive = !$hasStatusColumn || !isset($row['status']) || $row['status'] === 'y';
                if ($isActive) {
                    $activeCodes[] = $row['seo'];
                    $availableLanguages[] = array(
                        'code' => $row['seo'],
                        'name' => $row['name'],
                    );
                }

                if ($hasDefaultColumn && isset($row['is_default']) && $row['is_default'] === 'y') {
                    $defaultLanguage = $row['seo'];
                }
            }

            if (empty($activeCodes)) {
                return $state;
            }

            $state['active_codes'] = array_values(array_unique($activeCodes));
            $state['default_language'] = in_array($defaultLanguage, $state['active_codes'], TRUE) ? $defaultLanguage : $state['active_codes'][0];
            $state['available_languages'] = $availableLanguages;
        } catch (Exception $exception) {
            return $state;
        }

        return $state;
    }

    private function publishLanguageRegistryState(array $languageState)
    {
        $this->site['default_lang'] = $languageState['default_language'];
        $this->site['available_languages'] = $languageState['available_languages'];

        Kayit::yenile('active_langs', $languageState['active_codes']);
        Kayit::yenile('default_lang', $languageState['default_language']);
    }

    private function mergeSettingsForLanguage($langCode)
    {
        if (!$this->hasConfiguredDatabase()) {
            return;
        }

        $langCode = is_string($langCode) && $langCode !== ''
            ? $langCode
            : (isset($this->site['default_lang']) ? $this->site['default_lang'] : 'en');

        try {
            $settingsModel = Load::model('mod_settings');
            $settingsModel->where_lang($langCode)->gRo();

            if ($settingsModel->getSize() > 0) {
                $settingsModel->sync();
                $this->site = array_merge($this->site, $settingsModel->toArray());
            }
        } catch (Exception $exception) {
        }
    }

    public function __construct()
    {
        Load::lib('Session');
        $this->url = Load::lib('Url');
        $url_config = Kayit::getir('url_config');
        $this->site = Kayit::getir('site_config');
        $languageState = $this->buildLanguageRegistryState();
        $this->site['lang'] = $languageState['default_language'];
        $this->publishLanguageRegistryState($languageState);

        if ($this->hasConfiguredDatabase()) {
            try {
                $this->mergeSettingsForLanguage($this->site['lang']);

                try {
                    $lang_model = Load::model('lang');
                    $lang_model->all();
                    $available_langs = [];
                    $active_langs = Kayit::getir('active_langs');
                    if ($lang_model->getSize() > 0) {
                        foreach ($lang_model->toArray() as $l) {
                            if (is_array($active_langs) && in_array($l['seo'], $active_langs, TRUE)) {
                                $available_langs[] = ['code' => $l['seo'], 'name' => $l['name']];
                            }
                        }
                    }
                    $this->site['available_languages'] = $available_langs;
                } catch (Exception $lang_e) {
                }

                Kayit::yenile('site_config', $this->site);
            } catch (Exception $e) {
                Kayit::yenile('site_config', $this->site);
            }
        } else {
            Kayit::yenile('site_config', $this->site);
        }

        if (Kayit::getir('i18n') === true) {
            $activeLangs = Kayit::getir('active_langs');
            if (is_array($activeLangs) && in_array($this->url->path['0'], $activeLangs, TRUE)) {
                $this->site['lang'] = $this->url->path['0'];
                $this->site['saddress'] = $this->site['address'] . $this->url->path['0'] . '/';
                $lang_len = mb_strlen($this->url->path['0'], "UTF-8");
                $this->url->link = substr($this->url->link, $lang_len + 1);
            } else {
                $this->site['lang'] = isset($this->site['default_lang']) ? $this->site['default_lang'] : $this->site['lang'];
                $this->site['saddress'] = $this->site['address'] . $this->site['lang'] . '/';
            }
            Kayit::yenile('lang', $this->site['lang']);
        }

        $this->mergeSettingsForLanguage($this->site['lang']);

        $this->site['url'] = trim($this->url->link, '/');
        Kayit::yenile('site_config', $this->site);

        if ($this->hasConfiguredDatabase()) {
            try {
                $cleanRequestUrl = trim($this->url->link, '/');
                $db = Load::lib('Database');
                $tableExists = false;
                $adapter = $db->getAdapterType();
                if ($adapter && strpos($adapter, 'PDO_SQLITE') !== false) {
                    $db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='mod_redirect'");
                    $tableExists = $db->getSize() > 0;
                } else {
                    $db->fetch_array("SHOW TABLES LIKE 'mod_redirect'");
                    $tableExists = $db->getSize() > 0;
                }

                if ($tableExists) {
                    $safeUrl = addslashes($cleanRequestUrl);
                    $db->fetch_array("SELECT * FROM mod_redirect WHERE old_url = '" . $safeUrl . "' OR old_url = '/" . $safeUrl . "' LIMIT 1");
                    if ($db->getSize() > 0) {
                        $redirectRow = $db->toArray()[0];
                        $target = $redirectRow['new_url'];
                        $statusCode = (int)$redirectRow['status_code'];
                        if ($statusCode !== 301 && $statusCode !== 302) {
                            $statusCode = 301;
                        }

                        if (preg_match('#^https?://#i', $target)) {
                            $redirectUrl = $target;
                        } else {
                            $targetHasLang = false;
                            $activeLangs = Kayit::getir('active_langs');
                            if (is_array($activeLangs)) {
                                foreach ($activeLangs as $al) {
                                    if (strpos($target, $al . '/') === 0 || $target === $al) {
                                        $targetHasLang = true;
                                        break;
                                    }
                                }
                            }

                            if ($targetHasLang) {
                                $baseUrl = isset($this->site['address']) ? $this->site['address'] : '/';
                            } else {
                                $baseUrl = isset($this->site['saddress']) ? $this->site['saddress'] : (isset($this->site['address']) ? $this->site['address'] : '/');
                                if (strpos($baseUrl, '/tr/') !== false && strpos($target, 'tr/') === 0) {
                                    $target = substr($target, 3);
                                } elseif (strpos($baseUrl, '/en/') !== false && strpos($target, 'en/') === 0) {
                                    $target = substr($target, 3);
                                }
                            }
                            $redirectUrl = rtrim($baseUrl, '/') . '/' . ltrim($target, '/');
                        }

                        if ($statusCode === 301) {
                            header("HTTP/1.1 301 Moved Permanently");
                        } else {
                            header("HTTP/1.1 302 Found");
                        }
                        header("Location: " . $redirectUrl);
                        exit;
                    }
                }
            } catch (Exception $e) {
                // Fail silently to prevent site crashes
            }
        }

        if (is_array($url_config)) {
            foreach ($url_config as $key => $val) {
                $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));
                if (preg_match('#^' . $key . '$#', $this->url->link)) {
                    if (strpos($val, '$') !== false and strpos($key, '(') !== false) {
                        $val = preg_replace('#^' . $key . '$#', $val, $this->url->link);
                    }
                    return $this->parselle($val);
                }
            }
        }
        unset($url_config);
        $this->parselle($this->url->link);
    }


    public function parselle($parsel)
    {
        if (strpos($parsel, '/') !== false) {
            $val = explode('/', $parsel);
            $this->class = (isset($val['0']) and $val['0'] != '') ? $val['0'] : Kayit::getir('app_default_mod');
            $this->method = (isset($val['1']) and $val['1'] != '') ? $val['1'] : Kayit::getir('app_default_function');
            $ext = array_slice($val, 2);
            if (isset($ext[0])) {
                foreach ($ext as $k) {
                    if ($k != '') {
                        $this->ext[] = $k;
                    }
                }
            }
        } else {
            if ($this->url->root()) {
                $this->class = Kayit::getir('app_default_mod');
                $this->method = Kayit::getir('app_default_function');
            } elseif (strpos($parsel, '?') === false) {
                $this->class = Kayit::getir('app_default_mod');
                $this->method = $this->url->link;
            } else {
                $this->class = Kayit::getir('app_default_mod');
                $this->method = Kayit::getir('app_default_function');
            }
        }
    }

    public function _isprivate()
    {
        if (substr($this->method, 0, 1) === '_') {
            throw new AmedException($this->method);
        }
        return true;
    }

    private function hasAdminContext()
    {
        return Kayit::varmi('AdminTemplate-lib');
    }

    private function renderAdminRouteError($type, $target)
    {
        header("HTTP/1.1 404 Not Found");

        $managementLang = Load::lang('management');
        $adminTemplate = Load::lib('AdminTemplate');
        $adminTemplate->lang($managementLang);

        $site = Kayit::getir('site_config');
        $site['title'] = $managementLang['management']['route_error_page_title'];

        $config = [
            'module' => [
                'headline' => $managementLang['management']['missing_module_title'],
                'heading' => $managementLang['management']['missing_module_heading'],
                'description' => sprintf($managementLang['management']['missing_module_description'], $target),
            ],
            'method' => [
                'headline' => $managementLang['management']['missing_method_title'],
                'heading' => $managementLang['management']['missing_method_heading'],
                'description' => sprintf($managementLang['management']['missing_method_description'], $target),
            ],
            'private_method' => [
                'headline' => $managementLang['management']['missing_method_title'],
                'heading' => $managementLang['management']['private_method_heading'],
                'description' => sprintf($managementLang['management']['private_method_description'], $target),
            ],
        ];

        $selected = isset($config[$type]) ? $config[$type] : $config['method'];

        $adminTemplate->setGlobal('site', $site);
        $adminTemplate->setGlobal('error_title', $managementLang['management']['route_error_page_title']);
        $adminTemplate->setGlobal('error_headline', $selected['headline']);
        $adminTemplate->setGlobal('error_heading', $selected['heading']);
        $adminTemplate->setGlobal('error_description', $selected['description']);
        $adminTemplate->setGlobal('hide_navbar', true);
        $adminTemplate->setGlobal('hide_search', true);
        $adminTemplate->setGlobal('show_fab', false);
        $adminTemplate->setGlobal('show_sidebar', false);
        $adminTemplate->setGlobal('show_navbar', false);
        $adminTemplate->setGlobal('show_filter_sheet', false);
        $adminTemplate->setGlobal('show_extra_sheets', false);
        $adminTemplate->setGlobal('show_wizards', false);
        $adminTemplate->tpl('pages/route_error');
        $adminTemplate->output();
    }

    public function __destruct()
    {
        try {
            $mod = Load::mod($this->class);
        } catch (ModException $e) {
            $e->show();
            exit();
        }

        try {
            $this->_isprivate();
        } catch (AmedException $e) {
            if ($this->hasAdminContext()) {
                $this->renderAdminRouteError('private_method', $this->method);
                exit();
            }
            $e->notcall();
            exit();
        }

        try {
            Run::calistir($mod, $this->method, $this->ext);
        } catch (RunException $e) {
            if ($this->hasAdminContext()) {
                $this->renderAdminRouteError('method', $this->class . '/' . $this->method);
                exit();
            }
            $e->show();
            exit();
        }

        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax_request']) && ($_POST['ajax_request'] === 'true' || $_POST['ajax_request'] === '1'))
        );

        $currentFullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');


        if (!$isAjax && !headers_sent()) {
            if (Kayit::varmi('AdminTemplate-lib')) {
                $admintemplate = Load::lib('AdminTemplate');
                if (isset($_SESSION)) {
                    $admintemplate->setGlobal('session', $_SESSION);
                    if (isset($_SESSION['flash'])) {
                        unset($_SESSION['flash']);
                    }
                }
                if (isset($_COOKIE)) {
                    $admintemplate->setGlobal('cookie', $_COOKIE);
                }
                $admintemplate->setGlobal('mod', $this->class);
                $admintemplate->setGlobal('site', Kayit::getir('site_config'));
                $admintemplate->setGlobal('link', $currentFullUrl);
                $admintemplate->ctx_add('site', Kayit::getir('site_config'));
                $admintemplate->output();
                unset($admintemplate);
            }

            if (Kayit::varmi('TwigTemplate-lib')) {
                $tema = Load::lib('TwigTemplate');
                if (isset($_SESSION)) {
                    $tema->setGlobal('session', $_SESSION);
                }
                if (isset($_COOKIE)) {
                    $tema->setGlobal('cookie', $_COOKIE);
                }
                $tema->setGlobal('mod', $this->class);
                $tema->setGlobal('link', $currentFullUrl);
                $tema->output();
                unset($tema);
            }
        }

        unset($this->url);
        if (isset($_SESSION)) {
            session_write_close();
        }
    }
}
