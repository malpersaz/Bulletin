<?php

class AmedException extends Exception
{
    public function notCall()
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
        $database = Registry::get('database');
        $dbConfig = Registry::get('db_config');

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
        $bootstrap = Registry::get('i18n_bootstrap');
        $fallback = [
            'default_language' => 'ku',
            'active_codes' => ['ku'],
        ];

        if (!is_array($bootstrap)) {
            return $fallback;
        }

        $activeCodes = [];
        if (isset($bootstrap['active_langs']) && is_array($bootstrap['active_langs'])) {
            $activeCodes = array_values(array_unique(array_filter($bootstrap['active_langs'], 'is_string')));
        }

        if ($activeCodes === []) {
            $activeCodes = $fallback['active_codes'];
        }

        $defaultLanguage = isset($bootstrap['default_lang']) && is_string($bootstrap['default_lang']) && in_array($bootstrap['default_lang'], $activeCodes, TRUE)
            ? $bootstrap['default_lang']
            : $activeCodes[0];

        return [
            'default_language' => $defaultLanguage,
            'active_codes' => $activeCodes,
        ];
    }

    private function buildLanguageRegistryState()
    {
        $bootstrapState = $this->getBootstrapLanguageState();
        $fallbackCodes = $bootstrapState['active_codes'];
        $defaultLanguage = $bootstrapState['default_language'];
        $state = [
            'active_codes' => $fallbackCodes,
            'default_language' => $defaultLanguage,
            'available_languages' => [],
        ];

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
            $activeCodes = [];
            $availableLanguages = [];

            foreach ($rows as $row) {
                $isActive = !$hasStatusColumn || !isset($row['status']) || $row['status'] === 'y';
                if ($isActive) {
                    $activeCodes[] = $row['seo'];
                    $availableLanguages[] = [
                        'code' => $row['seo'],
                        'name' => $row['name'],
                    ];
                }

                if ($hasDefaultColumn && isset($row['is_default']) && $row['is_default'] === 'y') {
                    $defaultLanguage = $row['seo'];
                }
            }

            if ($activeCodes === []) {
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

        Registry::set('active_langs', $languageState['active_codes']);
        Registry::set('default_lang', $languageState['default_language']);
    }

    private function ensureSiteNameColumn($db)
    {
        if (!$db) {
            return false;
        }

        $table = 'mod_settings';
        $column = 'site_name';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $db->getAdapterType();
        $exists = false;

        try {
            if (strpos($adapter, 'PDO_SQLITE') !== false) {
                $db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
                $rows = $db->toArray();
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (isset($row['name']) && $row['name'] === $column) {
                            $exists = true;
                            break;
                        }
                    }
                }
            } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
                $db->fetch_array($sql);
                $exists = ($db->getSize() !== 0);
            } else {
                $db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
                $exists = ($db->getSize() !== 0);
            }

            if (!$exists) {
                if (strpos($adapter, 'PDO_SQLITE') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN site_name VARCHAR(255) DEFAULT NULL";
                } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN site_name VARCHAR(255) DEFAULT NULL";
                } else {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `site_name` VARCHAR(255) DEFAULT NULL";
                }
                $db->query($sql);
                $exists = true;
            }
        } catch (Exception $e) {
            $exists = false;
        }

        return $exists;
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
            $db = Load::lib('Database');
            $this->ensureSiteNameColumn($db);
            $this->ensureAppTemplateColumn($db);

            $settingsModel = Load::model('mod_settings');
            $settingsModel->where_lang($langCode)->gRo();

            if ($settingsModel->getSize() > 0) {
                $settingsModel->sync();
                $this->site = array_merge($this->site, $settingsModel->toArray());
            }

            $activeTemplate = !empty($this->site['app_template']) ? basename(Req::Clear($this->site['app_template'])) : 'amida';
            $cookieTheme = isset($_COOKIE['theme']) ? basename(Req::Clear($_COOKIE['theme'])) : null;
            if ($cookieTheme && file_exists(template_dir . $cookieTheme) && is_dir(template_dir . $cookieTheme)) {
                $activeTemplate = $cookieTheme;
            }
            $fullTemplatePath = template_dir . $activeTemplate;
            if (!file_exists($fullTemplatePath) || !is_dir($fullTemplatePath)) {
                $activeTemplate = 'amida';
            }
            $this->site['app_template'] = $activeTemplate;
            Registry::set('app_template', $activeTemplate);

            if (empty($this->site['site_name'])) {
                $default_lang = Registry::get('default_lang');
                if (!is_string($default_lang) || $default_lang === '') {
                    $default_lang = 'tr';
                }
                if ($default_lang !== $langCode) {
                    $fallbackModel = Load::model('mod_settings');
                    $fallbackModel->where_lang($default_lang)->gRo();
                    if ($fallbackModel->getSize() > 0) {
                        $fallbackModel->sync();
                        $fallback_data = $fallbackModel->getResultOne()->toArray();
                        if (!empty($fallback_data['site_name'])) {
                            $this->site['site_name'] = $fallback_data['site_name'];
                        }
                    }
                }
            }

            if (empty($this->site['site_name'])) {
                try {
                    $db->fetch_array("SELECT site_name FROM mod_settings WHERE site_name IS NOT NULL AND site_name != '' LIMIT 1");
                    if ($db->getSize() > 0) {
                        $this->site['site_name'] = $db->toArray()[0]['site_name'];
                    }
                } catch (Exception $db_e) {
                }
            }

            $brandAssetFields = ['favicon_path', 'favicon_96', 'apple_touch_icon', 'site_webmanifest', 'logo_path', 'logo_symbol', 'site_icon'];
            $needsAssetFallback = false;
            foreach ($brandAssetFields as $assetField) {
                if (empty($this->site[$assetField])) {
                    $needsAssetFallback = true;
                    break;
                }
            }

            if ($needsAssetFallback) {
                try {
                    $db->fetch_array("SELECT " . implode(', ', $brandAssetFields) . " FROM mod_settings WHERE favicon_path IS NOT NULL AND favicon_path != '' LIMIT 1");
                    if ($db->getSize() > 0) {
                        $assetRow = $db->toArray()[0];
                        foreach ($brandAssetFields as $assetField) {
                            if (empty($this->site[$assetField]) && !empty($assetRow[$assetField])) {
                                $this->site[$assetField] = $assetRow[$assetField];
                            }
                        }
                    }
                } catch (Exception $asset_e) {
                }
            }

            $this->site['name'] = !empty($this->site['site_name']) ? $this->site['site_name'] : ($this->site['title'] ?? '');

            $settingsDir = root_dir . 'static' . DIRECTORY_SEPARATOR . 'mod_settings' . DIRECTORY_SEPARATOR;
            if (!empty($this->site['site_webmanifest'])) {
                $manifestPath = $settingsDir . $this->site['site_webmanifest'];
                if (!file_exists($manifestPath) || filesize($manifestPath) < 5) {
                    try {
                        $manifestGenerator = Load::lib('BrandAssetGenerator');
                        $manifestGenerator->ensureManifestExists([
                            'title' => $this->site['name'],
                            'site_name' => $this->site['site_name'] ?? '',
                        ]);
                    } catch (Exception $m_e) {
                    }
                }
                if (!file_exists($manifestPath) || filesize($manifestPath) < 5) {
                    $this->site['site_webmanifest'] = '';
                }
            }
        } catch (Exception $exception) {
        }
    }

    private function ensureAppTemplateColumn($db)
    {
        if (!$db) {
            return false;
        }

        $table = 'mod_settings';
        $column = 'app_template';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $db->getAdapterType();
        $exists = false;

        try {
            if (strpos($adapter, 'PDO_SQLITE') !== false) {
                $db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
                $rows = $db->toArray();
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (isset($row['name']) && $row['name'] === $column) {
                            $exists = true;
                            break;
                        }
                    }
                }
            } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
                $db->fetch_array($sql);
                $exists = ($db->getSize() !== 0);
            } else {
                $db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
                $exists = ($db->getSize() !== 0);
            }

            if (!$exists) {
                if (strpos($adapter, 'PDO_SQLITE') !== false || strpos($adapter, 'POSTGRESQL') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN app_template VARCHAR(64) DEFAULT 'amida'";
                } else {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `app_template` VARCHAR(64) DEFAULT 'amida'";
                }
                $db->query($sql);
                $exists = true;
            }
        } catch (Exception $e) {
            $exists = false;
        }

        return $exists;
    }

    public function __construct()
    {
        Load::lib('Session');
        $this->url = Load::lib('Url');
        $url_config = Registry::get('url_config');
        $this->site = Registry::get('site_config');
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
                    $active_langs = Registry::get('active_langs');
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

                Registry::set('site_config', $this->site);
            } catch (Exception $e) {
                Registry::set('site_config', $this->site);
            }
        } else {
            Registry::set('site_config', $this->site);
        }

        if (Registry::get('i18n') === true) {
            $activeLangs = Registry::get('active_langs');
            if (is_array($activeLangs) && in_array($this->url->path['0'], $activeLangs, TRUE)) {
                $this->site['lang'] = $this->url->path['0'];
                $this->site['saddress'] = $this->site['address'] . $this->url->path['0'] . '/';
                $lang_len = mb_strlen($this->url->path['0'], "UTF-8");
                if (strlen($this->url->link) <= $lang_len) {
                    $this->url->link = '';
                } else {
                    $this->url->link = substr($this->url->link, $lang_len + 1);
                }

                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    if (!isset($_COOKIE['lang']) || $_COOKIE['lang'] !== $this->site['lang']) {
                        @setcookie('lang', $this->site['lang'], time() + (86400 * 30), '/');
                    }
                }
            } else {
                $cookieLang = (isset($_COOKIE['lang']) && is_string($_COOKIE['lang'])) ? $_COOKIE['lang'] : null;
                if ($cookieLang && is_array($activeLangs) && in_array($cookieLang, $activeLangs, TRUE)) {
                    $this->site['lang'] = $cookieLang;
                } else {
                    $this->site['lang'] = isset($this->site['default_lang']) ? $this->site['default_lang'] : $this->site['lang'];
                }
                $this->site['saddress'] = $this->site['address'] . $this->site['lang'] . '/';
            }
            Registry::set('lang', $this->site['lang']);
        }

        $this->mergeSettingsForLanguage($this->site['lang']);

        $this->site['url'] = trim($this->url->link, '/');
        Registry::set('site_config', $this->site);

        // Canonical homepage and clean URL 301/302 redirect normalization
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax_request']) && ($_POST['ajax_request'] === 'true' || $_POST['ajax_request'] === '1'))
        );

        if (PHP_SAPI !== 'cli' && !$isAjax && !headers_sent()) {
            $rawLink = trim((string) $this->url->link, '/');
            $rawPath0 = isset($this->url->path[0]) ? $this->url->path[0] : '';
            $isI18n = (Registry::get('i18n') === true);
            $queryString = (!empty($_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : '';

            // 1. Redirect redundant /index or /<lang>/index to canonical homepage
            if ($rawLink === 'index') {
                $targetUrl = $isI18n ? $this->site['saddress'] : $this->site['address'];
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $targetUrl . $queryString);
                exit;
            }

            // 2. Redirect bare root "/" to active/preferred language "/<lang>/" when i18n is enabled
            if ($isI18n && $this->url->root() && $rawPath0 === '') {
                $targetUrl = $this->site['saddress'];
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $targetUrl . $queryString);
                exit;
            }

            // 3. Redirect bare language code "/<lang>" (without trailing slash) to canonical "/<lang>/"
            if ($isI18n && !empty($rawPath0) && in_array($rawPath0, (array) Registry::get('active_langs'), true)) {
                $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
                // Only when the whole path is the language segment itself ("/tr").
                // Deeper paths whose LAST segment equals a language code
                // ("/tr/mod_settings/edit/tr") must route to their module, not home.
                if ($reqPath !== '' && substr($reqPath, -1) !== '/' && $this->url->link === '') {
                    $targetUrl = $this->site['saddress'];
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $targetUrl . $queryString);
                    exit;
                }
            }
        }

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
                    $safeLangUrl = addslashes((isset($this->site['lang']) ? $this->site['lang'] . '/' : '') . $cleanRequestUrl);
                    $db->fetch_array("SELECT * FROM mod_redirect WHERE old_url = '" . $safeUrl . "' OR old_url = '/" . $safeUrl . "' OR old_url = '" . $safeLangUrl . "' OR old_url = '/" . $safeLangUrl . "' LIMIT 1");
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
                            $activeLangs = Registry::get('active_langs');
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
                    return $this->parseRoute($val);
                }
            }
        }
        unset($url_config);
        $this->parseRoute($this->url->link);
    }


    public function parseRoute($route)
    {
        if (strpos($route, '/') !== false) {
            $val = explode('/', $route);
            $this->class = (isset($val['0']) and $val['0'] != '') ? $val['0'] : Registry::get('app_default_mod');
            $this->method = (isset($val['1']) and $val['1'] != '') ? $val['1'] : Registry::get('app_default_function');
            $ext = array_slice($val, 2);
            if (isset($ext[0])) {
                foreach ($ext as $k) {
                    if ($k != '') {
                        $this->ext[] = $k;
                    }
                }
            }
        } else {
            if ($this->url->root() || $route === '' || $route === false) {
                $this->class = Registry::get('app_default_mod');
                $this->method = Registry::get('app_default_function');
            } elseif ($route !== '' && is_dir(root_dir . 'mod' . DS . $route) && file_exists(root_dir . 'mod' . DS . $route . DS . 'index.php')) {
                $this->class = $route;
                $this->method = Registry::get('app_default_function');
            } elseif (strpos((string) $route, '?') === false && (string) $this->url->link !== '') {
                $this->class = Registry::get('app_default_mod');
                $this->method = $this->url->link;
            } else {
                $this->class = Registry::get('app_default_mod');
                $this->method = Registry::get('app_default_function');
            }
        }
    }

    public function _isPrivate()
    {
        if (substr($this->method, 0, 1) === '_') {
            throw new AmedException($this->method);
        }
        return true;
    }

    private function hasAdminContext()
    {
        return Registry::has('AdminTemplate-lib');
    }

    private function renderAdminRouteError($type, $target)
    {
        header("HTTP/1.1 404 Not Found");

        $managementLang = Load::lang('management');
        $adminTemplate = Load::lib('AdminTemplate');
        $adminTemplate->lang($managementLang);

        $site = Registry::get('site_config');
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
            $this->_isPrivate();
        } catch (AmedException $e) {
            if ($this->hasAdminContext()) {
                $this->renderAdminRouteError('private_method', $this->method);
                exit();
            }
            $e->notCall();
            exit();
        }

        try {
            Run::execute($mod, $this->method, $this->ext);
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


        if (!headers_sent()) {
            if (Registry::has('AdminTemplate-lib')) {
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
                $admintemplate->setGlobal('site', Registry::get('site_config'));
                $admintemplate->setGlobal('link', $currentFullUrl);
                $admintemplate->ctx_add('site', Registry::get('site_config'));
                $admintemplate->output();
                unset($admintemplate);
            }

            if (Registry::has('TwigTemplate-lib')) {
                $twigTemplate = Load::lib('TwigTemplate');
                if (isset($_SESSION)) {
                    $twigTemplate->setGlobal('session', $_SESSION);
                }
                if (isset($_COOKIE)) {
                    $twigTemplate->setGlobal('cookie', $_COOKIE);
                }
                $twigTemplate->setGlobal('mod', $this->class);
                $twigTemplate->setGlobal('link', $currentFullUrl);
                $twigTemplate->output();
                unset($twigTemplate);
            }
        }

        unset($this->url);
        if (isset($_SESSION)) {
            session_write_close();
        }
    }
}
