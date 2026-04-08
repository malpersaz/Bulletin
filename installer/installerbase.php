<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . DS . 'app' . DS . 'InstallerLang.php');
// Installer interface contract.
interface installerbaseinterface
{
    public function __construct();

    public function install();
}


class installerbase
{
    public $mod;
    public $mod_id;
    public $rolls;
    public $db;
    public $moduleRegistry;
    public $uninstall_blocked = FALSE;

    public function __construct()
    {
        $this->db = new Database();
        $this->moduleRegistry = Load::lib('ModuleRegistry');

    }

    protected function sync_module_registry_schema()
    {
        if ($this->check_table_exists('mods') === FALSE) {
            return TRUE;
        }

        $columns = array(
            'status' => "ALTER TABLE `mods` ADD COLUMN `status` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'installed' AFTER `mod_name`",
            'schema_ready' => "ALTER TABLE `mods` ADD COLUMN `schema_ready` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y' AFTER `status`",
            'installed_at' => "ALTER TABLE `mods` ADD COLUMN `installed_at` datetime DEFAULT NULL AFTER `schema_ready`",
            'updated_at' => "ALTER TABLE `mods` ADD COLUMN `updated_at` datetime DEFAULT NULL AFTER `installed_at`"
        );

        foreach ($columns as $column => $sql) {
            if ($this->check_column_exists('mods', $column) === TRUE) {
                continue;
            }

            if ($this->db->query($sql) === FALSE) {
                $this->error("Module registry column '" . $column . "' could not be added");
                return FALSE;
            }

            $this->oke("Module registry column '" . $column . "' added");
        }

        return TRUE;
    }

    protected function update_module_registry_state($module, $status, $schemaReady)
    {
        if ($this->check_table_exists('mods') === FALSE) {
            return TRUE;
        }

        if ($this->sync_module_registry_schema() === FALSE) {
            return FALSE;
        }

        $model = Load::model('mods');
        $model->where_mod_name($module)->gRo();
        if ($model->getSize() === 0) {
            return TRUE;
        }

        $model->sync();
        if ($this->check_column_exists('mods', 'status') === TRUE) {
            $model->status = $status;
        }
        if ($this->check_column_exists('mods', 'schema_ready') === TRUE) {
            $model->schema_ready = $schemaReady;
        }
        if ($this->check_column_exists('mods', 'updated_at') === TRUE) {
            $model->updated_at = NOW;
        }

        if ($model->update('mod_id') === FALSE) {
            $this->error("Module registry state for '" . $module . "' could not be updated");
            return FALSE;
        }

        return TRUE;
    }

    protected function check_module_registered($module)
    {
        if ($this->check_table_exists('mods') === FALSE) {
            return FALSE;
        }

        $sql = "SELECT mod_id FROM mods WHERE mod_name='" . addslashes($module) . "'";
        if ($this->check_column_exists('mods', 'status') === TRUE) {
            $sql .= " AND status IN ('installed', 'ready')";
        }
        $sql .= " LIMIT 1";
        $this->db->fetch_array($sql);
        return $this->db->getSize() !== 0;
    }

