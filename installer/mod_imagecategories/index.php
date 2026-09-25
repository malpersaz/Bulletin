<?php

class mod_imagecategories extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_imagecategories';
        $this->tables = array(
            'mod_imagecategories',
            'pictures'
        );
        $this->roles = array(
            'add', 'edit',  'delete', 'list', 'moderate_edit','moderate_delete','gallery'
        );
    }

    public function install()
    {
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }
        if ($this->installer_seed_category_bootstrap('mod_imagecategories') === FALSE) {
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
