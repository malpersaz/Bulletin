<?php

if (defined('root_dir') === false) {
    exit(':)');
}

class InstallationState
{
    private $requiredTables = [
        'lang',
        'mods',
        'mod_settings',
        'mod_user',
        'mod_user_group',
        'mod_menu',
        'mod_page',
    ];

    public function inspect()
    {
        $summary = [
            'status' => 'needs_installation',
            'is_installed' => false,
            'database' => null,
            'adapter' => null,
            'issues' => [],
        ];

        $database = Registry::get('database');
        $dbConfig = Registry::get('db_config');

        if (!is_string($database) || $database === '') {
            $summary['issues'][] = $this->buildIssue('database_key_missing', 'No active database key is registered.');
            return $summary;
        }

        $summary['database'] = $database;

        if (!is_array($dbConfig) || !isset($dbConfig[$database]) || !is_array($dbConfig[$database])) {
            $summary['issues'][] = $this->buildIssue('database_config_missing', 'The active database configuration is missing.');
            return $summary;
        }

        $config = $dbConfig[$database];
        $adapter = isset($config['adapter']) ? strtoupper((string) $config['adapter']) : '';
        $conf = isset($config['conf']) && is_array($config['conf']) ? $config['conf'] : [];
        $summary['adapter'] = $adapter;

        if ($adapter !== 'PDO_MYSQL' && $adapter !== 'PDO_SQLITE') {
            $summary['issues'][] = $this->buildIssue('database_adapter_unsupported', 'Only PDO MySQL and PDO SQLite install-state checks are supported.');
            return $summary;
        }

        if ($adapter === 'PDO_MYSQL' && !extension_loaded('pdo_mysql')) {
            $summary['issues'][] = $this->buildIssue('pdo_mysql_missing', 'The PDO MySQL extension is not loaded.');
            return $summary;
        }

        if ($adapter === 'PDO_SQLITE' && !extension_loaded('pdo_sqlite')) {
            $summary['issues'][] = $this->buildIssue('pdo_sqlite_missing', 'The PDO SQLite extension is not loaded.');
            return $summary;
        }

        try {
            $pdo = $this->connect($adapter, $conf);
        } catch (Exception $exception) {
            $summary['issues'][] = $this->buildIssue('database_connection_failed', $exception->getMessage());
            return $summary;
        }

        $missingTables = $this->findMissingTables($pdo, $adapter, $conf);
        if (!empty($missingTables)) {
            $summary['issues'][] = $this->buildIssue(
                'database_schema_incomplete',
                'Core installation tables are missing.',
                ['tables' => $missingTables]
            );
            return $summary;
        }

        $summary['status'] = 'installed';
        $summary['is_installed'] = true;

        return $summary;
    }

    private function connect($adapter, array $conf)
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ];

        if ($adapter === 'PDO_SQLITE') {
            $path = $this->normalizeSqlitePath(isset($conf['database_path']) ? $conf['database_path'] : '');
            if ($path === '') {
                throw new RuntimeException('The SQLite database path is missing.');
            }

            if (!file_exists($path)) {
                throw new RuntimeException('The SQLite database file does not exist yet.');
            }

            if (is_file($path) && filesize($path) === 0) {
                throw new RuntimeException('The SQLite database file exists but is empty.');
            }

            return new PDO('sqlite:' . $path, null, null, $options);
        }

        $host = trim((string) (isset($conf['host']) ? $conf['host'] : ''));
        $database = trim((string) (isset($conf['database']) ? $conf['database'] : ''));
        $port = isset($conf['port']) && $conf['port'] !== '' ? ';port=' . (int) $conf['port'] : '';

        if ($host === '' || $database === '') {
            throw new RuntimeException('The MySQL host or database name is missing.');
        }

        $dsn = 'mysql:host=' . $host . ';dbname=' . $database . $port . ';charset=utf8';

        return new PDO(
            $dsn,
            isset($conf['user']) ? (string) $conf['user'] : '',
            isset($conf['pass']) ? (string) $conf['pass'] : '',
            $options
        );
    }

    private function normalizeSqlitePath($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:/|[A-Za-z]:[\\\\/])#', $path) === 1) {
            return $path;
        }

        return root_dir . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function findMissingTables(PDO $pdo, $adapter, array $conf)
    {
        $missing = [];

        foreach ($this->requiredTables as $table) {
            if (!$this->tableExists($pdo, $adapter, $conf, $table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    private function tableExists(PDO $pdo, $adapter, array $conf, $table)
    {
        if ($adapter === 'PDO_SQLITE') {
            $statement = $pdo->prepare("SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name = :table LIMIT 1");
            $statement->execute([':table' => $table]);
            return (bool) $statement->fetchColumn();
        }

        $database = (string) $conf['database'];
        $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table');
        $statement->execute([':schema' => $database, ':table' => $table]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function getTableColumns(PDO $pdo, $adapter, array $conf, $table)
    {
        if ($adapter === 'PDO_SQLITE') {
            $statement = $pdo->query('PRAGMA table_info(' . $this->quoteIdentifier($table, $adapter) . ')');
            $rows = $statement->fetchAll();
            $columns = [];

            foreach ($rows as $row) {
                if (isset($row['name'])) {
                    $columns[] = $row['name'];
                }
            }

            return $columns;
        }

        $database = (string) $conf['database'];
        $statement = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table');
        $statement->execute([':schema' => $database, ':table' => $table]);

        return array_map(function ($column) {
            return is_array($column) && isset($column['COLUMN_NAME']) ? $column['COLUMN_NAME'] : null;
        }, $statement->fetchAll());
    }

    private function quoteIdentifier($identifier, $adapter)
    {
        if ($adapter === 'PDO_SQLITE') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function buildIssue($key, $message, array $meta = [])
    {
        return [
            'key' => $key,
            'message' => $message,
            'meta' => $meta,
        ];
    }
}
