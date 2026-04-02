<?php

class mod_document extends installerbase implements installerbaseinterface
{
    public $tablolar;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_document';
        $this->tablolar = array(
            'mod_document'
        );
        $this->rolls = array(
            'add', 'edit',  'delete', 'list', 'moderate_edit', 'moderate_delete','file'
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
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }



    public function uninstall()
    {

        $this->remove_tables();
        $this->remove_mods();
        $this->remove_rool();
        $this->remove_perm();
        return TRUE;
    }
}
