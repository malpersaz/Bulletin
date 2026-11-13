<?php

class mod_socialmedia_post extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_socialmedia_post';
        $this->tables = [
            'mod_socialmedia_post',
            'mod_socialmedia_post_link',
        ];
        $this->roles = [
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete',
        ];
    }

    public function install()
    {
        if ($this->create_tables() === false) {
            return false;
        }
        if ($this->insert_mods() === false) {
            return false;
        }
        if ($this->insert_roles() === false) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        BridgeCleanupService::cleanModule($this->mod);
        $this->remove_tables();
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return true;
    }
}
