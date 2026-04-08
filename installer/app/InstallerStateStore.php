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
        $state = $this->session->getir(self::SESSION_KEY);
        if (!is_array($state)) {
            $state = $this->getDefaultState();
            $this->session->ac(self::SESSION_KEY, $state);
        }

        return array_merge($this->getDefaultState(), $state);
    }

    public function replace(array $state)
    {
        $this->session->ac(self::SESSION_KEY, $state);
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
        return array(
            'step' => 'welcome',
            'installer_language' => 'en',
            'project_name' => 'Rojname',
            'project_default_language' => 'ku',
            'requested_modules' => array(),
            'resolved_modules' => array(),
            'sample_content' => 'no',
            'install_result' => null,
        );
    }
}
