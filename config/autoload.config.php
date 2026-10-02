<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: autoload.config.php
 * Description: Autoload Libraries and Files
 */
if (defined('root_dir') === False) exit(':)');

// We can load Static Classes
$autoload['libfile'] = array(
    // 'Error',
    'Req',
    'Helper',
    'Text',
    'Run',
    'Role',
    'RedisCache',
    'Cache',
    'Database',
    'Orm',
    'BaseModuleController',
    'BaseModuleValidator',
    'DataTableService',
    'ImageService',
    'GoogleIndexingService',
    'AIService',
    'LlmsService'
);
// Libraries to run automatically
$autoload['lib'] = array();

// Config files to be loaded automatically
$autoload['config'] = array(
    'db',
    'site',
    'i18n',
    'url',
    'session',
    'google'
);
Registry::add('autoload_config', $autoload);
unset($autoload);
