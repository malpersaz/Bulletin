<?php

class InstallerEnvironmentCheck
{
    public function run()
    {
        $checks = array();
        $checks[] = $this->buildStatus(
            version_compare(PHP_VERSION, '8.0.0', '>='),
            'php_version',
            array('version' => PHP_VERSION)
        );
        $checks[] = $this->checkExtensions();
        $checks[] = $this->checkWritableDirectories();
        $checks[] = $this->checkDatabaseConfiguration();

        return $checks;
    }

    public function hasBlockingFailures(array $checks)
    {
        foreach ($checks as $check) {
            if (!empty($check['blocking']) && $check['ok'] === false) {
                return true;
            }
        }

        return false;
    }

    private function checkExtensions()
    {
        $required = array('pdo', 'mbstring', 'json');
        $missing = array();

        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return $this->buildStatus(
            empty($missing),
            'extensions',
            array('missing' => implode(', ', $missing))
        );
    }

    private function checkWritableDirectories()
    {
        $directories = array(
            'cache' => root_dir . 'cache',
            'config' => root_dir . 'config',
            'data' => root_dir . 'data',
            'templates_c' => root_dir . 'templates_c',
        );
        $blocked = array();

        foreach ($directories as $name => $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $blocked[] = $name;
            }
        }

        return $this->buildStatus(
            empty($blocked),
            'directories',
            array('blocked' => implode(', ', $blocked))
        );
    }

    private function checkDatabaseConfiguration()
    {
        $dbConfig = Kayit::getir('db_config');
        $databaseKey = Kayit::getir('database');
        $configured = is_array($dbConfig) && isset($dbConfig[$databaseKey]['adapter']);
        $detail = 'No active database configuration found.';

        if ($configured) {
            $detail = $dbConfig[$databaseKey]['adapter'] . '|' . $databaseKey;
        }

        return $this->buildStatus($configured, 'database', array('detail' => $detail));
    }

    private function buildStatus($ok, $key, array $meta)
    {
        return array(
            'ok' => $ok === true,
            'blocking' => true,
            'key' => $key,
            'meta' => $meta,
        );
    }
}
