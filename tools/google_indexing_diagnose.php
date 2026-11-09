<?php

/**
 * Amed Framework - Google Indexing & Search Console Read-Only Diagnostic Tool
 *
 * Strictly CLI-only and read-only. Never calls publish, PUT sitemaps, or any
 * state-changing endpoint.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$rootDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
if (!defined('root_dir')) {
    define('root_dir', $rootDir);
}
if (!defined('lib_dir')) {
    define('lib_dir', $rootDir . 'lib' . DIRECTORY_SEPARATOR);
}

require_once lib_dir . 'Registry.class.php';
require_once lib_dir . 'HttpClient.class.php';
require_once lib_dir . 'GoogleIndexingService.class.php';

/**
 * Perform a read-only HTTP GET request with Bearer token authorization.
 *
 * @param string $url
 * @param string $token
 * @return array{status: int, body: string, json: array|null, error: string}
 */
function diagnoseHttpGet(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false || $err !== '') {
        return ['status' => $status, 'body' => '', 'json' => null, 'error' => $err];
    }

    $decoded = json_decode((string) $body, true);
    return [
        'status' => $status,
        'body' => (string) $body,
        'json' => is_array($decoded) ? $decoded : null,
        'error' => '',
    ];
}

echo "=== Amed Bulletin Google Indexing & Search Console Diagnostic ===\n";
echo "Date (UTC): " . gmdate('Y-m-d\TH:i:s\Z') . "\n\n";

$service = new GoogleIndexingService();
$refClass = new ReflectionClass(GoogleIndexingService::class);

$credProp = $refClass->getProperty('credentialsPath');
$credPath = (string) $credProp->getValue($service);

if ($credPath === '' || !file_exists($credPath)) {
    fwrite(STDERR, "[FAIL] Service account credentials file not found.\n");
    exit(1);
}

$credJson = json_decode((string) @file_get_contents($credPath), true);
$clientEmail = is_array($credJson) && !empty($credJson['client_email']) ? $credJson['client_email'] : 'unknown';
echo "[1] Credentials loaded\n";
echo "    Path        : {$credPath}\n";
echo "    Client Email: {$clientEmail}\n\n";

$getTokenMethod = $refClass->getMethod('getAccessToken');
$webmastersToken = $getTokenMethod->invoke($service, 'https://www.googleapis.com/auth/webmasters.readonly');
if (!$webmastersToken) {
    $webmastersToken = $getTokenMethod->invoke($service, 'https://www.googleapis.com/auth/webmasters');
}
$indexingToken = $getTokenMethod->invoke($service, 'https://www.googleapis.com/auth/indexing');

echo "[2] OAuth2 Token Minting\n";
echo "    webmasters token: " . ($webmastersToken ? "OK" : "FAIL") . "\n";
echo "    indexing token  : " . ($indexingToken ? "OK" : "FAIL") . "\n\n";

if (!$webmastersToken || !$indexingToken) {
    fwrite(STDERR, "[FAIL] Could not mint required OAuth2 tokens.\n");
    exit(2);
}

$sitesRes = diagnoseHttpGet('https://www.googleapis.com/webmasters/v3/sites', $webmastersToken);
echo "[3] Search Console Sites (/webmasters/v3/sites) — HTTP {$sitesRes['status']}\n";
$targetSiteUrl = 'sc-domain:amedbulten.com';
$foundTargetSite = false;

if ($sitesRes['status'] === 200 && !empty($sitesRes['json']['siteEntry'])) {
    foreach ($sitesRes['json']['siteEntry'] as $entry) {
        $sUrl = $entry['siteUrl'] ?? '';
        $perm = $entry['permissionLevel'] ?? '';
        echo "    - {$sUrl} ({$perm})\n";
        if ($sUrl === $targetSiteUrl) {
            $foundTargetSite = true;
        }
    }
} else {
    fwrite(STDERR, "    [FAIL] Unable to list Search Console sites: {$sitesRes['body']}\n");
    exit(3);
}

if (!$foundTargetSite) {
    fwrite(STDERR, "    [FAIL] Target property {$targetSiteUrl} not found in service account sites.\n");
    exit(3);
}
echo "\n";

$encodedSite = rawurlencode($targetSiteUrl);
$sitemapsRes = diagnoseHttpGet("https://www.googleapis.com/webmasters/v3/sites/{$encodedSite}/sitemaps", $webmastersToken);
echo "[4] Sitemaps Status ({$targetSiteUrl}) — HTTP {$sitemapsRes['status']}\n";
if ($sitemapsRes['status'] === 200 && !empty($sitemapsRes['json']['sitemap'])) {
    printf("    %-48s | %-9s | %-7s | %-20s | %-20s | %s\n", 'Path', 'Submitted', 'Indexed', 'LastSubmitted', 'LastDownloaded', 'Errors');
    echo "    " . str_repeat('-', 122) . "\n";
    foreach ($sitemapsRes['json']['sitemap'] as $sm) {
        $path = $sm['path'] ?? '';
        $errors = (int) ($sm['errors'] ?? 0);
        $lastSub = $sm['lastSubmitted'] ?? '-';
        $lastDl = $sm['lastDownloaded'] ?? '-';
        $submittedCount = 0;
        $indexedCount = 0;
        if (!empty($sm['contents']) && is_array($sm['contents'])) {
            foreach ($sm['contents'] as $contentRow) {
                $submittedCount += (int) ($contentRow['submitted'] ?? 0);
                $indexedCount += (int) ($contentRow['indexed'] ?? 0);
            }
        }
        printf("    %-48s | %-9d | %-7d | %-20s | %-20s | %d\n", $path, $submittedCount, $indexedCount, $lastSub, $lastDl, $errors);
    }
    echo "    Note: Sitemap 'indexed=0' counters are a known GSC API reporting artifact when searchAnalytics shows live impressions (see SERVER.md §7.2).\n\n";
} else {
    echo "    No sitemaps returned or error (HTTP {$sitemapsRes['status']}).\n\n";
}

