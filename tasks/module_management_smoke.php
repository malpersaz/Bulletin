<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/tasks/module_management_smoke.php';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'module-management-smoke';
}

require_once dirname(__DIR__) . '/koberz.init.php';

$runtime = Load::lib('ModuleRuntime');

$moduleFeatureMap = [
    'mod_content' => 'content.front',
    'mod_news' => 'news.front',
    'mod_publication' => 'publication.front',
    'mod_video_embed' => 'video_embed.front',
];

$report = [
    'modules' => [],
    'features' => [],
    'issues' => [],
];

foreach ($moduleFeatureMap as $module => $feature) {
    $health = $runtime->getModuleHealth($module);
    $featureReady = $runtime->isReady($feature);
    $failureReason = $runtime->getFailureReason($feature);

    $report['modules'][$module] = $health;
    $report['features'][$feature] = [
        'ready' => $featureReady,
        'failure_reason' => $failureReason,
    ];

    if (($health['status'] ?? null) === 'ready' && $featureReady !== true) {
        $report['issues'][] = [
            'level' => 'error',
            'type' => 'ready_status_mismatch',
            'module' => $module,
            'feature' => $feature,
            'details' => $failureReason,
        ];
        continue;
    }

    if (($health['status'] ?? null) === 'degraded') {
        $report['issues'][] = [
            'level' => 'warning',
            'type' => 'degraded_module',
            'module' => $module,
            'feature' => $feature,
            'details' => $failureReason,
        ];
        continue;
    }

    if (($health['status'] ?? null) === 'installed' && $featureReady !== true) {
        $report['issues'][] = [
            'level' => 'warning',
            'type' => 'installed_but_not_ready',
            'module' => $module,
            'feature' => $feature,
            'details' => $failureReason,
        ];
        continue;
    }
}

$asJson = in_array('--json', $argv, true);

if ($asJson) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "Module Management Smoke Check\n";
    echo "============================\n\n";

    foreach ($report['modules'] as $module => $health) {
        $feature = $moduleFeatureMap[$module];
        $featureState = $report['features'][$feature];

        echo $module . "\n";
        echo "  status: " . ($health['status'] ?? 'unknown') . "\n";
        echo "  installed: " . ($health['installed'] ? 'yes' : 'no') . "\n";
        echo "  ready: " . ($health['ready'] ? 'yes' : 'no') . "\n";

        if (!empty($health['missing_modules'])) {
            echo "  missing_modules: " . implode(', ', $health['missing_modules']) . "\n";
        }

        if (!empty($health['missing_tables'])) {
            echo "  missing_tables: " . implode(', ', $health['missing_tables']) . "\n";
        }

        if ($featureState['failure_reason'] !== null) {
            echo "  failure_reason: " . $featureState['failure_reason']['reason'] . "\n";
        }

        echo "\n";
    }

    if (empty($report['issues'])) {
        echo "No issues detected.\n";
    } else {
        echo "Issues\n";
        echo "------\n";
        foreach ($report['issues'] as $issue) {
            echo strtoupper($issue['level']) . ': ' . $issue['type'] . ' [' . $issue['module'] . "]\n";
        }
    }
}

$hasErrors = false;
foreach ($report['issues'] as $issue) {
    if ($issue['level'] === 'error') {
        $hasErrors = true;
        break;
    }
}

exit($hasErrors ? 1 : 0);
