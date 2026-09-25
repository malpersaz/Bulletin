<?php

class ModuleRuntime
{
    private $db;
    private $registry;
    private $installedModules = null;
    private $moduleStateCache = [];
    private $tableExistsCache = [];
    private $columnExistsCache = [];
    private $featureFailureCache = [];

    public function __construct()
    {
        $this->db = Load::lib('Database');
        $this->registry = Load::lib('ModuleRegistry');
    }

    public function isReady($feature)
    {
        if (array_key_exists($feature, $this->featureFailureCache) && $this->featureFailureCache[$feature] === null) {
            return true;
        }

        $config = $this->getFeatureConfig($feature);

        if ($config === null) {
            $this->featureFailureCache[$feature] = [
                'feature' => $feature,
                'reason' => 'unknown_feature',
                'missing_modules' => [],
                'missing_tables' => [],
            ];
            return false;
        }

        $missingModules = [];
        foreach ($config['modules'] as $module) {
            if (!$this->isModuleReady($module)) {
                $missingModules[] = $module;
            }
        }

        $missingTables = [];
        foreach ($config['tables'] as $table) {
            if (!$this->tableExists($table)) {
                $missingTables[] = $table;
            }
        }

        if ($missingModules !== [] || $missingTables !== []) {
            $reason = $missingTables !== [] ? 'missing_table' : 'missing_module';
            if ($missingModules !== [] && $missingTables !== []) {
                $reason = 'missing_dependency';
            }

            $this->featureFailureCache[$feature] = [
                'feature' => $feature,
                'reason' => $reason,
                'missing_modules' => $missingModules,
                'missing_tables' => $missingTables,
            ];
            return false;
        }

        $this->featureFailureCache[$feature] = null;
        return true;
    }

    public function requireReady($feature, array $context = [])
    {
        $ready = $this->isReady($feature);
        if ($ready) {
            return true;
        }

        $this->logFeatureBlock($feature, $context);
        return false;
    }

    public function isModuleInstalled($module)
    {
        return in_array($module, $this->getInstalledModules(), true);
    }

    public function isModuleReady($module)
    {
        if (!$this->isModuleInstalled($module)) {
            return false;
        }

        $state = $this->getModuleState($module);
        if ($state === null) {
            return true;
        }

        if (isset($state['status']) && $state['status'] !== '' && !in_array($state['status'], ['installed', 'ready'], true)) {
            return false;
        }

        if (isset($state['schema_ready']) && $state['schema_ready'] !== '' && $state['schema_ready'] !== 'y') {
            return false;
        }

        return true;
    }

    public function tableExists($table)
    {
        if (isset($this->tableExistsCache[$table])) {
            return $this->tableExistsCache[$table];
        }

        $safeTable = addslashes($table);
        $adapter = (string) $this->db->getAdapterType();

        if (strpos($adapter, 'PDO_SQLITE') !== false) {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $safeTable . "'";
        } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
            $sql = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema') AND tablename = '" . $safeTable . "'";
        } else {
            $sql = "SHOW TABLES LIKE '" . $safeTable . "'";
        }

        $this->db->fetch_array($sql);
        $this->tableExistsCache[$table] = ($this->db->getSize() !== 0);

