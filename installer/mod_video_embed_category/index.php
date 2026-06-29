<?php

class mod_video_embed_category extends installerbase implements installerbaseinterface
{
    public $tablolar;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_video_embed_category';
        $this->tablolar = array(
            'mod_video_embed_category'
        );
        $this->rolls = array(
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'
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
        if ($this->installer_seed_category_bootstrap('mod_video_embed_category', 'videos') === FALSE) {
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
