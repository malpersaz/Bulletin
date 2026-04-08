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
        $availableDrivers = array();

        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        foreach (array('pdo_mysql', 'pdo_sqlite') as $driver) {
            if (extension_loaded($driver)) {
                $availableDrivers[] = $driver;
            }
        }

        return $this->buildStatus(
            empty($missing),
            'extensions',
            array(
                'missing' => $missing,
                'available_drivers' => $availableDrivers,
            )
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
            array('blocked' => $blocked)
        );
    }
    private function buildStatus($ok, $key, array $meta, $blocking = true)
    {
        return array(
            'ok' => $ok === true,
            'blocking' => $blocking,
            'key' => $key,
            'meta' => $meta,
        );
    }
}
