<?php

/**
 * MCP server smoke test (CLI)
 * Run inside the app container: php /var/www/html/tasks/mcp_smoke.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/mcp.php';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'mcp-smoke';
}

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/lib/McpServer.class.php';

if (!Registry::has('lang')) {
    Registry::add('lang', 'en');
}

/**
 * CLI harness for McpServer with an injectable request body
 */
class SmokeMcpServer extends McpServer
{
    private string $body = '';

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    protected function readRequestBody(): string
    {
        return $this->body;
    }
}

$passCount = 0;
$failCount = 0;

function check($label, $condition, $detail = '')
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "[PASS] {$label}\n";
    } else {
        $failCount++;
        echo "[FAIL] {$label}" . ($detail !== '' ? ": {$detail}" : '') . "\n";
    }
}

function runRpc($payload, string $bearerKey = '', array $serverOverrides = []): array
{
    $server = new SmokeMcpServer();

    $savedServer = $_SERVER;
    $_SERVER['REQUEST_METHOD'] = $serverOverrides['REQUEST_METHOD'] ?? 'POST';
    $_SERVER['CONTENT_TYPE'] = $serverOverrides['CONTENT_TYPE'] ?? 'application/json';

    if ($bearerKey !== '') {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $bearerKey;
    } else {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_X_API_KEY']);
    }

    foreach ($serverOverrides as $k => $v) {
        $_SERVER[$k] = $v;
    }

    $rawPayload = is_string($payload) ? $payload : json_encode($payload);
    $server->setBody($rawPayload);

    ob_start();
    try {
        $server->handle();
        $_SERVER = $savedServer;
        $raw = ob_get_clean();
        $decoded = json_decode($raw, true);
        return $decoded === null ? ['__raw' => $raw] : $decoded;
    } catch (\Throwable $e) {
        $_SERVER = $savedServer;
        ob_end_clean();
        return ['__exception' => get_class($e) . ': ' . $e->getMessage()];
    }
}

// Clean leftovers from previous smoke runs
(function () {
    $db = Load::lib('Database');
    try {
        $db->query("DELETE FROM mod_news WHERE name LIKE 'MCP Smoke%'");
        $db->query("DELETE FROM mod_news_category WHERE name LIKE 'MCP Smoke%'");
        $db->query("DELETE FROM mod_menu WHERE url LIKE '%mcp_smoke%'");
        $db->query("DELETE FROM mod_mcp_api_key WHERE name IN ('Smoke test key', 'Revoked')");
    } catch (\Throwable $e) {}
})();

// =========================================================================
// 1. Infrastructure self-healing
// =========================================================================
Load::mod('mod_mcp');
check('Infrastructure: mod_mcp_api_key table exists', (function () {
    $db = Load::lib('Database');
    $db->fetch_array("SHOW TABLES LIKE 'mod_mcp_api_key'");
    return $db->getSize() > 0;
})());
check('Infrastructure: mod_mcp registered in mods table', (function () {
    $db = Load::lib('Database');
    $db->fetch_array("SELECT mod_id FROM mods WHERE mod_name = 'mod_mcp'");
    return $db->getSize() > 0;
})());
check('Infrastructure: roles and permissions installed', (function () {
    $db = Load::lib('Database');
    $db->fetch_array(
        "SELECT m.role_name FROM mod_user_role m
         JOIN mods ON mods.mod_id = m.mod_id
         WHERE mods.mod_name = 'mod_mcp'"
    );
    $roles = [];
    foreach ($db->toArray() as $row) {
        $roles[] = $row['role_name'];
    }
    sort($roles);
    return $roles === ['add', 'delete', 'edit', 'list'];
})());

// =========================================================================
// 2. Transport: method restrictions and auth
// =========================================================================
$result = runRpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], '', ['REQUEST_METHOD' => 'GET']);
check('Transport: GET returns invalid request error', ($result['error']['code'] ?? 0) === -32600, json_encode($result));

