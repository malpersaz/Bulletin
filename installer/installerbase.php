<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . DS . 'app' . DS . 'InstallerLang.php');

function installer_site_build_name()
{
    if (Registry::has('site_config') !== TRUE) {
        $siteConfigFile = defined('root_dir') ? root_dir . 'config' . DS . 'site.config.php' : null;
        if (is_string($siteConfigFile) && file_exists($siteConfigFile)) {
            require_once($siteConfigFile);
        }
    }

    if (Registry::has('site_config') === TRUE) {
        $siteConfig = Registry::get('site_config');
        if (is_array($siteConfig)) {
            $build = isset($siteConfig['build']) ? trim((string) $siteConfig['build']) : '';
            if ($build !== '') {
                return $build;
            }
        }
    }

    return '';
}

function installer_get_project_setup_state()
{
    $defaultLang = Registry::has('lang') ? (string) Registry::get('lang') : 'en';
    $defaults = array(
        'project_name' => installer_site_build_name(),
        'project_default_language' => $defaultLang,
        'project_description' => '',
        'project_owner_name' => '',
        'project_established_year' => '',
        'project_phone' => '',
        'project_phone_country_code' => '',
        'project_whatsapp' => '',
        'project_whatsapp_country_code' => '',
        'project_email' => '',
        'project_office_address' => '',
        'project_work_address' => '',
    );

    if (Registry::has('installer_project_setup') === TRUE) {
        $state = Registry::get('installer_project_setup');
        if (is_array($state)) {
            return array_merge($defaults, $state);
        }
    }

    $session = Load::lib('Session');
    $state = $session->get('installer_mobile_state');
    if (!is_array($state)) {
        return $defaults;
    }

    return array_merge($defaults, $state);
}

function installer_extract_project_setup_state(array $state)
{
    $defaultLang = isset($state['installer_language']) ? (string) $state['installer_language'] : (Registry::has('lang') ? (string) Registry::get('lang') : 'en');
    return array(
        'project_name' => isset($state['project_name']) ? (string) $state['project_name'] : installer_site_build_name(),
        'project_default_language' => isset($state['project_default_language']) ? (string) $state['project_default_language'] : $defaultLang,
        'project_description' => isset($state['project_description']) ? (string) $state['project_description'] : '',
        'project_owner_name' => isset($state['project_owner_name']) ? (string) $state['project_owner_name'] : '',
        'project_established_year' => isset($state['project_established_year']) ? (string) $state['project_established_year'] : '',
        'project_phone' => isset($state['project_phone']) ? (string) $state['project_phone'] : '',
        'project_phone_country_code' => isset($state['project_phone_country_code']) ? (string) $state['project_phone_country_code'] : '',
        'project_whatsapp' => isset($state['project_whatsapp']) ? (string) $state['project_whatsapp'] : '',
        'project_whatsapp_country_code' => isset($state['project_whatsapp_country_code']) ? (string) $state['project_whatsapp_country_code'] : '',
        'project_email' => isset($state['project_email']) ? (string) $state['project_email'] : '',
        'project_office_address' => isset($state['project_office_address']) ? (string) $state['project_office_address'] : '',
        'project_work_address' => isset($state['project_work_address']) ? (string) $state['project_work_address'] : '',
    );
}

function installer_capture_project_setup_state(array $state)
{
    Registry::set('installer_project_setup', installer_extract_project_setup_state($state));
}

function installer_site_build_file_token()
{
    $token = preg_replace('/[^A-Za-z0-9._-]+/', '', installer_site_build_name());
    if ($token === '') {
        $fallbackSource = defined('root_dir') ? basename(rtrim(str_replace('\\', '/', root_dir), '/')) : basename(dirname(__DIR__));
        $token = preg_replace('/[^A-Za-z0-9._-]+/', '', (string) $fallbackSource);
    }

    return $token !== '' ? $token : md5(__DIR__);
}

