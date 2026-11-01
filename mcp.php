<?php

/**
 * mcp.php - Model Context Protocol (MCP) endpoint
 *
 * Exposes CMS content CRUD tools over the MCP Streamable HTTP transport.
 * Clients authenticate with an API key created in the admin panel:
 *
 *   Authorization: Bearer amcp_...
 *
 * Amed Framework
 */

require_once('vendor/autoload.php');
require_once('init.php');

// Language registry defaults (lang selection is an admin/public concern, not MCP)
if (!Registry::has('lang')) {
    Registry::add('lang', 'en');
}

// Machine-facing endpoint: warnings must never corrupt the JSON-RPC stream
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Pre-parse request input to extract JSON-RPC request ID and notification status
$rawInput = (string) file_get_contents('php://input');
$decodedInput = json_decode($rawInput, true);

$isNotification = false;
$reqId = null;
if (is_array($decodedInput)) {
    if (array_key_exists('id', $decodedInput)) {
        $reqId = (is_int($decodedInput['id']) || is_string($decodedInput['id'])) ? $decodedInput['id'] : null;
    } elseif (isset($decodedInput['method'])) {
        $isNotification = true;
    }
}

$site = Registry::has('site_config') ? (array) Registry::get('site_config') : [];
$isLocal = !empty($isLocal);

/*
 * Safety net: the framework Database layer renders an HTML exception page and
 * calls exit() on SQL errors, bypassing normal exception flow. Buffer all
 * output; at shutdown, replace anything that is not a valid MCP response with
 * a clean JSON-RPC internal error.
 */
ob_start();
register_shutdown_function(static function () use ($reqId, $isNotification, $isLocal, $site): void {
    $buffer = (string) ob_get_clean();

    $serverName = class_exists('McpServer') ? McpServer::resolveServerName() : ($site['mcp_server_name'] ?? 'amed-mcp');
    $resource = class_exists('McpServer') ? McpServer::resolveCanonicalResource() : rtrim((string) ($site['address'] ?? ''), '/');

    if ($buffer === '') {
        // Notification response (HTTP 202, empty body): ensure identity headers are present
        if (!headers_sent()) {
            header('X-MCP-Server-Name: ' . $serverName);
            header('X-MCP-Resource: ' . $resource);
        }
        return;
    }

    $decoded = json_decode($buffer, true);
    if (is_array($decoded) && (isset($decoded['jsonrpc']) || isset($decoded[0]['jsonrpc']))) {
        echo $buffer;
        return;
    }

    if ($isNotification) {
        if (!headers_sent()) {
            http_response_code(202);
            header('X-MCP-Server-Name: ' . $serverName);
            header('X-MCP-Resource: ' . $resource);
        }
        return;
    }

    // Framework-level fatal (e.g. DatabaseException HTML page): emit a valid MCP error
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-MCP-Server-Name: ' . $serverName);
        header('X-MCP-Resource: ' . $resource);
    }
    $errorPayload = [
        'jsonrpc' => '2.0',
        'id' => $reqId,
        'error' => [
            'code' => -32603,
            'message' => 'Internal error.',
        ],
    ];
    if ($isLocal && $buffer !== '') {
        $errorPayload['error']['data'] = ['diagnostic' => strip_tags(substr($buffer, 0, 500))];
    }
    echo json_encode($errorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

try {
    /** @var McpServer $mcpServer */
    $mcpServer = Load::lib('McpServer');
    $mcpServer->handle();
} catch (\Throwable $e) {
    error_log('MCP endpoint error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ob_get_clean();

    $serverName = class_exists('McpServer') ? McpServer::resolveServerName() : ($site['mcp_server_name'] ?? 'amed-mcp');
    $resource = class_exists('McpServer') ? McpServer::resolveCanonicalResource() : rtrim((string) ($site['address'] ?? ''), '/');

    if ($isNotification) {
        if (!headers_sent()) {
            http_response_code(202);
            header('X-MCP-Server-Name: ' . $serverName);
            header('X-MCP-Resource: ' . $resource);
        }
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-MCP-Server-Name: ' . $serverName);
        header('X-MCP-Resource: ' . $resource);
    }
    $errorPayload = [
        'jsonrpc' => '2.0',
        'id' => $reqId,
        'error' => [
            'code' => -32603,
            'message' => 'Internal error.',
        ],
    ];
    if ($isLocal) {
        $errorPayload['error']['data'] = ['diagnostic' => $e->getMessage()];
    }
    echo json_encode($errorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