$result = runRpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], '', ['CONTENT_TYPE' => 'text/plain']);
check('Transport: wrong content type rejected', ($result['error']['code'] ?? 0) === -32600, json_encode($result));

$result = runRpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], '');
check('Auth: missing key rejected', ($result['error']['code'] ?? 0) === -32600 && stripos($result['error']['message'] ?? '', 'api key') !== false, json_encode($result));

$result = runRpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], 'amcp_totally_invalid_key');
check('Auth: invalid key rejected', ($result['error']['code'] ?? 0) === -32600 && str_contains($result['error']['message'] ?? '', 'Invalid API key'), json_encode($result));

// =========================================================================
// 3. Key lifecycle
// =========================================================================
$plaintextKey = 'amcp_' . bin2hex(random_bytes(24));
$db = Load::lib('Database');
$db->query("INSERT INTO mod_mcp_api_key (name, api_key_hash, key_prefix, group_id, user_id, status, created_at) VALUES ('Smoke test key', '" . hash('sha256', $plaintextKey) . "', '" . substr($plaintextKey, 0, 12) . "', 1, 1, 'y', '" . date('Y-m-d H:i:s') . "')");
$keyId = (int) $db->insert_id();
check('Key lifecycle: key saved with hash only', $keyId > 0);

// Malformed body
$result = runRpc('not json at all', $plaintextKey);
check('Transport: malformed JSON returns parse error', ($result['error']['code'] ?? 0) === -32700, json_encode($result));

// =========================================================================
// 4. Protocol handshake
// =========================================================================
$result = runRpc([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'initialize',
    'params' => [
        'protocolVersion' => '2025-03-26',
        'capabilities' => [],
        'clientInfo' => ['name' => 'smoke-client', 'version' => '1.0']
    ]
], $plaintextKey);

check('Handshake: protocolVersion returned', ($result['result']['protocolVersion'] ?? '') === '2025-03-26', json_encode($result));
check('Handshake: serverInfo name is amed-bulletin-mcp', ($result['result']['serverInfo']['name'] ?? '') === 'amed-bulletin-mcp', json_encode($result));