        return $this->tableExistsCache[$table];
    }

    public function getFeatureConfig($feature)
    {
        return $this->registry->getFeatureConfig($feature);
    }

    public function getFailureReason($feature)
    {
        if (!array_key_exists($feature, $this->featureFailureCache)) {
            $this->isReady($feature);
        }

        return isset($this->featureFailureCache[$feature]) ? $this->featureFailureCache[$feature] : null;
    }

    public function getModuleState($module)
    {
        if (array_key_exists($module, $this->moduleStateCache)) {
            return $this->moduleStateCache[$module];
        }

        if (!$this->tableExists('mods')) {
            $this->moduleStateCache[$module] = null;
            return null;
        }

        $columns = ['mod_name'];
        if ($this->columnExists('mods', 'status')) {
            $columns[] = 'status';
        }
        if ($this->columnExists('mods', 'schema_ready')) {
            $columns[] = 'schema_ready';
        }
        if ($this->columnExists('mods', 'installed_at')) {
            $columns[] = 'installed_at';
        }
        if ($this->columnExists('mods', 'updated_at')) {
            $columns[] = 'updated_at';
        }

        $sql = "SELECT " . implode(', ', $columns) . " FROM `mods` WHERE mod_name='" . addslashes($module) . "' LIMIT 1";
        $this->db->fetch_array($sql);
        if ($this->db->getSize() === 0) {
            $this->moduleStateCache[$module] = null;
            return null;
        }

        $rows = $this->db->toArray();
        $this->moduleStateCache[$module] = isset($rows[0]) ? $rows[0] : null;
        return $this->moduleStateCache[$module];
    }

    public function getModuleHealth($module)
    {
        $state = $this->getModuleState($module);
        $dependency = $this->registry->getInstallDependencyConfig($module);
        $installed = $this->isModuleInstalled($module);
        $ready = $this->isModuleReady($module);
        $missingModules = [];
        $missingTables = [];

        if ($dependency !== null) {
            foreach ($dependency['modules'] as $dependencyModule) {
                if (!$this->isModuleInstalled($dependencyModule)) {
                    $missingModules[] = $dependencyModule;
                }
            }

            foreach ($dependency['tables'] as $dependencyTable) {
                if (!$this->tableExists($dependencyTable)) {
                    $missingTables[] = $dependencyTable;
                }
            }
        }

        return [
            'module' => $module,
            'installed' => $installed,
            'ready' => $ready,
            'status' => ($state !== null && isset($state['status'])) ? $state['status'] : ($ready ? 'ready' : ($installed ? 'installed' : 'missing')),
            'schema_ready' => ($state !== null && isset($state['schema_ready'])) ? $state['schema_ready'] : ($ready ? 'y' : 'n'),
            'missing_modules' => $missingModules,
            'missing_tables' => $missingTables,
            'installed_at' => ($state !== null && isset($state['installed_at'])) ? $state['installed_at'] : null,
            'updated_at' => ($state !== null && isset($state['updated_at'])) ? $state['updated_at'] : null,
        ];
    }

    public function logFeatureBlock($feature, array $context = [])
    {
        $failure = $this->getFailureReason($feature);
        if ($failure === null) {
            return;
        }

        $payload = array_merge($failure, [
            'context' => $context,
        ]);

        error_log('module_runtime_blocked: ' . json_encode($payload));
    }

    private function columnExists($table, $column)
    {
        $cacheKey = $table . ':' . $column;
        if (isset($this->columnExistsCache[$cacheKey])) {
            return $this->columnExistsCache[$cacheKey];
        }

        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();

        if (strpos($adapter, 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
            $rows = $this->db->toArray();
            $this->columnExistsCache[$cacheKey] = false;
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row['name']) && $row['name'] === $column) {
                        $this->columnExistsCache[$cacheKey] = true;
                        break;
                    }
                }
            }
            return $this->columnExistsCache[$cacheKey];
        }

        if (strpos($adapter, 'POSTGRESQL') !== false) {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
            $this->db->fetch_array($sql);
            $this->columnExistsCache[$cacheKey] = ($this->db->getSize() !== 0);
            return $this->columnExistsCache[$cacheKey];
        }

        $this->db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
        $this->columnExistsCache[$cacheKey] = ($this->db->getSize() !== 0);

        return $this->columnExistsCache[$cacheKey];
    }

    private function getInstalledModules()
    {
        if (is_array($this->installedModules)) {
            return $this->installedModules;
        }

        $this->installedModules = [];

        if (!$this->tableExists('mods')) {
            return $this->installedModules;
        }

        $sql = "SELECT mod_name";
        if ($this->columnExists('mods', 'status')) {
            $sql .= ", status";
        }
        if ($this->columnExists('mods', 'schema_ready')) {
            $sql .= ", schema_ready";
        }
        $sql .= " FROM `mods`";
        $this->db->fetch_array($sql);
        $rows = $this->db->toArray();

        if (!is_array($rows)) {
            return $this->installedModules;
        }

        foreach ($rows as $row) {
            if (isset($row['mod_name']) && $row['mod_name'] !== '') {
                if (isset($row['status']) && $row['status'] !== '' && !in_array($row['status'], ['installed', 'ready'], true)) {
                    continue;
                }
                $this->installedModules[] = $row['mod_name'];
                $this->moduleStateCache[$row['mod_name']] = $row;
            }
        }

        return $this->installedModules;
    }
}