    protected function require_installed_modules(array $modules)
    {
        if (empty($modules)) {
            return TRUE;
        }

        if ($this->check_table_exists('mods') === FALSE) {
            $this->error("Dependency check failed because the 'mods' table is missing");
            return FALSE;
        }

        foreach ($modules as $module) {
            if ($this->check_module_registered($module) === FALSE) {
                $this->update_module_registry_state($this->mod, 'degraded', 'n');
                $this->error("Required module '" . $module . "' must be installed before '" . $this->mod . "'");
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function require_existing_tables(array $tables)
    {
        foreach ($tables as $table) {
            if ($this->check_table_exists($table) === FALSE) {
                $this->update_module_registry_state($this->mod, 'degraded', 'n');
                $this->error("Required table '" . $table . "' must exist before '" . $this->mod . "'");
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function enforce_install_dependencies()
    {
        $dependency = $this->moduleRegistry->getInstallDependencyConfig($this->mod);
        if ($dependency === null) {
            return TRUE;
        }

        if ($this->require_installed_modules($dependency['modules']) === FALSE) {
            return FALSE;
        }

        if ($this->require_existing_tables($dependency['tables']) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    protected function ensure_permission_infrastructure()
    {
        if (empty($this->rolls)) {
            return TRUE;
        }

        if ($this->mod === 'mod_user') {
            return TRUE;
        }

        if ($this->require_installed_modules(array('mod_user')) === FALSE) {
            $this->update_module_registry_state($this->mod, 'degraded', 'n');
            return FALSE;
        }

        if ($this->require_existing_tables(array('mod_user_roll', 'mod_user_perm')) === FALSE) {
            $this->update_module_registry_state($this->mod, 'degraded', 'n');
            return FALSE;
        }

        return TRUE;
    }

    protected function block_if_dependents_installed()
    {
        $this->uninstall_blocked = FALSE;
        $dependents = $this->moduleRegistry->getUninstallDependents($this->mod);
        if (empty($dependents)) {
            return TRUE;
        }

        foreach ($dependents as $module) {
            if ($this->check_module_registered($module) === TRUE) {
                $this->uninstall_blocked = TRUE;
                $this->error("Cannot uninstall '" . $this->mod . "' while dependent module '" . $module . "' is still installed");
                return FALSE;
            }
        }

        return TRUE;
    }


    public function check_table_exists($table)
    {

        if($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false){
            $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "'");
        }else{
            $this->db->fetch_array("show tables like '" . $table . "'");
        }

        if ($this->db->getSize() === 0) {
            return FALSE;
        }
        return TRUE;
    }

    public function check_column_exists($table, $column)
    {
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();

        if (strpos($adapter, 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
            $rows = $this->db->toArray();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row['name']) && $row['name'] === $column) {
                        return TRUE;
                    }
                }
            }
            return FALSE;
        }

        if (strpos($adapter, 'POSTGRESQL') !== false) {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
            $this->db->fetch_array($sql);
            return $this->db->getSize() !== 0;
        }

        $this->db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
        return $this->db->getSize() !== 0;
    }

    public function install_sql_file($file)
    {
        $mods_sql_text = $this->load_sql_file($this->mod . DS .$file);
        if ($this->db->query($mods_sql_text)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function load_sql_file($file)
    {
        if (file_exists($file) === FALSE) {
            throw new Exception($file . " Not Found");
        }
        return file_get_contents($file);
    }


    public function oke($message)
    {
        echo $this->mod . ": <font color='green'>" . $message . "</font>";
        echo "<br>";
    }

    public function error($message)
    {
        echo $this->mod . ": <font color='red'>" . $message . "</font>";
        echo "<br><font color='red'>Database Error: " . $this->db->sql_error() . "</font>";
        echo "<br>";
    }

    public function warning($message)
    {
        echo $this->mod . ": <font color='#ffd700'>" . $message . "</font>";
        echo "<br>";
    }

    public function system_mesaj($message)
    {
        echo "<font style='color:green;font-size:20px;'>" . $message . "</font>";
        echo "<br>";
    }

    /**
     * Inserts module into 'mods' table
     */
    protected function insert_mods()
    {
        if ($this->sync_module_registry_schema() === FALSE) {
            return FALSE;
        }

        // Create a fresh model for each module insertion.
        $checkModel = Load::model('mods');
        $checkModel->where_mod_name($this->mod)->gRo();

        if ($checkModel->getSize() === 0) {
            $model = Load::model('mods');
            $model->mod_id = NULL;
            $model->mod_name = $this->mod;
            if ($this->check_column_exists('mods', 'status') === TRUE) {
                $model->status = 'ready';
            }
            if ($this->check_column_exists('mods', 'schema_ready') === TRUE) {
                $model->schema_ready = 'y';
            }
            if ($this->check_column_exists('mods', 'installed_at') === TRUE) {
                $model->installed_at = NOW;
            }
            if ($this->check_column_exists('mods', 'updated_at') === TRUE) {
                $model->updated_at = NOW;
            }
            if ($model->save(TRUE) === FALSE) {
                $this->error(" ERROR OCCURRED WHILE ADDING MODULE TO 'mods' TABLE:" . print_r($model, TRUE));
                return FALSE;
            }
            $this->mod_id = $model->mod_id;
        } else {
            $checkModel->sync();
            if ($this->check_column_exists('mods', 'status') === TRUE) {
                $checkModel->status = 'ready';
            }
            if ($this->check_column_exists('mods', 'schema_ready') === TRUE) {
                $checkModel->schema_ready = 'y';
            }
            if ($this->check_column_exists('mods', 'updated_at') === TRUE) {
                $checkModel->updated_at = NOW;
            }
            $checkModel->update('mod_id');
            $result = $checkModel->toArray();
            $this->mod_id = $result['mod_id'];
        }

        if ($this->update_module_registry_state($this->mod, 'ready', 'y') === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Inserts roles into 'mod_user_roll' table
     */
    protected function insert_roll()
    {
        if ($this->ensure_permission_infrastructure() === FALSE) {
            return FALSE;
        }

        if (is_array($this->rolls)) {
            foreach ($this->rolls AS $roll) {
                $model = Load::model('mod_user.mod_user_roll');
                $model->where_mod_id_and_roll_name($this->mod_id, $roll)->limit(1)->getResultOne(TRUE, TRUE);
                if ($model->getSize() === 0) {
                    $model->roll_id = NULL;
                    $model->mod_id = $this->mod_id;
                    $model->roll_name = $roll;
                    if ($model->save(TRUE) === FALSE) {
                        $this->error(" ERROR OCCURRED WHILE ADDING ROLES:" . print_r($model));
                        return FALSE;
                    }
                    $this->oke("Role '" . $roll . "' added");
                } else {
                    $model->sync();
                    $this->warning("Role '" . $roll . "' already exists in database");
                }
                if ($this->addperm($model->roll_id, $model->roll_name) === FALSE) {
                    return FALSE;
                }
            }
        } else {
            $this->warning("No roles found to add!");
        }
        return TRUE;
    }

    /**
     * Inserts permissions into 'mod_user_perm' table
     */
    protected function addperm($roll_id, $roll_name)
    {
        $pmodel = Load::model('mod_user.mod_user_perm');

        $pmodel->where_mod_id_and_roll_id_and_group_id($this->mod_id, $roll_id, '1')->limit(1)->getResultOne(TRUE, TRUE);
        if ($pmodel->getSize() === 0) {
            $pmodel->group_id = 1;
            $pmodel->roll_id = $roll_id;
            $pmodel->mod_id = $this->mod_id;
            if ($pmodel->save() === FALSE) {
                $this->error(" ERROR OCCURRED WHILE ADDING PERMISSIONS:" . print_r($pmodel));
                return FALSE;
            }
            $this->oke("Role '" . $roll_name . "' added as permission for Group ID 1");
        } else {
            $this->warning("Role '" . $roll_name . "' already exists as permission for Group ID 1");
        }
        return TRUE;
    }

    protected function create_tables()
    {
        if ($this->enforce_install_dependencies() === FALSE) {
            return FALSE;
        }

        foreach ($this->tablolar AS $tablo) {
            if ($this->check_table_exists($tablo) === TRUE) {
                $this->warning(" Table '" . $tablo . "' is already installed in database");
            } elseif ($this->install_sql_file($tablo . ".sql")) {
                $this->oke(" File '" . $tablo . ".sql' added to database");
            } else {
                $this->error(" Database table '" . $tablo . "' could not be created");
                return FALSE;
            }
        }
    }

    // Drop table.
    public function drop_table($table)
    {
        if ($this->db->query("DROP TABLE " . $table)) {
            return TRUE;
        }
        return FALSE;
    }

    // Remove tables.
    protected function remove_tables()
    {
        if ($this->block_if_dependents_installed() === FALSE) {
            return FALSE;
        }

        foreach ($this->tablolar AS $tablo) {
            if ($this->check_table_exists($tablo) === FALSE) {
                $this->warning(" Table '" . $tablo . "' is not installed in database");
            } elseif ($this->drop_table($tablo)) {
                $this->oke(" Table '" . $tablo . "' dropped");
            }
        }
    }

    protected function remove_mods()
    {
        if ($this->uninstall_blocked === TRUE) {
            return FALSE;
        }

        $model = Load::model('mods');
        $model->where_mod_name($this->mod)->gRo();
        if ($model->getSize() !== 0) {
            $model->sync();
            $this->mod_id = $model->mod_id;
            if ($model->delete()) {
                $this->oke(" Module removed from 'mods' table");
            }
            $this->mod_id = $model->mod_id;
        } else {
            $this->warning(" Module not found in 'mods' table");
        }
        return TRUE;
    }

    protected function remove_rool()
    {
        if ($this->uninstall_blocked === TRUE) {
            return FALSE;
        }

        $model = Load::model('mod_user.mod_user_roll');
        if ($this->mod_id == '') {
            return TRUE;
        }
        $model->where_mod_id($this->mod_id);
        if ($model->delete()) {
            $this->oke(" Roles belonging to the module deleted from 'mod_user_roll' table");
        }
        return TRUE;
    }

    protected function remove_perm()
    {
        if ($this->uninstall_blocked === TRUE) {
            return FALSE;
        }

        $model = Load::model('mod_user.mod_user_perm');
        if ($this->mod_id == '') {
            return TRUE;
        }
        $model->where_mod_id($this->mod_id);
        if ($model->delete()) {
            $this->oke(" Permissions belonging to the module deleted from 'mod_user_perm' table");
        }
        return TRUE;
    }
}
