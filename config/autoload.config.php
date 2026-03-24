<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/26/11 - 1:58 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:autoload.config.php
 * Açıklama:Otomatik Yüklenecek Kütüphane ve Dosyalar
 */
if (defined('root_dir') === False) exit(':)');

// Static Sınıfları Yükleyebiliriz
$autoload['libfile'] = array(
    // 'Error',
    'Req',
    'Helper',
    'Text',
    'Run',
    'Roll',
    'Cache',
    'Database',
    'Orm',
    'BaseModuleController',
    'BaseModuleValidator',
    'DataTableService',
    'ImageService'
);
// Otomatik Çalıştırılacak Kütüphaneler
$autoload['lib'] = array();

// Otomatik Yüklenecek Config Dosyaları
$autoload['config'] = array(
    'db',
    'site',
    'i18n',
    'url',
    'session'
);
Kayit::ekle('autoload_config', $autoload);
unset($autoload);
