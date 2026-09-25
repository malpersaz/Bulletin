<?php

/**
 * Amed Framework
 * User: a.k.mutlu
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: db.config.php
 * Description: Database Connection Information
 */
if (defined('root_dir') === False) exit(':)');

$db = array(
    // Local Host - MySQL via PDO


      
    // SQLite Example - File-based database for development
    'localhost' => array(
        'adapter' => 'PDO_SQLITE',
        'conf' => array(
            'database_path' => root_dir . 'data/sqlite/NetcomBi.sqlite',
            // SQLite doesn't need host, user, pass
        )
    ),
    /*
    // PostgreSQL Example - Enterprise database
    'postgresql_local' => array(
        'adapter' => 'PDO_POSTGRESQL',
        'conf' => array(
            'host' => 'localhost',
            'user' => 'AKMlearnly_user',
            'pass' => 'AKMlearnly_password',
            'database' => 'AKMlearnly_db',
            'port' => 5432,
            'encoding' => 'utf8',
            'sslmode' => 'require' // optional
        )
    ),
    
    // PostgreSQL Cloud Example
    'postgresql_cloud' => array(
        'adapter' => 'PDO_POSTGRESQL',
        'conf' => array(
            'host' => 'your-postgres-host.com',
            'user' => 'username',
            'pass' => 'password',
            'database' => 'dbname',
            'port' => 5432,
            'sslmode' => 'require'
        )
    ),
    
    // MySQL with SSL Example
    'mysql_ssl' => array(
        'adapter' => 'PDO_MYSQL',
        'conf' => array(
            'host' => 'secure-mysql-host.com',
            'user' => 'username',
            'pass' => 'password',
            'database' => 'dbname',
            'port' => 3306,
            'encoding' => 'utf8mb4'
        )
    ),
    
    // Legacy MySQLi Example (for rollback if needed)
    'mysql_legacy' => array(
        'adapter' => 'Mysqli',
        'conf' => array(
            'host' => 'localhost',
            'user' => 'username',
            'pass' => 'password',
            'database' => 'dbname',
            'encoding' => 'utf8'
        )
    )
    */
);
if (
    $_SERVER['HTTP_HOST'] == 'localhost'
    || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false

    || strpos($_SERVER['HTTP_HOST'], 'pinggy.link') !== false
) {
    Registry::add('database', 'localhost');
} else {
    Registry::add('database', 'remote');
}
//Registry::add('database', 'remote');

Registry::add('db_config', $db);
unset($db);
