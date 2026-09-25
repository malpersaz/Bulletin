<?php

class InstallerStateStore
{
    const SESSION_KEY = 'installer_mobile_state';

    private $session;

    public function __construct()
    {
        $this->session = Load::lib('Session');
    }

    public function getState()
    {
        $state = null;

        if (Registry::has(self::SESSION_KEY) === TRUE) {
            $state = Registry::get(self::SESSION_KEY);
        }

        if (!is_array($state)) {
            $state = $this->session->get(self::SESSION_KEY);
        }

        if (!is_array($state)) {
            $state = $this->getDefaultState();
            $this->replace($state);
        }

        return array_merge($this->getDefaultState(), $state);
    }

    public function replace(array $state)
    {
        $this->session->set(self::SESSION_KEY, $state);
        Registry::set(self::SESSION_KEY, $state);
    }

    public function merge(array $changes)
    {
        $state = array_merge($this->getState(), $changes);
        $this->replace($state);
        return $state;
    }

    public function reset()
    {
        $this->replace($this->getDefaultState());
        return $this->getState();
    }

    public function getDefaultState()
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $defaultAddress = rtrim($protocol . $host, '/') . '/';

        return array(
            'step' => 'language',
            'installer_language' => 'en',
            'installer_language_confirmed' => false,
            'project_name' => installer_site_build_name(),
            'project_default_language' => 'ku',
            'project_description' => '',
            'project_owner_name' => '',
            'project_established_year' => '',
            'project_phone' => '',
            'project_phone_country_code' => '+90',
            'project_whatsapp' => '',
            'project_whatsapp_country_code' => '+90',
            'project_email' => '',
            'project_office_address' => '',
            'project_work_address' => '',
            'project_address' => $defaultAddress,
            'database_adapter' => 'PDO_MYSQL',
            'database_host' => 'localhost',
            'database_user' => '',
            'database_pass' => '',
            'database_name' => '',
            'database_path' => installer_default_sqlite_path(),
            'database_configured' => false,
            'requested_modules' => array(),
            'resolved_modules' => array(),
            'sample_content' => 'no',
            'install_result' => null,
        );
    }
}
