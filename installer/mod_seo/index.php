<?php

class mod_seo extends installerbase implements installerbaseinterface
{
    public $tablolar;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_seo';
        $this->tablolar = array();
        $this->rolls = array(
            'list', 'settings'
        );
    }

    public function install()
    {
        // Add Module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Add Roles and Permissions
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function uninstall()
    {
        $this->remove_mods();
        $this->remove_rool();
        $this->remove_perm();
        return TRUE;
    }
}