echo "[5] Indexing API Metadata Probe (urlNotifications/metadata)\n";
$statePath = $rootDir . 'data' . DIRECTORY_SEPARATOR . 'indexing_state.json';
$probeUrls = ['https://amedbulten.com/tr/'];

if (file_exists($statePath)) {
    $stateData = json_decode((string) @file_get_contents($statePath), true);
    if (!empty($stateData['urls']) && is_array($stateData['urls'])) {
        $newestUrl = null;
        $newestTs = -1;
        foreach ($stateData['urls'] as $key => $ts) {
            $parts = explode('|', (string) $key);
            $candidateUrl = $parts[0] ?? '';
            if ($candidateUrl !== '' && (int) $ts > $newestTs && !in_array($candidateUrl, $probeUrls, true)) {
                $newestTs = (int) $ts;
                $newestUrl = $candidateUrl;
            }
        }
        if ($newestUrl !== null) {
            $probeUrls[] = $newestUrl;
        }
    }
}

foreach ($probeUrls as $pUrl) {
    $metaEndpoint = 'https://indexing.googleapis.com/v3/urlNotifications/metadata?url=' . rawurlencode($pUrl);
    $metaRes = diagnoseHttpGet($metaEndpoint, $indexingToken);
    echo "    URL   : {$pUrl}\n";
    echo "    Status: HTTP {$metaRes['status']}\n";
    if ($metaRes['status'] === 404) {
        echo "    Hint  : 404 NOT_FOUND confirms SERVER.md §7.1 — Google Indexing API accepts HTTP 200 on publish but silently discards non-JobPosting/BroadcastEvent URLs.\n";
    } elseif ($metaRes['status'] === 200) {
        echo "    Body  : " . json_encode($metaRes['json'], JSON_UNESCAPED_SLASHES) . "\n";
    }
}
echo "\n";

echo "[6] Search Analytics (Last 28 Days — {$targetSiteUrl})\n";
$endDate = gmdate('Y-m-d');
$startDate = gmdate('Y-m-d', time() - (28 * 86400));
$saEndpoint = "https://www.googleapis.com/webmasters/v3/sites/{$encodedSite}/searchAnalytics/query";

$queryRes = HttpClient::postJson($saEndpoint, [
    'startDate' => $startDate,
    'endDate' => $endDate,
    'dimensions' => ['query'],
    'rowLimit' => 10,
], [
    'Authorization: Bearer ' . $webmastersToken,
], 15);

echo "    6a. Top Queries ({$startDate} -> {$endDate}) — HTTP {$queryRes['status']}\n";
if ($queryRes['status'] === 200 && !empty($queryRes['json']['rows'])) {
    printf("        %-40s | %-6s | %-11s | %s\n", 'Query', 'Clicks', 'Impressions', 'Position');
    echo "        " . str_repeat('-', 72) . "\n";
    foreach ($queryRes['json']['rows'] as $row) {
        $q = $row['keys'][0] ?? '';
        printf("        %-40s | %-6d | %-11d | %.1f\n", $q, (int) ($row['clicks'] ?? 0), (int) ($row['impressions'] ?? 0), (float) ($row['position'] ?? 0.0));
    }
} else {
    echo "        (No query rows returned)\n";
}
echo "\n";

$pageRes = HttpClient::postJson($saEndpoint, [
    'startDate' => $startDate,
    'endDate' => $endDate,
    'dimensions' => ['page'],
    'rowLimit' => 10,
], [
    'Authorization: Bearer ' . $webmastersToken,
], 15);

echo "    6b. Top Pages ({$startDate} -> {$endDate}) — HTTP {$pageRes['status']}\n";
if ($pageRes['status'] === 200 && !empty($pageRes['json']['rows'])) {
    printf("        %-68s | %-6s | %-11s | %s\n", 'Page', 'Clicks', 'Impressions', 'Position');
    echo "        " . str_repeat('-', 100) . "\n";
    foreach ($pageRes['json']['rows'] as $row) {
        $p = $row['keys'][0] ?? '';
        printf("        %-68s | %-6d | %-11d | %.1f\n", $p, (int) ($row['clicks'] ?? 0), (int) ($row['impressions'] ?? 0), (float) ($row['position'] ?? 0.0));
    }
} else {
    echo "        (No page rows returned)\n";
}

echo "\n=== Diagnostic Complete (OK) ===\n";
exit(0);
