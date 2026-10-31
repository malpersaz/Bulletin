<?php
/**
 * tasks/update_market_rates.php - Command Line Runner for Market Rates Cache Refresh
 *
 * Usage:
 *   php tasks/update_market_rates.php [--force]
 *
 * Amed Framework
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-market-task';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'MarketDataService.class.php';

$options = getopt('', ['force']);
$isForced = isset($options['force']);

echo "Refreshing market rates data...\n";

$result = MarketDataService::refreshRates($isForced);

if (!empty($result['items'])) {
    $itemCount = count($result['items']);
    $cachedTime = $result['updated_iso'] ?? ($result['last_updated'] ?? 'unknown');
    echo "Success: Market rates refreshed ({$itemCount} items, updated at {$cachedTime}).\n";
    exit(0);
}

echo "Warning: Market rates refresh did not return active items.\n";
exit(1);
