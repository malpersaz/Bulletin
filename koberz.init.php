<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/23/11 - 5:24 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:koberz.init.php
 * Açıklama: bootstrap dosyası
 */

/*
 * Define Edilen Değişkenler
 */
// Debug Durumu
define('DEBUG', TRUE);
// Framework Loaded Flag
define('AKM_LOADED', TRUE);
// Şimdiki Zaman
define('NOW', date("Y-m-d H:i:s", time()));
// Klasorler Arasındaki slash işareti win \ Linux /
define('DS', DIRECTORY_SEPARATOR);
// Sitenin bulunduğu Dizin
define('root_dir', DIRNAME(__FILE__) . DS);
// Kütüphane Dizini
define('lib_dir', root_dir . 'lib' . DS);
// Modül Dizini
define('mod_dir', root_dir . 'mod' . DS);
// Config Dizini
define('config_dir', root_dir . 'config' . DS);
// Sistemin Bulunduğu Dizin
$_subdir = explode('index.php', $_SERVER['SCRIPT_NAME']);
$subdir = isset($_subdir['0']) ? $_subdir['0'] : '/';
define('sub_dir', $subdir);
// Yaml Dizini
define('yaml_dir', root_dir . 'yaml' . DS);
// Plugin Dizini
define('plugin_dir', root_dir . 'plugins' . DS);
// Yükleme Dizini
define('up_dir', root_dir . 'depo' . DS);
// Cache Dir
define('cache_dir', root_dir . 'cache' . DS);
// Log dir
define('log_dir', root_dir . 'log' . DS);
// Hatalar Loglansınmı?
define('PHPLOG', log_dir . 'php-error-log.txt');
ini_set('error_log', PHPLOG);

// define('PHPLOG', 'show');
//define('PHPLOG', FALSE);
// Template Dir
define('template_dir', root_dir . 'templates' . DS);
/* Modül içerisindeki ana dizinler */
// Dil dizini
define('lang_dir', 'lang' . DS);
// Validator Dizini
define('validator_dir', 'validator' . DS);
// Model Dizini
define('model_dir', 'model' . DS);
// Model Dizini
define('helper_dir', 'helper' . DS);
// Eğer Log dosyası varsa Error Handleri set edelim



// Kayıt Sınıfı
require_once(lib_dir . 'Kayit.class.php');
// Loader Sınıfı
require_once(lib_dir . 'Load.class.php');
// Otomatik Yüklenecek Dosyalar
require_once(config_dir . 'autoload.config.php');
$autoload = Kayit::getir('autoload_config');
// Otomatik Yüklenecek Kütüphane Dosyaları
if (isset($autoload['libfile'][0])) {
    foreach ($autoload['libfile'] as $libfile) {
        Load::libfile($libfile);
    }
}
// Otomatik Yüklenecek Ayar Dosyaları
if (isset($autoload['config'][0])) {
    foreach ($autoload['config'] as $conf) {
        Load::config($conf);
    }
}

require_once mod_dir . 'index/indexMain.php';

// Otomatik Çalıştırılacak Kütüphane Dosyaları
if (isset($autoload['lib'][0])) {
    foreach ($autoload['lib'] as $lib) {
        Load::lib($lib);
    }
}

unset($autoload);
