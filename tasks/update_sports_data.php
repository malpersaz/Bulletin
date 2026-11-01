<?php
/**
 * tasks/update_sports_data.php - Command Line Runner for Super League Cache Refresh
 *
 * Usage:
 *   php tasks/update_sports_data.php [--force]
 *
 * Amed Framework
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-sports-task';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'SportsDataService.class.php';

$options = getopt('', ['force']);
$isForced = isset($options['force']);

echo "Refreshing Super League sports data...\n";

$result = SportsDataService::refreshSuperLeagueData($isForced);

if (!empty($result['standings'])) {
    $standingsCount = count($result['standings']);
    $matchesCount = count($result['matches'] ?? []);
    $matchdayNumber = $result['matchday'] ?? 10;
    $cachedTime = $result['updated_iso'] ?? date('c');
    echo "Success: Super League data refreshed ({$standingsCount} standings, {$matchesCount} matches, matchday {$matchdayNumber}, updated at {$cachedTime}).\n";
    exit(0);
}

echo "Warning: Super League refresh did not return active items.\n";
exit(1);
