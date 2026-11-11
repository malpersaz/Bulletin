<?php
/**
 * tasks/social_share_worker.php - Background Outbox Worker for Social Media Publishing
 *
 * Usage:
 *   php tasks/social_share_worker.php [--once] [--daemon] [--limit=<n>]
 *
 * Amed Framework — Bulletin CMS
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-social-share-worker';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'NewsSocialShareService.class.php';

$options = getopt('', ['once', 'daemon', 'limit:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php tasks/social_share_worker.php [options]\n";
    echo "Options:\n";
    echo "  --once       Process one batch and exit immediately\n";
    echo "  --daemon     Run continuously in loop\n";
    echo "  --limit=<n>  Batch limit per run (default: 5, max: 50)\n";
    echo "  --help       Show this help message\n";
    exit(0);
}

$isOnce = isset($options['once']) || !isset($options['daemon']);
$isDaemon = isset($options['daemon']);
$limit = isset($options['limit']) ? max(1, min(50, (int) $options['limit'])) : 5;

$workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(3));

echo "[" . date('Y-m-d H:i:s') . "] === Amed Social Share Outbox Worker started ({$workerId}) ===\n";

$totalProcessed = 0;
$totalPublished = 0;

do {
    $stats = NewsSocialShareService::processPendingJobs($limit, $workerId);
    $processed = $stats['processed'] ?? 0;
    $published = $stats['published'] ?? 0;
    $inProgress = $stats['in_progress'] ?? 0;
    $failed = $stats['failed'] ?? 0;
    $skipped = $stats['skipped'] ?? 0;

    $totalProcessed += $processed;
    $totalPublished += $published;

    if ($processed > 0) {
        echo sprintf(
            "[%s] Batch: %d processed | %d published | %d in-progress | %d failed | %d skipped\n",
            date('Y-m-d H:i:s'),
            $processed,
            $published,
            $inProgress,
            $failed,
            $skipped
        );
    } else {
        if ($isOnce) {
            echo "[" . date('Y-m-d H:i:s') . "] No due jobs found. Exiting.\n";
            break;
        }
    }

    if ($isDaemon) {
        // Sleep 5s between checks if idle, or 1s if busy
        $sleepSec = ($processed === 0) ? 5 : 1;
        sleep($sleepSec);
    }
} while ($isDaemon);

echo sprintf(
    "[%s] === Worker terminated cleanly (Total Processed: %d, Total Published: %d) ===\n",
    date('Y-m-d H:i:s'),
    $totalProcessed,
    $totalPublished
);

exit(0);
