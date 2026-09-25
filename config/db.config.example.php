<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: db.config.example.php
 * Description: Database Connection Information
 */
if (defined('root_dir') === False) exit(':)');

$db = array(
    // Local Host - MySQL via PDO
    'localhost' => array(
        'adapter' => 'PDO_MYSQL',
        'conf' => array(
            'host' => '**',
            'user' => '********',
            'pass' => '**************',
            'database' => '********',
            'encoding' => 'utf8',
            'collate' => 'utf8_unicode_ci'
        )
    ),

    // Remote - MySQL via PDO
    'remote' => array(
        'adapter' => 'PDO_MYSQL',
        'conf' => array(
            'host' => '******.kasserver.com',
            'user' => '********',
            'pass' => "******************",
            'database' => '********',
            'encoding' => 'utf8',
            'collate' => 'utf8_unicode_ci'
        )
    ),

    /*    
    // SQLite Example - File-based database for development
    'sqlite_local' => array(
        'adapter' => 'PDO_SQLITE',
        'conf' => array(
            'database_path' => root_dir . 'data/sqlite/NetcomBi.sqlite',
            // SQLite does not require host, user, password
        )
    ),
    
    // PostgreSQL Example - Enterprise database
    'postgresql_local' => array(
        'adapter' => 'PDO_POSTGRESQL',
        'conf' => array(
            'host' => 'localhost',
            'user' => 'rojname_user',
            'pass' => 'rojname_password',
            'database' => 'rojname_db',
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

Registry::add('db_config', $db);
unset($db);
