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

/*
 * Safety net: the framework Database layer renders an HTML exception page and
 * calls exit() on SQL errors, bypassing normal exception flow. Buffer all
 * output; at shutdown, replace anything that is not a valid MCP response with
 * a clean JSON-RPC internal error.
 */
ob_start();
register_shutdown_function(static function (): void {
    $buffer = (string) ob_get_clean();

    if ($buffer === '') {
        // Notification response (HTTP 202, empty body): nothing to validate
        return;
    }

    $decoded = json_decode($buffer, true);
    if (is_array($decoded) && (isset($decoded['jsonrpc']) || isset($decoded[0]['jsonrpc']))) {
        echo $buffer;
        return;
    }

    // Framework-level fatal (e.g. DatabaseException HTML page): emit a valid MCP error
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => null,
        'error' => [
            'code' => -32603,
            'message' => 'Internal error.',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

try {
    /** @var McpServer $mcpServer */
    $mcpServer = Load::lib('McpServer');
    $mcpServer->handle();
} catch (\Throwable $e) {
    error_log('MCP endpoint error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ob_get_clean();
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => null,
        'error' => [
            'code' => -32603,
            'message' => 'Internal error.',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
