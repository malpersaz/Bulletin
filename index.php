<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/23/11 - 5:23 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:index.php
 * Açıklama: .htaccess ile rewrite'lere cevap veren ilk dosya
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

// Hata Gösterimi
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
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($isAjaxRequest, $ajax_logger) {
    if (!(error_reporting() & $errno)) return false;
    
    $ajax_logger("PHP ($errno): $errstr", $errfile, $errline);
    
    if ($isAjaxRequest) {
        // Only exit on fatal-like errors, or if we want to be strict
        // For now, let's just log and continue for non-fatal if we can
        if (in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            while (ob_get_level() > 0) ob_end_clean();
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
            }
            echo json_encode(['status' => 'error', 'message' => "System Error: $errstr"]);
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

// Karekter Seti
if ($isAjaxRequest) {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

// Memory Limit
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 30);

require_once('vendor/autoload.php');
require_once('init.php');

require_once('lib/InstallationState.php');
$installationState = new InstallationState();
$installCheck = $installationState->inspect();
if (!$installCheck['is_installed']) {
    header('Location: ' . sub_dir . 'installer/', true, 302);
    exit;
}

Load::lib('Amed');


/*Bench::stop('amed');
print_r(Bench::get('amed'));*/
