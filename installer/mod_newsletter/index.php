<?php

/**
 * Newsletter Module Installer Class
 * Amed Framework (AKM Bulletin CMS)
 */
class mod_newsletter extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_newsletter';
        $this->tables = array(
            'mod_newsletter'
        );
        $this->roles = array(
            'list', 'delete'
        );
    }

    public function install()
    {
        // Create database tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Insert module definition
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
        $this->remove_tables();
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return TRUE;
    }
}
