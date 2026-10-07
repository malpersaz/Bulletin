<?php

require_once __DIR__ . '/../lib/ModuleRegistry.class.php';
require_once __DIR__ . '/../lib/ModuleRuntime.class.php';

final class FakeModuleRuntimeDb
{
    private $tables;
    private $columns;
    private $modsRows;
    private $lastRows = [];

    public function __construct(array $tables, array $columns, array $modsRows)
    {
        $this->tables = $tables;
        $this->columns = $columns;
        $this->modsRows = $modsRows;
    }

    public function getAdapterType()
    {
        return 'PDO_MYSQL';
    }

    public function fetch_array($sql)
    {
        $sql = trim($sql);
        $this->lastRows = [];

        if (preg_match("/^SHOW TABLES LIKE '([^']+)'$/", $sql, $matches)) {
            $table = stripslashes($matches[1]);
            if (in_array($table, $this->tables, true)) {
                $this->lastRows = [['table' => $table]];
            }
            return $this;
        }

        if (preg_match("/^SHOW COLUMNS FROM `([^`]+)` LIKE '([^']+)'$/", $sql, $matches)) {
            $table = $matches[1];
            $column = stripslashes($matches[2]);
            if (isset($this->columns[$table]) && in_array($column, $this->columns[$table], true)) {
                $this->lastRows = [['Field' => $column]];
            }
            return $this;
        }

        if (preg_match("/^SELECT (.+) FROM `mods`$/", $sql, $matches)) {
            $columns = array_map('trim', explode(',', $matches[1]));
            foreach ($this->modsRows as $row) {
                $selected = [];
                foreach ($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $selected[$column] = $row[$column];
                    }
                }
                $this->lastRows[] = $selected;
            }
            return $this;
        }

        if (preg_match("/^SELECT (.+) FROM `mods` WHERE mod_name='([^']+)' LIMIT 1$/", $sql, $matches)) {
            $columns = array_map('trim', explode(',', $matches[1]));
            $module = stripslashes($matches[2]);
            foreach ($this->modsRows as $row) {
                if (($row['mod_name'] ?? null) !== $module) {
                    continue;
                }
                $selected = [];
                foreach ($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $selected[$column] = $row[$column];
                    }
                }
                $this->lastRows[] = $selected;
                break;
            }
            return $this;
        }

        throw new RuntimeException('Unhandled SQL in fake DB: ' . $sql);
    }

    public function getSize()
    {
        return count($this->lastRows);
    }

    public function toArray()
    {
        return $this->lastRows;
    }
}

function assert_true($condition, $message)
{
    if ($condition !== true) {
        throw new RuntimeException($message);
    }
}

function assert_false($condition, $message)
{
    if ($condition !== false) {
        throw new RuntimeException($message);
    }
}

function assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function build_runtime(array $tables, array $modsRows)
{
    $runtimeReflection = new ReflectionClass('ModuleRuntime');
    $runtime = $runtimeReflection->newInstanceWithoutConstructor();

    $registry = new ModuleRegistry();
    $columns = [
        'mods' => ['mod_name', 'status', 'schema_ready', 'installed_at', 'updated_at'],
    ];
    $fakeDb = new FakeModuleRuntimeDb($tables, $columns, $modsRows);

    foreach (
        [
            'db' => $fakeDb,
            'registry' => $registry,
            'installedModules' => null,
            'moduleStateCache' => [],
            'tableExistsCache' => [],
            'columnExistsCache' => [],
            'featureFailureCache' => [],
        ] as $property => $value
    ) {
        $reflectionProperty = $runtimeReflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($runtime, $value);
    }

    return $runtime;
}

function test_registry_maps()
{
    $registry = new ModuleRegistry();
    $news = $registry->getFeatureConfig('news.front');

    assert_same(['mod_news', 'mod_news_category'], $news['modules'], 'news.front module map changed unexpectedly');
    assert_same(['mod_news', 'mod_news_category'], $news['tables'], 'news.front table map changed unexpectedly');
    assert_same(['mod_news'], $registry->getUninstallDependents('mod_news_category'), 'mod_news_category uninstall dependents mismatch');
}