function installer_default_sqlite_path()
{
    $preferred = installer_preferred_external_sqlite_path();
    if (installer_sqlite_path_is_preparable($preferred)) {
        return $preferred;
    }

    return 'data/sqlite/' . installer_site_build_file_token() . '.sqlite';
}

function installer_preferred_external_sqlite_path()
{
    return '../storage/sqlite/' . installer_site_build_file_token() . '.sqlite';
}

function installer_internal_sqlite_base_path()
{
    return root_dir . 'data/sqlite';
}

function installer_is_absolute_path($path)
{
    return is_string($path) && preg_match('#^(?:/|[A-Za-z]:[\\\\/])#', $path) === 1;
}

function installer_normalize_path_string($path)
{
    $path = str_replace('\\', '/', (string) $path);
    $isAbsolute = installer_is_absolute_path($path);
    $prefix = '';

    if ($isAbsolute) {
        if (preg_match('#^[A-Za-z]:/#', $path) === 1) {
            $prefix = substr($path, 0, 2);
            $path = substr($path, 2);
        }

        $path = '/' . ltrim($path, '/');
    }

    $segments = explode('/', $path);
    $normalized = array();

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            if (!empty($normalized) && end($normalized) !== '..') {
                array_pop($normalized);
                continue;
            }

            if (!$isAbsolute) {
                $normalized[] = '..';
            }

            continue;
        }

        $normalized[] = $segment;
    }

    $normalizedPath = implode('/', $normalized);

    if ($isAbsolute) {
        return $prefix . '/' . $normalizedPath;
    }

    return $normalizedPath;
}

function installer_resolve_sqlite_path($path)
{
    $path = installer_normalize_path_string($path);

    if ($path === '') {
        $path = installer_default_sqlite_path();
    }

    if (installer_is_absolute_path($path)) {
        return installer_normalize_path_string($path);
    }

    return installer_normalize_path_string(root_dir . $path);
}

function installer_sqlite_directory_candidates($path = null)
{
    $path = $path ?: installer_default_sqlite_path();
    $resolvedPath = installer_resolve_sqlite_path($path);
    $resolvedDirectory = installer_normalize_path_string(dirname($resolvedPath));

    return array(
        'resolved_path' => $resolvedPath,
        'resolved_directory' => $resolvedDirectory,
        'internal_directory' => installer_normalize_path_string(installer_internal_sqlite_base_path()),
    );
}

function installer_find_writable_parent_directory($path)
{
    $path = installer_normalize_path_string($path);
    while ($path !== '' && $path !== '.' && $path !== '/' && !is_dir($path)) {
        $parent = dirname($path);
        if ($parent === $path) {
            break;
        }

        $path = installer_normalize_path_string($parent);
    }

    return $path;
}

function installer_sqlite_path_is_preparable($path)
{
    $resolvedDirectory = installer_sqlite_directory_candidates($path)['resolved_directory'];
    if (is_dir($resolvedDirectory)) {
        return is_writable($resolvedDirectory);
    }

    $parent = installer_find_writable_parent_directory($resolvedDirectory);
    if ($parent === '' || $parent === '.' || !is_dir($parent)) {
        return false;
    }

    return is_writable($parent);
}

function installer_path_is_within($path, $basePath)
{
    $path = rtrim(installer_normalize_path_string($path), '/');
    $basePath = rtrim(installer_normalize_path_string($basePath), '/');

    return $path === $basePath || strpos($path . '/', $basePath . '/') === 0;
}

function installer_is_allowed_sqlite_path($path)
{
    $resolvedPath = installer_normalize_path_string($path);
    if ($resolvedPath === '') {
        return false;
    }

    if (!installer_path_is_within($resolvedPath, root_dir)) {
        return true;
    }

    return installer_path_is_within($resolvedPath, installer_internal_sqlite_base_path());
}

function installer_is_internal_sqlite_path($path)
{
    $resolvedPath = installer_normalize_path_string($path);
    if ($resolvedPath === '') {
        return false;
    }

    return installer_path_is_within($resolvedPath, installer_internal_sqlite_base_path());
}