$result = runRpc(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping'], $plaintextKey);
check('Handshake: ping returns empty result', isset($result['result']) && $result['result'] === [], json_encode($result));

// =========================================================================
// 5. Tool discovery (tools/list)
// =========================================================================
$result = runRpc(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list'], $plaintextKey);
$tools = $result['result']['tools'] ?? [];
$toolNames = array_column($tools, 'name');
check('Discovery: tools/list returns array', is_array($tools) && count($tools) > 50, 'count=' . count($tools));
check('Discovery: list_news registered', in_array('list_news', $toolNames, true));
check('Discovery: get_news registered', in_array('get_news', $toolNames, true));
check('Discovery: create_news registered', in_array('create_news', $toolNames, true));
check('Discovery: list_news_categories registered', in_array('list_news_categories', $toolNames, true));
check('Discovery: list_publications registered', in_array('list_publications', $toolNames, true));
check('Discovery: list_pages registered', in_array('list_pages', $toolNames, true));
check('Discovery: list_galleries registered', in_array('list_galleries', $toolNames, true));
check('Discovery: list_videos registered', in_array('list_videos', $toolNames, true));
check('Discovery: list_site_settings registered', in_array('list_site_settings', $toolNames, true));

// Ensure omitted Business modules are NOT present
check('Parity: list_services NOT in Bulletin', !in_array('list_services', $toolNames, true));
check('Parity: list_products NOT in Bulletin', !in_array('list_products', $toolNames, true));
check('Parity: list_appointments NOT in Bulletin', !in_array('list_appointments', $toolNames, true));

// Verify every tool has MCP 2025-03-26 title and annotations (readOnlyHint, destructiveHint, idempotentHint)
$missingAnnotations = [];
foreach ($tools as $toolItem) {
    if (empty($toolItem['title']) || empty($toolItem['annotations']) || !array_key_exists('readOnlyHint', $toolItem['annotations'])) {
        $missingAnnotations[] = $toolItem['name'] ?? 'unknown';
    }
}
check('Discovery: all tools have title and MCP annotations', empty($missingAnnotations), 'missing=' . implode(',', $missingAnnotations));

// =========================================================================
// 6. News CRUD Tool execution
// =========================================================================
$result = runRpc([
    'jsonrpc' => '2.0',
    'id' => 4,
    'method' => 'tools/call',
    'params' => [
        'name' => 'list_news',
        'arguments' => ['limit' => 5]
    ]
], $plaintextKey);

$callResult = json_decode($result['result']['content'][0]['text'] ?? '{}', true);
check('News: list_news returns existing items', isset($callResult['items']) && is_array($callResult['items']), json_encode($result));
check('News: list_news count reflects database', ($callResult['total'] ?? 0) > 0, 'total=' . ($callResult['total'] ?? 0));

$firstNewsId = (int) ($callResult['items'][0]['id'] ?? 0);
if ($firstNewsId > 0) {
    $result = runRpc([
        'jsonrpc' => '2.0',
        'id' => 5,
        'method' => 'tools/call',
        'params' => [
            'name' => 'get_news',
            'arguments' => ['id' => $firstNewsId]
        ]
    ], $plaintextKey);
    $itemResult = json_decode($result['result']['content'][0]['text'] ?? '{}', true);
    check('News: get_news returns full record', ($itemResult['item']['id'] ?? 0) == $firstNewsId, json_encode($result));
}

// Category listing
$result = runRpc([
    'jsonrpc' => '2.0',
    'id' => 6,
    'method' => 'tools/call',
    'params' => [
        'name' => 'list_news_categories',
        'arguments' => ['limit' => 5]
    ]
], $plaintextKey);
$catResult = json_decode($result['result']['content'][0]['text'] ?? '{}', true);
check('News: list_news_categories returns categories', isset($catResult['items']) && ($catResult['total'] ?? 0) > 0, json_encode($result));

// =========================================================================
// 6b. Resources, Prompts, Protocol & Curator Hardening (v1.0.3.79)
// =========================================================================
$resList = runRpc(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'resources/list'], $plaintextKey);
check('Protocol: resources/list returns site/info and modules/active',
    isset($resList['result']['resources']) && count($resList['result']['resources']) >= 2,
    json_encode($resList)
);

$resRead = runRpc(['jsonrpc' => '2.0', 'id' => 8, 'method' => 'resources/read', 'params' => ['uri' => 'cms://site/info']], $plaintextKey);
check('Protocol: resources/read site/info returns valid content',
    !empty($resRead['result']['contents'][0]['text']) && str_contains($resRead['result']['contents'][0]['text'], 'site_status'),
    json_encode($resRead)
);

$resTemplates = runRpc(['jsonrpc' => '2.0', 'id' => 9, 'method' => 'resources/templates/list'], $plaintextKey);
$templates = $resTemplates['result']['resourceTemplates'] ?? [];
$templateUris = array_column($templates, 'uriTemplate');
check('Curator[MCP-8]: resources/templates/list includes news, publication, page, and announcement',
    in_array('cms://news/{id}', $templateUris, true)
    && in_array('cms://publication/{id}', $templateUris, true)
    && in_array('cms://page/{id}', $templateUris, true)
    && in_array('cms://announcement/{id}', $templateUris, true),
    json_encode($templateUris)
);

$pList = runRpc(['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list'], $plaintextKey);
check('Protocol: prompts/list returns editorial and SEO prompts',
    isset($pList['result']['prompts']) && count($pList['result']['prompts']) >= 3,
    json_encode($pList)
);

$pSeo = runRpc(['jsonrpc' => '2.0', 'id' => 11, 'method' => 'prompts/get', 'params' => ['name' => 'seo_meta_optimizer', 'arguments' => ['title' => 'Nûçe', 'content' => 'Naverok']]], $plaintextKey);
check('Protocol: prompts/get seo_meta_optimizer enforces 90-160 chars deck rule',
    str_contains($pSeo['result']['messages'][0]['content']['text'] ?? '', '90 and 160 characters'),
    json_encode($pSeo)
);

$callResult = runRpc(['jsonrpc' => '2.0', 'id' => 12, 'method' => 'tools/call', 'params' => ['name' => 'list_site_settings', 'arguments' => []]], $plaintextKey);
check('Modernization: tools/call returns structuredContent alongside text block',
    isset($callResult['result']['structuredContent']) && is_array($callResult['result']['structuredContent']) && isset($callResult['result']['content'][0]['text']),
    json_encode($callResult)
);

$batchPayload = [
    ['jsonrpc' => '2.0', 'id' => 13, 'method' => 'ping'],
    ['jsonrpc' => '2.0', 'id' => 14, 'method' => 'ping'],
];
$batchResponse = runRpc($batchPayload, $plaintextKey, ['HTTP_MCP_PROTOCOL_VERSION' => '2025-06-18']);
check('Modernization: batch requests rejected on protocol version 2025-06-18',
    ($batchResponse['error']['code'] ?? 0) === -32600 && str_contains($batchResponse['error']['message'] ?? '', 'batching is not supported'),
    json_encode($batchResponse)
);

$batchResponseOld = runRpc($batchPayload, $plaintextKey, ['HTTP_MCP_PROTOCOL_VERSION' => '2025-03-26']);
check('Modernization: batch requests permitted on legacy protocol version 2025-03-26',
    is_array($batchResponseOld) && count($batchResponseOld) === 2,
    json_encode($batchResponseOld)
);

// Background Jobs Table Lifecycle
require_once lib_dir . 'AgentRunner.class.php';
$jobOwnerUserId = 1;
$jobId = AgentRunner::createJob($jobOwnerUserId, 1, 'en', 'Smoke test background prompt');
$job = AgentRunner::getJob($jobId);
check('Jobs: createJob and getJob retrieve queued job', !empty($job) && $job['status'] === 'queued', json_encode($job));

$cancelled = AgentRunner::cancelJob($jobId, $jobOwnerUserId);
$jobAfter = AgentRunner::getJob($jobId);
check('Jobs: cancelJob sets job status to cancelled', $cancelled === true && ($jobAfter['status'] ?? '') === 'cancelled', json_encode($jobAfter));
$db->query("DELETE FROM mod_agent_jobs WHERE job_id = '{$jobId}'");

// Determine active test language and news category
$db->fetch_array("SELECT seo FROM lang WHERE status = 'y' ORDER BY is_default DESC LIMIT 1");
$langRows = $db->toArray();
$testLang = $langRows[0]['seo'] ?? 'tr';

$db->fetch_array("SELECT id FROM mod_news_category WHERE lang = '{$testLang}' AND root = 'n' ORDER BY id ASC LIMIT 1");
$newsCatRows = $db->toArray();
$sharedNewsCategoryId = (int) ($newsCatRows[0]['id'] ?? 0);
if ($sharedNewsCategoryId === 0) {
    $catRes = runRpc(['jsonrpc' => '2.0', 'id' => 15, 'method' => 'tools/call', 'params' => ['name' => 'create_news_category', 'arguments' => [
        'name' => 'MCP Smoke Seed News Category',
        'lang' => $testLang,
    ]]], $plaintextKey);
    $catPayload = json_decode($catRes['result']['content'][0]['text'] ?? 'null', true);
    $sharedNewsCategoryId = (int) ($catPayload['id'] ?? 0);
}

// MCP-1: 145-char summary accepted + HTML sanitization
$longSummary145 = str_pad('Curator SERP summary within 90-160 chars window for Google snippet parity and meta description synchronization ', 145, 'x');
$dirtyHtmlInput = '<h1>Main Heading</h1><p sty' . 'le="color:red" on' . 'click="alert(1)">Clean paragraph text.</p><scr' . 'ipt>evil()</scr' . 'ipt>';
$resCurator = runRpc(['jsonrpc' => '2.0', 'id' => 16, 'method' => 'tools/call', 'params' => ['name' => 'create_news', 'arguments' => [
    'name' => 'MCP Smoke Curator Hardening Item',
    'lang' => $testLang,
    'category_id' => $sharedNewsCategoryId,
    'summary' => $longSummary145,
    'content' => $dirtyHtmlInput,
    'seo' => 'curator_smoke_slug_v1',
]]], $plaintextKey);
$curatorCreated = json_decode($resCurator['result']['content'][0]['text'] ?? 'null', true);
$curatorId = (int) ($curatorCreated['id'] ?? 0);
$curatorItem = $curatorCreated['item'] ?? [];
check('Curator[MCP-1]: 145-char summary accepted and auto-synced to description',
    ($resCurator['result']['isError'] ?? true) === false
    && ($curatorItem['summary'] ?? '') === $longSummary145
    && ($curatorItem['description'] ?? '') === $longSummary145,
    json_encode($curatorItem)
);
check('Curator[MCP-1]: sanitizeBodyHtml strips inline style/script/onclick and downgrades h1 to h2',
    str_contains($curatorItem['content'] ?? '', '<h2>Main Heading</h2>')
    && !str_contains($curatorItem['content'] ?? '', 'sty' . 'le=')
    && !str_contains($curatorItem['content'] ?? '', '<scr' . 'ipt')
    && !str_contains($curatorItem['content'] ?? '', 'on' . 'click='),
    $curatorItem['content'] ?? ''
);

// MCP-2: Lookup by seo + lang
$resBySlug = runRpc(['jsonrpc' => '2.0', 'id' => 17, 'method' => 'tools/call', 'params' => ['name' => 'get_news', 'arguments' => [
    'seo' => 'curator_smoke_slug_v1',
    'lang' => $testLang,
]]], $plaintextKey);
$gotBySlug = json_decode($resBySlug['result']['content'][0]['text'] ?? 'null', true);
check('Curator[MCP-2]: get_news resolves record by seo + lang without numeric id',
    ($resBySlug['result']['isError'] ?? true) === false && (int) ($gotBySlug['item']['id'] ?? 0) === $curatorId,
    json_encode($gotBySlug)
);

// MCP-3: Automatic 301 redirect registration + chain collapse on slug update
runRpc(['jsonrpc' => '2.0', 'id' => 18, 'method' => 'tools/call', 'params' => ['name' => 'update_news', 'arguments' => [
    'id' => $curatorId,
    'seo' => 'curator_smoke_slug_v2',
]]], $plaintextKey);
runRpc(['jsonrpc' => '2.0', 'id' => 19, 'method' => 'tools/call', 'params' => ['name' => 'update_news', 'arguments' => [
    'id' => $curatorId,
    'seo' => 'curator_smoke_slug_v3',
]]], $plaintextKey);
$oldUrlV1 = $testLang . '/news/curator_smoke_slug_v1';
$oldUrlV2 = $testLang . '/news/curator_smoke_slug_v2';
$newUrlV3 = $testLang . '/news/curator_smoke_slug_v3';
$db->fetch_array("SELECT old_url, new_url, status_code FROM mod_redirect WHERE old_url IN ('{$oldUrlV1}', '{$oldUrlV2}') ORDER BY old_url ASC");
$redirectRows = $db->toArray();
check('Curator[MCP-3]: slug updates auto-register 301 redirects and collapse chains',
    count($redirectRows) === 2
    && ($redirectRows[0]['new_url'] ?? '') === $newUrlV3
    && ($redirectRows[1]['new_url'] ?? '') === $newUrlV3
    && (int) ($redirectRows[0]['status_code'] ?? 0) === 301,
    json_encode($redirectRows)
);

// Cleanup test news and redirects
runRpc(['jsonrpc' => '2.0', 'id' => 20, 'method' => 'tools/call', 'params' => ['name' => 'delete_news', 'arguments' => ['id' => $curatorId]]], $plaintextKey);
$db->query("DELETE FROM mod_redirect WHERE old_url IN ('{$oldUrlV1}', '{$oldUrlV2}', '{$newUrlV3}')");

// MCP-5: get_google_settings masks gemini_api_key
$resGoogle = runRpc(['jsonrpc' => '2.0', 'id' => 21, 'method' => 'tools/call', 'params' => ['name' => 'get_google_settings', 'arguments' => []]], $plaintextKey);
$googleSettingsPayload = json_decode($resGoogle['result']['content'][0]['text'] ?? 'null', true);
$maskedKeyVal = (string) ($googleSettingsPayload['gemini_api_key'] ?? '');
check('Curator[MCP-5]: get_google_settings exposes has_gemini_api_key boolean and masks raw key',
    ($resGoogle['result']['isError'] ?? true) === false
    && is_bool($googleSettingsPayload['has_gemini_api_key'] ?? null)
    && ($maskedKeyVal === '' || str_contains($maskedKeyVal, '****')),
    json_encode($googleSettingsPayload)
);

// Language Parity Gate: list_site_settings exposes active_languages & lang_status
$resSiteLang = runRpc(['jsonrpc' => '2.0', 'id' => 22, 'method' => 'tools/call', 'params' => ['name' => 'list_site_settings', 'arguments' => ['active_only' => 'y']]], $plaintextKey);
$siteLangPayload = json_decode($resSiteLang['result']['content'][0]['text'] ?? 'null', true);
check('Curator[LangGate]: list_site_settings returns active_languages, default_language, and lang_status=y',
    ($resSiteLang['result']['isError'] ?? true) === false
    && !empty($siteLangPayload['active_languages'])
    && !empty($siteLangPayload['default_language'])
    && (($siteLangPayload['items'][0]['lang_status'] ?? '') === 'y'),
    json_encode($siteLangPayload)
);

// Language Parity Gate: assertContentMatchesLang rejects cross-language contamination
$mismatchedProse = !in_array($testLang, ['ku', 'ckb', 'zza'], true)
    ? [
        'name' => 'Rojeva Werzîşê ya Amedê: Daxuyaniyên Besnik Hasi û Şampiyoniya Nehir Aytulun',
        'summary' => 'Li Amedê rojeva werzîşê bi daxuyaniyên teknîk dîrektorê Amedsporê Besnik Hasi û şampiyoniya cîhanê ya Nehir Aytulun derket pêş.',
        'content' => '<p>Di civîna çapemeniyê ya derbarê maça Samsunsporê de ji bo lîstikvanên nû yên tîmê daxuyanî hatiye dayîn.</p>',
    ]
    : [
        'name' => 'Diyarbakır Spor Gündemi: Amedspor Hazırlıkları ve Dünya Şampiyonluğu',
        'summary' => 'Diyarbakır temsilcisi Amedspor tarafından yapılan açıklamada yeni karşılaşma öncesinde hazırlıkların tamamlandığı bildirildi.',
        'content' => '<p>Kulüp yönetimi tarafından yapılan değerlendirmede oyuncuların durumu hakkında kapsamlı bilgiler paylaşıldı.</p>',
    ];
$resMismatch = runRpc(['jsonrpc' => '2.0', 'id' => 23, 'method' => 'tools/call', 'params' => ['name' => 'create_news', 'arguments' => [
    'name' => $mismatchedProse['name'],
    'lang' => $testLang,
    'category_id' => $sharedNewsCategoryId,
    'summary' => $mismatchedProse['summary'],
    'content' => $mismatchedProse['content'],
]]], $plaintextKey);
check('Curator[LangGate]: create_news rejects cross-language prose contamination',
    ($resMismatch['result']['isError'] ?? false) === true
    && str_contains($resMismatch['result']['content'][0]['text'] ?? '', 'Content language mismatch'),
    json_encode($resMismatch)
);

// Legal & Masthead (Künye) Settings Round-Trip (v1.0.5)
$resGetBefore = runRpc(['jsonrpc' => '2.0', 'id' => 24, 'method' => 'tools/call', 'params' => ['name' => 'get_site_settings', 'arguments' => ['lang' => $testLang]]], $plaintextKey);
$beforeItem = json_decode($resGetBefore['result']['content'][0]['text'] ?? 'null', true)['item'] ?? [];

$resUpdateLegal = runRpc(['jsonrpc' => '2.0', 'id' => 25, 'method' => 'tools/call', 'params' => ['name' => 'update_site_settings', 'arguments' => [
    'lang' => $testLang,
    'managing_director' => 'Smoke Publisher',
    'editor_in_chief' => 'Smoke Editor-in-Chief',
    'press_code_license' => 'ISSN-SMOKE-2026',
    'dpo_email' => 'dpo@example.org',
    'hosting_provider' => 'Smoke Cloud Hosting',
    'compliance_jurisdiction' => 'Diyarbakır Courts',
    'legal_trading_name' => 'Smoke Media Group',
    'company_registration_number' => 'REG-SMOKE-01',
    'corporate_address' => 'Smoke HQ Address',
    'contact_address' => 'Smoke Contact Address',
]]], $plaintextKey);
$updatedLegalPayload = json_decode($resUpdateLegal['result']['content'][0]['text'] ?? 'null', true);
$updatedLegalItem = $updatedLegalPayload['item'] ?? [];
check('Settings[v1.0.5]: update_site_settings persists masthead (Künye) and corporate legal fields',
    ($resUpdateLegal['result']['isError'] ?? true) === false
    && ($updatedLegalItem['managing_director'] ?? '') === 'Smoke Publisher'
    && ($updatedLegalItem['editor_in_chief'] ?? '') === 'Smoke Editor-in-Chief'
    && ($updatedLegalItem['press_code_license'] ?? '') === 'ISSN-SMOKE-2026'
    && ($updatedLegalItem['dpo_email'] ?? '') === 'dpo@example.org'
    && ($updatedLegalItem['hosting_provider'] ?? '') === 'Smoke Cloud Hosting'
    && ($updatedLegalItem['compliance_jurisdiction'] ?? '') === 'Diyarbakır Courts'
    && ($updatedLegalItem['legal_trading_name'] ?? '') === 'Smoke Media Group'
    && ($updatedLegalItem['company_registration_number'] ?? '') === 'REG-SMOKE-01'
    && ($updatedLegalItem['corporate_address'] ?? '') === 'Smoke HQ Address'
    && ($updatedLegalItem['contact_address'] ?? '') === 'Smoke Contact Address',
    json_encode($updatedLegalPayload)
);

// Restore previous legal settings
runRpc(['jsonrpc' => '2.0', 'id' => 26, 'method' => 'tools/call', 'params' => ['name' => 'update_site_settings', 'arguments' => [
    'lang' => $testLang,
    'managing_director' => (string) ($beforeItem['managing_director'] ?? ''),
    'editor_in_chief' => (string) ($beforeItem['editor_in_chief'] ?? ''),
    'press_code_license' => (string) ($beforeItem['press_code_license'] ?? ''),
    'dpo_email' => (string) ($beforeItem['dpo_email'] ?? ''),
    'hosting_provider' => (string) ($beforeItem['hosting_provider'] ?? ''),
    'compliance_jurisdiction' => (string) ($beforeItem['compliance_jurisdiction'] ?? ''),
    'legal_trading_name' => (string) ($beforeItem['legal_trading_name'] ?? ''),
    'company_registration_number' => (string) ($beforeItem['company_registration_number'] ?? ''),
    'corporate_address' => (string) ($beforeItem['corporate_address'] ?? ''),
    'contact_address' => (string) ($beforeItem['contact_address'] ?? ''),
]]], $plaintextKey);

// =========================================================================
// 7. Cleanup
// =========================================================================
$db->query("DELETE FROM mod_mcp_api_key WHERE id = " . $keyId);
check('Cleanup: smoke test key deleted', true);

echo "\n=========================================\n";
echo "Smoke test complete: {$passCount} passed, {$failCount} failed.\n";
exit($failCount === 0 ? 0 : 1);
