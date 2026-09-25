<?php

/**
 * Amed Framework
 * User: a.k.mutlu
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: site.config.example.php
 * Description: Site configuration values (Example template)
 */
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($host == 'localhost' 
|| strpos($host, 'localhost') !== false
|| strpos($host, 'pinggy.link') !== false
) {
    // 1. Localhost environment settings
    $site['address'] = 'http://'.$host.'/';
    $site['saddress'] = 'http://'.$host.'/';
    $site['iaddress'] = 'http://'.$host.'/static/';
    $site['taddress'] = 'http://'.$host.'/templates/';
} else {
    // 2. Production environment settings with CDN subdomains
    $site['address'] = 'https://sitename.com/';
    $site['saddress'] = 'https://sitename.com/';
    $site['iaddress'] = 'https://static.sitename.com/';
    $site['taddress'] = 'https://assets.sitename.com/';
}
$site['js'] = 'templates/base_static/js/';
$site['lang'] = 'en';
$site['default_lang'] = $site['lang'];
$site['author'] = 'AKMSoftware';
$site['generator'] = 'Amed Framework';
$site['developer'] = 'AKMSoftware';
$site['version'] = '1.0.1.93';
$site['build'] = 'AKM LearnLy';
$site['app_template'] = 'furniture';

if (isset($_COOKIE['tema'])) {
    $full = template_dir . basename(Req::Clear($_COOKIE['tema']));
    if (file_exists($full) && is_dir($full)) {
        $site['app_template'] = basename(Req::Clear($_COOKIE['tema']));
    }
}

$localSiteConfig = __DIR__ . DIRECTORY_SEPARATOR . 'site.config.local.php';
if (file_exists($localSiteConfig)) {
    require $localSiteConfig;
    if (isset($site_local) && is_array($site_local)) {
        $site = array_merge($site, $site_local);
    }
}

// Site theme
Registry::add('app_template', $site['app_template']);
// Admin theme
Registry::add('admin_template', 'admin');
// Default module
Registry::add('app_default_mod', 'index');
// Default function
Registry::add('app_default_function', 'index');
// Site language
Registry::add('lang', $site['lang']);
Registry::add('default_lang', $site['default_lang']);

Registry::add('site_config', $site);
unset($site);
