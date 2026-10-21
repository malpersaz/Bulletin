<?php

/**
 * MCP Module Installer Class
 * Amed Framework (AKM Bulletin CMS)
 */
class mod_mcp extends installerbase implements installerbaseinterface
{
    public $tables;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_mcp';
        $this->tables = array(
            'mod_mcp_api_key',
            'mod_mcp_oauth_client',
            'mod_mcp_oauth_code',
            'mod_mcp_oauth_token'
        );
        $this->roles = array(
            'list', 'add', 'edit', 'delete'
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
        // Insert roles and permissions (mod_user_role + mod_user_perm, group 1)
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
