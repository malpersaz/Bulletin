<?php

/**
 * Amed Framework
 * Description: Bootstrap Initialization File
 */

/*
 * Defined Variables
 */
// Debug State
define('DEBUG', TRUE);
// Framework Loaded Flag
define('AKM_LOADED', TRUE);
// Current Time
define('NOW', date("Y-m-d H:i:s", time()));
// Directory Separator
define('DS', DIRECTORY_SEPARATOR);
// Root Directory
define('root_dir', DIRNAME(__FILE__) . DS);

// Load Composer Autoloader
if (file_exists(root_dir . 'vendor/autoload.php')) {
    require_once root_dir . 'vendor/autoload.php';
}

// Library Directory
define('lib_dir', root_dir . 'lib' . DS);
// Module Directory
define('mod_dir', root_dir . 'mod' . DS);
// Config Directory
define('config_dir', root_dir . 'config' . DS);
// Web Sub-directory
$_subdir = explode('index.php', $_SERVER['SCRIPT_NAME']);
$subdir = isset($_subdir['0']) ? $_subdir['0'] : '/';
define('sub_dir', $subdir);
// Yaml Directory
define('yaml_dir', root_dir . 'yaml' . DS);
// Plugin Directory
define('plugin_dir', root_dir . 'plugins' . DS);
// Upload / Storage Directory
define('up_dir', root_dir . 'static' . DS);
// Cache Directory
define('cache_dir', root_dir . 'cache' . DS);
// Log Directory
define('log_dir', root_dir . 'log' . DS);
// Error Log Path
define('PHPLOG', log_dir . 'php-error-log.txt');
ini_set('error_log', PHPLOG);

// Template Directory
define('template_dir', root_dir . 'templates' . DS);

/* Module Sub-directories */
// Language Directory
define('lang_dir', 'lang' . DS);
// Validator Directory
define('validator_dir', 'validator' . DS);
// Model Directory
define('model_dir', 'model' . DS);
// Helper Directory
define('helper_dir', 'helper' . DS);

// Registry Class
require_once(lib_dir . 'Registry.class.php');
// Loader Class
require_once(lib_dir . 'Load.class.php');
// Shared Traits
if (file_exists(lib_dir . 'Traits' . DS . 'ModuleValidatorTrait.php')) {
    require_once(lib_dir . 'Traits' . DS . 'ModuleValidatorTrait.php');
}
if (file_exists(lib_dir . 'Traits' . DS . 'PublicHelperTrait.php')) {
    require_once(lib_dir . 'Traits' . DS . 'PublicHelperTrait.php');
}
// Autoload Files
require_once(config_dir . 'autoload.config.php');
$autoload = Registry::get('autoload_config');
// Automatically Loaded Library Files
if (isset($autoload['libfile'][0])) {
    foreach ($autoload['libfile'] as $libfile) {
        Load::libfile($libfile);
    }
}
// Automatically Loaded Configuration Files
if (isset($autoload['config'][0])) {
    foreach ($autoload['config'] as $configName) {
        Load::config($configName);
    }
}

require_once mod_dir . 'index/indexMain.php';

// Automatically Executed Library Files
if (isset($autoload['lib'][0])) {
    foreach ($autoload['lib'] as $lib) {
        Load::lib($lib);
    }
}

unset($autoload);
