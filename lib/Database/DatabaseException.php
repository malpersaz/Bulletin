<?php
/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 08.04.2011 - 18:57
 * Class Name: DatabaseException
 */
class DatabaseException extends Exception
{
    public $sql;
    public $_error;
    public function __construct($message, $sql = null, $mysql_error = null)
    {
        $this->message = $message;
        if ($sql != null) {
            $this->sql = $sql;
        }
        if ($mysql_error != null) {
            $this->_error = $mysql_error;
        }
    }

    public function show()
    {
        $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
        // Check for AJAX request
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax_request']) && $_POST['ajax_request'] === '1')
        );
        
        // Log error message
        $logFile = (defined('root_dir') ? root_dir : '') . 'logs/ajax_errors.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] DatabaseException: ' . $this->message . "\n";
        if (isset($this->sql)) {
            $logMessage .= '  SQL: ' . $this->sql . "\n";
        }
        if (isset($this->_error)) {
            $logMessage .= '  DB Error: ' . $this->_error . "\n";
        }
        $logMessage .= '  File: ' . $this->getFile() . ':' . $this->getLine() . "\n";
        $logMessage .= '  SAPI: ' . ($isCli ? 'cli' : 'web') . "\n";
        $logMessage .= '  URL: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
        $logMessage .= "---\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        if ($isCli) {
            fwrite(STDERR, "DatabaseException: " . $this->message . "\n");
            return;
        }

        // Connection problems (fatal DB connection failure) require 503 response
        $isConnectionFailure = (strpos($this->message, 'Connection Problem!') !== false);

        if ($isConnectionFailure) {
            // Clean any previously started output buffers before rendering error response
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            if (!headers_sent()) {
                http_response_code(503);
                header('Retry-After: 3600');
                header('Cache-Control: no-store, no-cache, must-revalidate');
            }
            
            // If request is AJAX, return JSON response
            if ($isAjax) {
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Database Error: ' . $this->message,
                    'debug' => [
                        'sql' => isset($this->sql) ? $this->sql : null,
                        'db_error' => isset($this->_error) ? $this->_error : null,
                        'file' => basename($this->getFile()),
                        'line' => $this->getLine()
                    ]
                ]);
                exit;
            }

            $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'], true)
                || (defined('ENV') && ENV === 'development');

            if (!$isLocal) {
                echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>503 Service Unavailable</title></head><body><main><h1>503 Service Unavailable</h1><p>The database is temporarily unavailable. Please try again shortly.</p></main></body></html>';
                exit;
            }
        }
        
        // For non-fatal SQL query errors or local development inspection:
        if ($isAjax) {
            return;
        }

        $isLocalEnv = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'], true)
            || (defined('ENV') && ENV === 'development')
            || (defined('DEBUG') && DEBUG);

        if (!$isLocalEnv) {
            // In production, do not echo raw stack traces into HTML for non-fatal query errors
            return;
        }

        // If it's a fatal connection failure on local, show details and exit
        if ($isConnectionFailure) {
            echo "<div class=\"dexception\">";
            echo "<h2>Database Connection Problem</h2>";
            echo "<p>" . htmlspecialchars($this->message) . "</p>";
            echo "<pre>" . htmlspecialchars($this->getTraceAsString()) . "</pre>";
            echo "</div>";
            exit;
        }

        // For non-fatal SQL query errors: log only, do not halt execution or output raw HTML
        return;
    }
}
