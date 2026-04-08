<?php

class mods extends installerbase implements installerbaseinterface
{
    public $model;
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

        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }

        if ($this->sync_core_module_registry_entries() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    private function sync_core_module_registry_entries()
    {
        return $this->ensure_module_registry_entry('lang');
    }

    private function ensure_module_registry_entry($moduleName)
    {
        $model = Load::model('mods');
        $model->where_mod_name($moduleName)->gRo();

        if ($model->getSize() !== 0) {
            $model->sync();
            if ($this->check_column_exists('mods', 'status') === TRUE) {
                $model->status = 'ready';
            }
            if ($this->check_column_exists('mods', 'schema_ready') === TRUE) {
                $model->schema_ready = 'y';
            }
            if ($this->check_column_exists('mods', 'updated_at') === TRUE) {
                $model->updated_at = NOW;
            }

            return $model->update('mod_id') !== FALSE;
        }

        $record = Load::model('mods');
        $record->mod_id = NULL;
        $record->mod_name = $moduleName;
        if ($this->check_column_exists('mods', 'status') === TRUE) {
            $record->status = 'ready';
        }
        if ($this->check_column_exists('mods', 'schema_ready') === TRUE) {
            $record->schema_ready = 'y';
        }
        if ($this->check_column_exists('mods', 'installed_at') === TRUE) {
            $record->installed_at = NOW;
        }
        if ($this->check_column_exists('mods', 'updated_at') === TRUE) {
            $record->updated_at = NOW;
        }

        return $record->save() !== FALSE;
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
