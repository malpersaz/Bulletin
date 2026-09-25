<?php

class mod_seo extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_seo';
        $this->tables = array();
        $this->roles = array(
            'list', 'settings', 'google_settings'
        );
    }

    public function install()
    {
        // Add module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Insert roles and permissions
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function uninstall()
    {
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return TRUE;
    }
}
