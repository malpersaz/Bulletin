<?php
/**
 * tasks/agent_worker.php - Background Worker for Asynchronous Agent Jobs
 *
 * Usage:
 *   php tasks/agent_worker.php [--once] [--daemon] [--job-id=<id>] [--limit=<n>]
 *
 * Amed Framework
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-agent-worker';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'AgentRunner.class.php';

$options = getopt('', ['once', 'daemon', 'job-id:', 'limit:']);
$isOnce = isset($options['once']);
$isDaemon = isset($options['daemon']);
$targetJobId = $options['job-id'] ?? null;
$limit = isset($options['limit']) ? max(1, min(50, (int) $options['limit'])) : 10;

AgentRunner::ensureJobTable();

$workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(4));

if ($targetJobId !== null) {
    echo "[INFO] Processing single target job: {$targetJobId} with worker {$workerId}\n";
    $success = AgentRunner::runJob((string) $targetJobId, $workerId);
    if ($success) {
        echo "[DONE] Job {$targetJobId} completed successfully.\n";
        exit(0);
    } else {
        echo "[FAIL] Job {$targetJobId} failed or was not in runnable state.\n";
        exit(1);
    }
}

echo "=== Amed Bulletin Agent Worker started ({$workerId}) ===\n";

$processedCount = 0;

do {
    // 1. Reap stale jobs (heartbeat > 120s old)
    $reaped = AgentRunner::reapStaleJobs(120);
    if ($reaped > 0) {
        echo "[REAPER] Reaped {$reaped} stale job(s) into failed status.\n";
    }

    // 2. Atomically claim next available job
    $claimed = AgentRunner::claimNextJob($workerId);

    if (!$claimed) {
        if ($isOnce) {
            echo "[INFO] No queued jobs found. Exiting (--once).\n";
            break;
        }
        if (!$isDaemon) {
            echo "[INFO] No queued jobs found. Finished batch.\n";
            break;
        }
        sleep(3);
        continue;
    }

    $jId = (string) $claimed['job_id'];
    echo "[RUN] Executing job: {$jId}...\n";
    $ok = AgentRunner::runJob($jId, $workerId);
    echo $ok ? "[DONE] Job {$jId} succeeded.\n" : "[WARN] Job {$jId} ended with failure or waiting.\n";
    $processedCount++;

    if ($isOnce) {
        break;
    }

    if (!$isDaemon && $processedCount >= $limit) {
        echo "[INFO] Reached batch limit ({$limit}). Finished batch.\n";
        break;
    }

    if ($isDaemon) {
        usleep(500000); // 0.5s pause between jobs
    }
} while ($isDaemon || $processedCount < $limit);

echo "=== Worker terminated cleanly ===\n";
exit(0);