function test_runtime_ready_feature()
{
    $runtime = build_runtime(
        ['mods', 'mod_news', 'mod_news_category'],
        [
            ['mod_name' => 'mod_news', 'status' => 'ready', 'schema_ready' => 'y'],
            ['mod_name' => 'mod_news_category', 'status' => 'ready', 'schema_ready' => 'y'],
        ]
    );

    assert_true($runtime->isReady('news.front'), 'news.front should be ready');
    assert_same(null, $runtime->getFailureReason('news.front'), 'ready feature should not expose a failure reason');
}

function test_runtime_missing_table_reason()
{
    $runtime = build_runtime(
        ['mods', 'mod_news'],
        [
            ['mod_name' => 'mod_news', 'status' => 'ready', 'schema_ready' => 'y'],
            ['mod_name' => 'mod_news_category', 'status' => 'ready', 'schema_ready' => 'y'],
        ]
    );

    assert_false($runtime->isReady('news.front'), 'news.front should fail when mod_news_category table is missing');
    $failure = $runtime->getFailureReason('news.front');
    assert_same('missing_table', $failure['reason'], 'missing table reason mismatch');
    assert_same(['mod_news_category'], $failure['missing_tables'], 'missing table payload mismatch');
}

function test_runtime_disabled_module_reason()
{
    $runtime = build_runtime(
        ['mods', 'mod_news', 'mod_news_category'],
        [
            ['mod_name' => 'mod_news', 'status' => 'disabled', 'schema_ready' => 'n'],
            ['mod_name' => 'mod_news_category', 'status' => 'ready', 'schema_ready' => 'y'],
        ]
    );

    assert_false($runtime->isReady('news.front'), 'disabled module should not be ready');
    $failure = $runtime->getFailureReason('news.front');
    assert_same('missing_module', $failure['reason'], 'disabled module should surface as missing module for feature readiness');
    assert_same(['mod_news'], $failure['missing_modules'], 'disabled module payload mismatch');
}

function test_module_health_detects_dependency_gaps()
{
    $runtime = build_runtime(
        ['mods', 'mod_news', 'mod_news_category'],
        [
            ['mod_name' => 'mod_news', 'status' => 'degraded', 'schema_ready' => 'n'],
            ['mod_name' => 'mod_news_category', 'status' => 'ready', 'schema_ready' => 'y'],
        ]
    );

    $health = $runtime->getModuleHealth('mod_news');
    assert_same('degraded', $health['status'], 'module health should preserve degraded status');
    assert_same(['mod_gallery', 'mod_user'], $health['missing_modules'], 'module health should report missing dependency modules');
    assert_same(['mod_gallery', 'mod_user'], $health['missing_tables'], 'module health should report missing dependency tables');
}

function test_require_ready_logs_block()
{
    $runtime = build_runtime(
        ['mods', 'mod_news'],
        [
            ['mod_name' => 'mod_news', 'status' => 'ready', 'schema_ready' => 'y'],
            ['mod_name' => 'mod_news_category', 'status' => 'ready', 'schema_ready' => 'y'],
        ]
    );

    $logFile = tempnam(sys_get_temp_dir(), 'module-runtime-test-');
    ini_set('error_log', $logFile);

    assert_false(
        $runtime->requireReady('news.front', ['url' => '/news']),
        'requireReady should fail for missing dependency table'
    );

    $logContent = file_get_contents($logFile);
    assert_true(strpos($logContent, 'module_runtime_blocked:') !== false, 'requireReady should emit a blocked-feature log entry');
    assert_true(strpos($logContent, '"feature":"news.front"') !== false, 'blocked-feature log should include feature name');

    @unlink($logFile);
}

$tests = [
    'registry maps' => 'test_registry_maps',
    'runtime ready feature' => 'test_runtime_ready_feature',
    'runtime missing table reason' => 'test_runtime_missing_table_reason',
    'runtime disabled module reason' => 'test_runtime_disabled_module_reason',
    'module health detects dependency gaps' => 'test_module_health_detects_dependency_gaps',
    'requireReady logs block' => 'test_require_ready_logs_block',
];

$failures = [];

foreach ($tests as $label => $test) {
    try {
        $test();
        echo "[PASS] " . $label . PHP_EOL;
    } catch (Throwable $exception) {
        $failures[] = [
            'label' => $label,
            'message' => $exception->getMessage(),
        ];
        echo "[FAIL] " . $label . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;
    }
}

if (!empty($failures)) {
    exit(1);
}

echo "All module management runtime tests passed." . PHP_EOL;
