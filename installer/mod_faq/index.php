<?php

class mod_faq extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_faq';
        $this->tables = array(
            'mod_faq'
        );
        $this->roles = array(
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'
        );
    }

    public function install()
    {
        // Create tables.
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Register the module.
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Register roles and permissions.
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
