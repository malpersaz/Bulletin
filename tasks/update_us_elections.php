<?php
/**
 * tasks/update_us_elections.php - Command Line Runner for US Elections Results Refresh
 *
 * Usage:
 *   php tasks/update_us_elections.php [--force]
 *
 * Amed Framework
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-elections-task';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'UsElectionDataService.class.php';

$options = getopt('', ['force']);
$isForced = isset($options['force']);

echo "Refreshing US Elections results data...\n";

$result = UsElectionDataService::refreshData($isForced);

if (!empty($result['senate']) && !empty($result['house'])) {
    $senateDem = $result['senate']['democrats'] ?? 0;
    $senateRep = $result['senate']['republicans'] ?? 0;
    $houseDem = $result['house']['democrats'] ?? 0;
    $houseRep = $result['house']['republicans'] ?? 0;
    $updatedAt = date('c', (int) ($result['updated_at'] ?? time()));
    echo "Success: US Elections results refreshed (Senate: Dem {$senateDem} - Rep {$senateRep} | House: Dem {$houseDem} - Rep {$houseRep}, updated at {$updatedAt}).\n";
    exit(0);
}

echo "Warning: US Elections refresh did not return valid data.\n";
exit(1);
