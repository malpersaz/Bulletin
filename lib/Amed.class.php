<?php

class AmedException extends Exception
{
    public function notcall()
    {
        echo $this->getMessage() . " Methodu Çalıştırılamaz";
    }
}

class Amed
{
    private $method;
    private $class;
    private $ext;
    private $site;
    private $url;

    public function __construct()
    {
        Load::lib('Session');
        $this->url = Load::lib('Url');
        $url_config = Kayit::getir('url_config');
        $this->site = Kayit::getir('site_config');
        
        // Load settings module to get ALL settings globally
        try {
            $settings_model = Load::model('mod_settings');
            // If lang is set in site config, use it, otherwise fallback
            $lang_code = isset($this->site['lang']) ? $this->site['lang'] : 'tr'; 
            $settings_model->where_lang($lang_code)->gRo();
            
            if ($settings_model->getSize() > 0) {
                $settings_model->sync();
                
                // Merge all settings from DB into site config
                // DB settings overwrite file config settings
                $db_settings = $settings_model->toArray();
                $this->site = array_merge($this->site, $db_settings);
            }
            // Load available languages
            try {
                $lang_model = Load::model('lang');
                $lang_model->all();
                $available_langs = [];
                $valid_langs = Kayit::getir('valid_lang');
                if ($lang_model->getSize() > 0) {
                    foreach ($lang_model->toArray() as $l) {
                        if (in_array($l['seo'], $valid_langs)) {
                            $available_langs[] = ['code' => $l['seo'], 'name' => $l['name']];
                        }
                    }
                }
                $this->site['available_languages'] = $available_langs;
            } catch (Exception $lang_e) {
                // Ignore if lang model is missing
            }

            // Update the registry so it persists globally
            Kayit::yenile('site_config', $this->site);
            
        } catch (Exception $e) {
            // If mod_settings model fails to load, ignore
             Kayit::yenile('site_config', $this->site);
        }

        if (Kayit::getir('i18n') === true) {
            if (in_array($this->url->path['0'], Kayit::getir('valid_lang'))) {
                $this->site['lang'] = $this->url->path['0'];
                $this->site['sadres'] = $this->site['adres'] . $this->url->path['0'] . '/';
                $lang_len = mb_strlen($this->url->path['0'], "UTF-8");
                $this->url->link = substr($this->url->link, $lang_len + 1);
            } else {
                $this->site['sadres'] = $this->site['adres'] . $this->site['lang'] . '/';
            }
            Kayit::yenile('lang', $this->site['lang']);
            Kayit::yenile('site_config', $this->site);
        }


        if (is_array($url_config)) {
            // Eğer Özel url tanımlanmışsa değeleri değiştirelim.
            foreach ($url_config as $key => $val) {

                // :any ile .+ yı :num ile [0-9]+'yı değiştirelim
                $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

                // Eğer url de özel url bulunursa
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
        // Eğer Gelen Değerin içinde / varmı bakıyoruz.
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

    public function __destruct()
    {
        //     $data = [
        //         'm' => $this->class . ":" . $this->method,
        //        'u' => $_SESSION['username'],
        //      'd' => $_SERVER['HTTP_HOST'],
        //    't' => $this->site['app_template']
        //   ];

        //var_dump($data);
        try {
            $mod = Load::mod($this->class);
        } catch (ModException $e) {
            // AmedLog::info('module_not_found', $data);

            $e->show();
            exit();
        }

        try {
            $this->_isprivate();
        } catch (AmedException $e) {
            $e->notcall();
            exit();
        }

        try {
            Run::calistir($mod, $this->method, $this->ext);
        } catch (RunException $e) {

            // AmedLog::info('module_not_found', $data);

            $e->show();
            exit();
        }

        // AmedLog::info('req', $data);


        // Skip template output for AJAX requests to prevent corrupting JSON responses
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
                //var_dump($tema);
                if (isset($_SESSION)) {
                    $tema->setGlobal('session', $_SESSION);
                }
                if (isset($_COOKIE)) {
                    $tema->setGlobal('cookie', $_COOKIE);
                }
                $tema->setGlobal('mod', $this->class);
                //$tema->setGlobal('site', Kayit::getir('site_config'));
                $tema->setGlobal('link', $currentFullUrl);
                //$tema->ctx_add('site', Kayit::getir('site_config'));
                $tema->output();
                unset($tema);
            }
        }

        unset($this->url);
        // Eğer Session varsa tüm objeleri öldürmeden sessionu kaydedelim.
        if (isset($_SESSION)) {
            session_write_close();
        }
    }
}
