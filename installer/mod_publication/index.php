<?php

class mod_publication extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_publication';
        $this->tablolar = array(
            'mod_publication',
            'mod_publication_contents'
        );
        $this->rolls = array(
            'add', 'edit', 'delete', 'list', 'gallery', 'file', 'edit_status', 'change_status', 'approval', 'moderate_edit', 'moderate_delete', 'video_download'
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