function installer_prepare_sqlite_directory($path)
{
    $resolvedPath = installer_resolve_sqlite_path($path);
    $directory = installer_normalize_path_string(dirname($resolvedPath));
    $isInternal = installer_is_internal_sqlite_path($resolvedPath);
    $directoryMode = $isInternal ? 0777 : 0750;
    $fileMode = $isInternal ? 0666 : 0640;

    if (!is_dir($directory)) {
        if (!@mkdir($directory, $directoryMode, true) && !is_dir($directory)) {
            throw new RuntimeException("Directory could not be created: " . $directory);
        }
    }

    if (!is_writable($directory) && $isInternal) {
        @chmod($directory, $directoryMode);
        clearstatcache(true, $directory);
    }

    if (!is_writable($directory)) {
        throw new RuntimeException("Directory not writable: " . $directory);
    }

    return array(
        'path' => $resolvedPath,
        'directory' => $directory,
        'directory_mode' => $directoryMode,
        'file_mode' => $fileMode,
    );
}

function installer_replace_build_name($value)
{
    if (is_array($value)) {
        $replaced = array();
        foreach ($value as $key => $item) {
            $replaced[$key] = installer_replace_build_name($item);
        }

        return $replaced;
    }

    if (!is_string($value)) {
        return $value;
    }

    return str_replace('{{build_name}}', installer_site_build_name(), $value);
}

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
    public $roles = [];
    public $rolls;
    public $db;
    public $moduleRegistry;
    public $tables = [];
    public $uninstall_blocked = FALSE;
    protected $installer_state_session_key = 'installer_mobile_state';

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

        $adapter = (string) $this->db->getAdapterType();
        $isSqlite = strpos($adapter, 'PDO_SQLITE') !== false;

        $columns = array(
            'status' => "ALTER TABLE `mods` ADD COLUMN `status` varchar(32) NOT NULL DEFAULT 'installed'",
            'schema_ready' => "ALTER TABLE `mods` ADD COLUMN `schema_ready` char(1) NOT NULL DEFAULT 'y'",
            'installed_at' => "ALTER TABLE `mods` ADD COLUMN `installed_at` datetime DEFAULT NULL",
            'updated_at' => "ALTER TABLE `mods` ADD COLUMN `updated_at` datetime DEFAULT NULL"
        );

        foreach ($columns as $column => $sql) {
            if ($this->check_column_exists('mods', $column) === TRUE) {
                continue;
            }

            // SQLite does not support AFTER clause
            if (!$isSqlite) {
                if ($column === 'status') $sql .= " AFTER `mod_name`";
                if ($column === 'schema_ready') $sql .= " AFTER `status`";
                if ($column === 'installed_at') $sql .= " AFTER `schema_ready`";
                if ($column === 'updated_at') $sql .= " AFTER `installed_at`";
            }

            if ($this->db->query($sql) === FALSE) {
                $this->error("Module registry column '" . $column . "' could not be added");
                return FALSE;
            }

            $this->success("Module registry column '" . $column . "' added");
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

        if ($this->require_existing_tables(array('mod_user_role', 'mod_user_perm')) === FALSE) {
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
            $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name='" . $table . "'");
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
        if ($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $mods_sql_text = SqliteSqlTransformer::transform($mods_sql_text);
            
            // For SQLite, we must split and run multiple statements individually
            // because PDO::query doesn't support multiple statements at once.
            $statements = array_filter(array_map('trim', explode(';', $mods_sql_text)));
            foreach ($statements as $stmt) {
                if ($stmt === '') continue;
                if ($this->db->query($stmt) === FALSE) {
                    return FALSE;
                }
            }
            return TRUE;
        }

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


    public function success($message)
    {
        echo $this->mod . ": <font color='green'>" . $message . "</font>";
        echo "<br>";
    }


    public function error($message)
    {
        echo $this->mod . ": <font color='red'>" . $message . "</font>";
        echo "<br><font color='red'>Database Error: " . $this->db->error() . "</font>";
        echo "<br>";
    }

    public function warning($message)
    {
        echo $this->mod . ": <font color='#ffd700'>" . $message . "</font>";
        echo "<br>";
    }

    public function system_message($message)
    {
        echo "<font color='green'>" . $message . "</font>";
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
     * Inserts roles into 'mod_user_role' table
     */
    protected function insert_roles()
    {
        return $this->insert_roll();
    }

    protected function insert_roll()
    {
        if ($this->ensure_permission_infrastructure() === FALSE) {
            return FALSE;
        }

        $roles = !empty($this->roles) ? $this->roles : ($this->rolls ?? []);
        if (is_array($roles) && !empty($roles)) {
            foreach ($roles as $role) {
                $model = Load::model('mod_user.mod_user_role');
                $model->where_mod_id_and_role_name($this->mod_id, $role)->limit(1)->getResultOne(TRUE, TRUE);
                if ($model->getSize() === 0) {
                    $model->role_id = NULL;
                    $model->mod_id = $this->mod_id;
                    $model->role_name = $role;
                    if ($model->save(TRUE) === FALSE) {
                        $this->error(" ERROR OCCURRED WHILE ADDING ROLES:" . print_r($model, TRUE));
                        return FALSE;
                    }
                    $this->success("Role '" . $role . "' added");
                } else {
                    $model->sync();
                    $this->warning("Role '" . $role . "' already exists in database");
                }
                if ($this->addperm($model->role_id, $model->role_name) === FALSE) {
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
    protected function addperm($role_id, $role_name)
    {
        $pmodel = Load::model('mod_user.mod_user_perm');

        $pmodel->where_mod_id_and_role_id_and_group_id($this->mod_id, $role_id, '1')->limit(1)->getResultOne(TRUE, TRUE);
        if ($pmodel->getSize() === 0) {
            $pmodel->group_id = 1;
            $pmodel->role_id = $role_id;
            $pmodel->mod_id = $this->mod_id;
            if ($pmodel->save() === FALSE) {
                $this->error(" ERROR OCCURRED WHILE ADDING PERMISSIONS:" . print_r($pmodel, TRUE));
                return FALSE;
            }
            $this->success("Role '" . $role_name . "' added as permission for Group ID 1");
        } else {
            $this->warning("Role '" . $role_name . "' already exists as permission for Group ID 1");
        }
        return TRUE;
    }

    protected function get_installer_tables()
    {
        if (is_array($this->tables) && !empty($this->tables)) {
            return $this->tables;
        }
        if (isset($this->tables) && is_array($this->tables)) {
            return $this->tables;
        }
        return [];
    }

    protected function create_tables()
    {
        if ($this->enforce_install_dependencies() === FALSE) {
            return FALSE;
        }

        foreach ($this->get_installer_tables() as $table) {
            if ($this->check_table_exists($table) === TRUE) {
                $this->warning(" Table '" . $table . "' is already installed in database");
            } elseif ($this->install_sql_file($table . ".sql")) {
                $this->success(" File '" . $table . ".sql' added to database");
            } else {
                $this->error(" Database table '" . $table . "' could not be created");
                return FALSE;
            }
        }
    }

    // Drop table or view.
    public function drop_table($table)
    {
        $sql = "DROP TABLE " . $table;
        
        // In SQLite, we must distinguish between TABLE and VIEW for dropping.
        if ($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("SELECT type FROM sqlite_master WHERE name='" . $table . "'");
            if ($this->db->getSize() > 0) {
                $res = $this->db->toArray();
                if (isset($res[0]['type']) && $res[0]['type'] === 'view') {
                    $sql = "DROP VIEW " . $table;
                }
            }
        }

        if ($this->db->query($sql)) {
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

        foreach ($this->get_installer_tables() as $table) {
            if ($this->check_table_exists($table) === FALSE) {
                $this->warning(" Table '" . $table . "' is not installed in database");
            } elseif ($this->drop_table($table)) {
                $this->success(" Table '" . $table . "' dropped");
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
                $this->success(" Module removed from 'mods' table");
            }
            $this->mod_id = $model->mod_id;
        } else {
            $this->warning(" Module not found in 'mods' table");
        }
        return TRUE;
    }

    protected function delete_roles()
    {
        return $this->remove_rool();
    }

    protected function remove_rool()
    {
        if ($this->uninstall_blocked === TRUE) {
            return FALSE;
        }

        $model = Load::model('mod_user.mod_user_role');
        if ($this->mod_id == '') {
            return TRUE;
        }
        $model->where_mod_id($this->mod_id);
        if ($model->delete()) {
            $this->success(" Roles belonging to the module deleted from 'mod_user_role' table");
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
            $this->success(" Permissions belonging to the module deleted from 'mod_user_perm' table");
        }
        return TRUE;
    }

    protected function installer_sample_content_enabled()
    {
        if (Registry::has($this->installer_state_session_key) === TRUE) {
            $state = Registry::get($this->installer_state_session_key);
            if (is_array($state) && isset($state['sample_content']) && $state['sample_content'] === 'yes') {
                return TRUE;
            }
        }

        $session = Load::lib('Session');
        $state = $session->get($this->installer_state_session_key);

        return is_array($state) && isset($state['sample_content']) && $state['sample_content'] === 'yes';
    }

    protected function installer_category_root_names()
    {
        return array(
            'ar' => '##العربية_التصنيفات',
            'de' => '##Deutsch_Kategorien',
            'en' => '##English_Categories',
            'fa' => '##فارسی_دسته‌بندی‌ها',
            'fr' => '##Français_Catégories',
            'ku' => '##Kurmancî_Kategorî',
            'ckb' => '##کوردی_هاوپۆلەکان',
            'sv' => '##Svenska_Kategorier',
            'tr' => '##Türkçe_Kategoriler',
            'zza' => '##Zazakî_Kategori',
            'ru' => '##Русский_Категории',
        );
    }

    protected function installer_category_starter_labels()
    {
        return array(
            'news' => array(
                'ar' => 'الأخبار',
                'de' => 'Nachrichten',
                'en' => 'News',
                'fa' => 'اخبار',
                'fr' => 'Actualités',
                'ku' => 'Nûçe',
                'ckb' => 'هەواڵەکان',
                'sv' => 'Nyheter',
                'tr' => 'Haberler',
                'zza' => 'Xeber',
                'ru' => 'Новости',
            ),
            'videos' => array(
                'ar' => 'الفيديوهات',
                'de' => 'Videos',
                'en' => 'Videos',
                'fa' => 'ویدیوها',
                'fr' => 'Vidéos',
                'ku' => 'Vîdyo',
                'ckb' => 'ڤیدیۆکان',
                'sv' => 'Videor',
                'tr' => 'Videolar',
                'zza' => 'Vîdyo',
                'ru' => 'Видео',
            ),
            'publications' => array(
                'ar' => 'المنشورات',
                'de' => 'Publikationen',
                'en' => 'Publications',
                'fa' => 'انتشارات',
                'fr' => 'Publications',
                'ku' => 'Weşan',
                'ckb' => 'بڵاوکراوەکان',
                'sv' => 'Publikationer',
                'tr' => 'Yayınlar',
                'zza' => 'Weşan',
                'ru' => 'Публикации',
            ),
            'bulletin' => array(
                'ar' => 'النشرة',
                'de' => 'Bulletin',
                'en' => 'Bulletin',
                'fa' => 'بولتن',
                'fr' => 'Bulletin',
                'ku' => 'Bulten',
                'ckb' => 'بولتین',
                'sv' => 'Bulletin',
                'tr' => 'Bülten',
                'zza' => 'Bulten',
                'ru' => 'Бюллетень',
            ),
        );
    }

    protected function installer_category_default_record($lang, $name, $parentId, $isRoot)
    {
        return array(
            'lang' => $lang,
            'name' => $name,
            'parent_id' => (int) $parentId,
            'order' => 1,
            'root' => $isRoot ? 'y' : 'n',
            'seo' => '#',
            'title' => '#',
            'keywords' => '#',
            'description' => '#',
            'image' => null,
            'menu' => 0,
            'show_on_homepage' => 'n',
            'acl_id' => 0,
            'user_id' => 1,
        );
    }

    protected function installer_seed_category_bootstrap($table, $starterKey = null)
    {
        $rootNames = $this->installer_category_root_names();

        foreach (InstallerLang::availableLanguages() as $code => $language) {
            if (!isset($rootNames[$code])) {
                $this->error("Root category label is missing for language '" . $code . "'");
                return FALSE;
            }

            $rootName = $rootNames[$code];
            $rootRecord = $this->installer_category_default_record($code, $rootName, 0, true);
            $rootId = $this->installer_ensure_category_record($table, $rootRecord, true);

            if ($rootId === FALSE) {
                return FALSE;
            }

            if ($starterKey === null || !$this->installer_sample_content_enabled()) {
                continue;
            }

            $starterName = $this->installer_resolve_category_starter_label($starterKey, $code);
            if ($starterName === FALSE) {
                $this->error("Starter category label '" . $starterKey . "' is missing for language '" . $code . "'");
                return FALSE;
            }

            $starterRecord = $this->installer_category_default_record($code, $starterName, $rootId, false);
            if ($this->installer_ensure_category_record($table, $starterRecord, false) === FALSE) {
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function installer_resolve_category_starter_label($starterKey, $lang)
    {
        $labels = $this->installer_category_starter_labels();
        if (!isset($labels[$starterKey]) || !isset($labels[$starterKey][$lang])) {
            return FALSE;
        }

        return $labels[$starterKey][$lang];
    }

    protected function installer_ensure_category_record($table, array $record, $isRoot)
    {
        $lang = addslashes($record['lang']);
        $parentId = (int) $record['parent_id'];

        if ($isRoot) {
            $query = "SELECT id FROM `" . addslashes($table) . "` WHERE lang='" . $lang . "' AND root='y' AND parent_id=0 ORDER BY id ASC LIMIT 1";
        } else {
            $name = addslashes($record['name']);
            $query = "SELECT id FROM `" . addslashes($table) . "` WHERE lang='" . $lang . "' AND parent_id=" . $parentId . " AND name='" . $name . "' ORDER BY id ASC LIMIT 1";
        }

        $this->db->fetch_array($query);
        if ($this->db->getSize() !== 0) {
            $rows = $this->db->getResult()->toArray();
            $existingId = isset($rows[0]['id']) ? (int) $rows[0]['id'] : 0;
            $this->warning($record['name'] . ' already exists');
            return $existingId;
        }

        $insert = $this->installer_filter_existing_columns($table, $record);
        $columns = array();
        $values = array();

        foreach ($insert as $column => $value) {
            $columns[] = '`' . $column . '`';
            $values[] = $this->installer_sql_value($value);
        }

        $sql = "INSERT INTO `" . addslashes($table) . "` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        if ($this->db->query($sql) === FALSE) {
            $this->error($record['name'] . ' could not be added');
            return FALSE;
        }

        $this->success($record['name'] . ' added');
        $this->db->fetch_array("SELECT id FROM `" . addslashes($table) . "` ORDER BY id DESC LIMIT 1");
        $rows = $this->db->getResult()->toArray();

        return isset($rows[0]['id']) ? (int) $rows[0]['id'] : TRUE;
    }

    protected function installer_filter_existing_columns($table, array $record)
    {
        $filtered = array();

        foreach ($record as $column => $value) {
            if ($this->check_column_exists($table, $column) === TRUE) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    protected function installer_sql_value($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }
}
