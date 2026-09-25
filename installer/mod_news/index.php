<?php

class mod_news extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_news';
        // Slider is part of the content schema, not a separate module table.
        $this->tables = array(
            'mod_news',
            'newses'
        );
        // Hero slider management reuses edit permission on content records.
        $this->roles = array(
            'add', 'edit',  'delete', 'list','gallery','file','edit_status','change_status','approval', 'moderate_edit','moderate_delete','video_download'
        );
    }

    public function install()
    {
        // Create the base content table together with the updated view definition.
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Register module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Register roles and permissions
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
