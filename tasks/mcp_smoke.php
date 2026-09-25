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
check('Handshake: serverInfo name is amed-rojname-mcp', ($result['result']['serverInfo']['name'] ?? '') === 'amed-rojname-mcp', json_encode($result));

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
check('Parity: list_services NOT in Rojname', !in_array('list_services', $toolNames, true));
check('Parity: list_products NOT in Rojname', !in_array('list_products', $toolNames, true));
check('Parity: list_appointments NOT in Rojname', !in_array('list_appointments', $toolNames, true));

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
// 7. Cleanup
// =========================================================================
$db->query("DELETE FROM mod_mcp_api_key WHERE id = " . $keyId);
check('Cleanup: smoke test key deleted', true);

echo "\n=========================================\n";
echo "Smoke test complete: {$passCount} passed, {$failCount} failed.\n";
exit($failCount === 0 ? 0 : 1);
