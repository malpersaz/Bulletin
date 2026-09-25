<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: site.config.example.php
 * Description: Site configuration values (Example template)
 */
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = ($host === 'localhost' 
    || strpos($host, 'localhost') !== false 
    || strpos($host, '127.0.0.1') !== false
    || strpos($host, 'pinggy.link') !== false
);

// Enforce HTTPS in production environments while keeping HTTP for local development
$scheme = $isLocal ? 'http' : 'https';

// Perform HTTP to HTTPS 301 redirection for live non-CLI requests
if (!$isLocal && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') && (($_SERVER['SERVER_PORT'] ?? 80) != 443)) {
    if (PHP_SAPI !== 'cli') {
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit();
    }
}

$site['address']  = $scheme . '://' . $host . '/';
$site['saddress'] = $scheme . '://' . $host . '/';

if ($isLocal) {
    // Localhost environment settings
    $site['iaddress'] = $scheme . '://' . $host . '/static/';
    $site['taddress'] = $scheme . '://' . $host . '/templates/';
} else {
    // Production environment settings with CDN subdomains
    $baseDomain = str_replace('www.', '', $host);
    $site['iaddress'] = 'https://static.' . $baseDomain . '/';
    $site['taddress'] = 'https://assets.' . $baseDomain . '/';
}
$site['js'] = 'templates/base_static/js/';
$site['lang'] = 'ku';
$site['default_lang'] = $site['lang'];
$site['author'] = 'AKMSoftware';
$site['generator'] = 'Amed Framework';
$site['developer'] = 'AKMSoftware';

// Automatically resolve active framework version from docs/VERSION.md with fallback
$site['version'] = '1.0.93';
$versionFile = (defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR) . 'docs' . DIRECTORY_SEPARATOR . 'VERSION.md';
if (is_readable($versionFile)) {
    $versionHandle = fopen($versionFile, 'r');
    if ($versionHandle) {
        while (($versionLine = fgets($versionHandle)) !== false) {
            if (preg_match('/^##\s*v?([0-9]+(?:\.[0-9]+)+)/i', trim($versionLine), $versionMatches)) {
                $site['version'] = $versionMatches[1];
                break;
            }
        }
        fclose($versionHandle);
    }
}

$site['build'] = 'Rojname CMS';
$site['app_template'] = $site['app_template'] ?? 'amida';

$cookieTheme = $_COOKIE['theme'] ?? $_COOKIE['tema'] ?? null;
if ($cookieTheme !== null) {
    $full = template_dir . basename(Req::Clear($cookieTheme));
    if (file_exists($full) && is_dir($full)) {
        $site['app_template'] = basename(Req::Clear($cookieTheme));
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
