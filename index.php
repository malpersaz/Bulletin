<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/23/11 - 5:23 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: index.php
 * Description: First entry file that handles .htaccess rewrite requests
 */
/*
require_once('lib/Bench.class.php');
Bench::start('amed');*/
$isAjaxRequest = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax_request']) && ($_POST['ajax_request'] === 'true' || $_POST['ajax_request'] === '1'))
);

if (!$isAjaxRequest) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

// Direct handler for site.webmanifest with proper CORS and MIME type
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if ($reqPath === '/site.webmanifest') {
    $manifestFile = __DIR__ . '/static/mod_settings/site.webmanifest';
    if (file_exists($manifestFile)) {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: public, max-age=604800');
        readfile($manifestFile);
        exit;
    }
}

// Error display
if ($isAjaxRequest) {
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
}
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('log_errors', TRUE);

// Define a common error/exception logger
$ajax_logger = function($message, $file, $line, $trace = '') {
    $logFile = __DIR__ . '/logs/ajax_errors.log';
    if (!is_dir(__DIR__ . '/logs')) {
        @mkdir(__DIR__ . '/logs', 0777, true);
    }
    $logMessage = '[' . date('Y-m-d H:i:s') . "] Error: $message\n";
    $logMessage .= "  File: $file:$line\n";
    $logMessage .= "  URL: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
    if ($trace) {
        $logMessage .= "  Trace: $trace\n";
    }
    $logMessage .= "---\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
};

// Global Error Handler
set_error_handler(function($errorNumber, $errorMessage, $errorFile, $errorLine) use ($isAjaxRequest, $ajax_logger) {
    if (!(error_reporting() & $errorNumber)) return false;
    
    $ajax_logger("PHP ($errorNumber): $errorMessage", $errorFile, $errorLine);
    
    if ($isAjaxRequest) {
        // Only exit on fatal-like errors, or if we want to be strict
        // For now, let's just log and continue for non-fatal if we can
        if ($errorNumber === E_RECOVERABLE_ERROR) {
            while (ob_get_level() > 0) ob_end_clean();
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
            }
            echo json_encode(['status' => 'error', 'message' => "System Error: $errorMessage"]);
            exit;
        }
    }
    return false; // Continue with normal error handling
});

// Global Exception Handler
set_exception_handler(function($e) use ($isAjaxRequest, $ajax_logger) {
    $ajax_logger($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    
    if ($isAjaxRequest) {
        while (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'Xeletiya Serverê: ' . $e->getMessage(),
            'debug' => [
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'type' => get_class($e)
            ]
        ]);
        exit;
    }
    
    if (method_exists($e, 'show')) {
        $e->show();
    } else {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    exit;
});

// Character set
if ($isAjaxRequest) {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

// Memory limit
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 30);

require_once('vendor/autoload.php');
require_once('init.php');
require_once(lib_dir . 'InstallationState.php');

$installationState = new InstallationState();
$installCheck = $installationState->inspect();

if (empty($installCheck['is_installed'])) {
    $siteConfig = Registry::get('site_config');
    $installerUrl = '/installer/';

    if (is_array($siteConfig) && !empty($siteConfig['address'])) {
        $installerUrl = rtrim($siteConfig['address'], '/') . '/installer/';
    }

    if ($isAjaxRequest) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(503);
        }

        $issue = isset($installCheck['issues'][0]['code']) ? $installCheck['issues'][0]['code'] : 'installation_required';
        echo json_encode(array(
            'status' => 'error',
            'code' => 'installation_required',
            'reason' => $issue,
            'redirect' => $installerUrl,
        ));
        exit;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Location: ' . $installerUrl, true, 302);
    exit;
}

Load::lib('Amed');


/*Bench::stop('amed');
print_r(Bench::get('amed'));*/
