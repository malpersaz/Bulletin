<?php

if (defined('root_dir') === False) exit(':)');

$localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'db.config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
    return;
}

$db = array();
Registry::add('database', 'installed');
Registry::add('db_config', $db);
unset($db);
