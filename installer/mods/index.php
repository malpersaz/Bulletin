<?php

class mods extends installerbase implements installerbaseinterface
{
    public $tablolar;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mods';
        $this->tablolar = array(
            'mods',
            'sys_view_logs'
        );
    }

    public function install()
    {
        foreach ($this->tablolar as $table) {
            if ($this->check_table_exists($table) === TRUE) {
                $this->warning(" '" . $table . "' table is already installed in the database");
                continue;
            }

            if ($this->install_sql_file($table . '.sql') === FALSE) {
                return FALSE;
            }

            $this->oke(" '" . $table . ".sql' file added to the database");
        }

        if ($this->sync_module_registry_schema() === FALSE) {
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
