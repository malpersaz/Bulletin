<?php
/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 08.04.2011 - 18:57
 * Sınıf İsmi: DatabaseException
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
        // AJAX daxwaznameyê kontrol bike
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax_request']) && $_POST['ajax_request'] === '1')
        );
        
        // Log şaşiyê
        $logFile = (defined('root_dir') ? root_dir : '') . 'logs/ajax_errors.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] DatabaseException: ' . $this->message . "\n";
        if (isset($this->sql)) {
            $logMessage .= '  SQL: ' . $this->sql . "\n";
        }
        if (isset($this->_error)) {
            $logMessage .= '  DB Error: ' . $this->_error . "\n";
        }
        $logMessage .= '  File: ' . $this->getFile() . ':' . $this->getLine() . "\n";
        $logMessage .= '  URL: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
        $logMessage .= "---\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Heke AJAX be, JSON vegere
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
            return;
        }
        
        // Non-AJAX: HTML output (awayê berê)
        $f = $this->getTrace();
        $last = end($f);
        unset($f);
        $error = "
          <style>
            .dexception {
                background: #FFFFCC;
                font-size: 14px;
                color: #000000;
            }

            .dexception h4 {
                padding: 8px;
                background: #eee;
                font: 'Courier New', Courier, mono;
                font-weight: bold;
            }
            .dexception h3{
                margin-bottom:2px;
            }
            .e {
                font-size: 12px;
                padding: 2px 8px;

                font: 'Courier New', Courier, mono;
            }

            .e_red {
                color: red;
                font-weight: bold;
            }
        </style>";
        $error .= "<div class='dexception'>";
        $error .= '<div class="e"><pre>';
        $error .= "<h1>DatabaseException</h1>";
        $error .= "<h4>" . $this->message . "</h4>";
        if (isset($this->sql)) {
            $error .= "<h3>SQL String</h3>\n";
            $error .= "<b class='e_red'>" . $this->sql . "</b>\n";
        }
        if (isset($this->_error)) {
            $error .= "<h3>Database Says</h3>\n";
            $error .= "<b class='e_red'>" . $this->_error . "</b>\n";
        }
        $error .= "<h3>Error</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $this->getFile() . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $this->getLine() . "\n";
        $error .= "<h3>Calling Files</h3>\n";
        if (isset($last['file'])) {
            $error .= "<b class='e_red'>File:</b>" . $last['file'] . "\n";
        }
        if (isset($last['line'])) {
            $error .= "<b class='e_red'>Line:</b>" . $last['line'] . "\n";
        }
        $error .= "<b class='e_red'>Class:</b>" . $last['class'] . "\n";
        $error .= "<b class='e_red'>Function:</b>" . $last['function'] . "\n";
        $error .= "<b class='e_red'>Args:</b>" . print_r(isset($last['args']) ? $last['args'] : [], true) . "\n";
        $error .= "<h3>Trac String</h3>\n";
        $error .= $this->getTraceAsString() . "\n";
        $error .= "</pre></div>";

        $error .= "</div>";


        $error .= "<hr>";
        $error .= "<div class='dexception'>";
        $error .= '<div class="e"><pre>';
        $error .= "<h1>DatabaseException</h1>";
        $error .= "<h4>" . $this->message . "</h4>";
        if (isset($this->sql)) {
            $error .= "<h3>SQL Query</h3>\n";
            $error .= "<b class='e_red'>" . $this->sql . "</b>\n";
        }
        if (isset($this->_error)) {
            $error .= "<h3>Database Error Message</h3>\n";
            $error .= "<b class='e_red'>" . $this->_error . "</b>\n";
        }
        $error .= "<h3>Error Line and File</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $this->getFile() . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $this->getLine() . "\n";
        $error .= "<h3>Error Trigerring Use</h3>\n";
        if (isset($last['file'])) {
            $error .= "<b class='e_red'>File:</b>" . $last['file'] . "\n";
        }
        if (isset($last['line'])) {
            $error .= "<b class='e_red'>Line:</b>" . $last['line'] . "\n";
        }
        $error .= "<b class='e_red'>Class:</b>" . $last['class'] . "\n";
        $error .= "<b class='e_red'>Function:</b>" . $last['function'] . "\n";
        $error .= "<b class='e_red'>Args:</b>" . print_r(isset($last['args']) ? $last['args'] : [], true) . "\n";
        $error .= "<h3>Trac String</h3>\n";
        $error .= $this->getTraceAsString() . "\n";
        $error .= "</pre></div>";

        $error .= "</div>";

        echo $error;
    }
}
