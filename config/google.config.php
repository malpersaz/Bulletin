<?php
/**
 * Amed Framework
 * User: Antigravity Advisor
 * Date: 7/15/26 - 1:04 AM
 * Copyright: AKM Software
 * File: google.config.php
 * Description: Shared Google Services & APIs Configuration
 */

$dataDir = dirname(__DIR__) . DS . 'data' . DS;
$credentialsPath = $dataDir . 'credentials.json';
$settingsPath = $dataDir . 'google_settings.json';

$bucketName = '';
$geminiApiKey = '';

if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true);
    $bucketName = $settings['bucket_name'] ?? '';
    $geminiApiKey = $settings['gemini_api_key'] ?? '';
}

$googleConfig = [
    'credentials_path' => file_exists($credentialsPath) ? $credentialsPath : '',
    'bucket_name' => $bucketName,
    'gemini_api_key' => $geminiApiKey,
    'redis' => [
        'enabled' => true,
        'host' => file_exists('/.dockerenv') ? 'redis' : '127.0.0.1',
        'port' => 6379
    ]
];

// Register config in framework container
Registry::add('google_config', $googleConfig);
