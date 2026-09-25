<?php
 /**
 * AKM Business Content Management System
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 16.04.2011- 17:04
 * Class: OrmException
 */
class OrmException extends Exception
{
    public $table;
    public $mysql_error;
    public function __construct($message, $table = null, $mysql_error = null)
    {
        $this->message = $message;
        if ($table != null) {
            $this->table = $table;
        }
        if ($mysql_error != null) {
            $this->mysql_error = $mysql_error;
        }
    }

    public function show()
    {
        error_reporting(0);
        //print_r($this->getTrace());
        $a=$this->getTrace();
        $last = end($a);
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
        $error .= "<h1>OrmException</h1>";
        $error .= "<h4>" . $this->message . "</h4>";
        if (isset($this->table)) {
            $error .= "<h3>Table</h3>\n";
            $error .= "<b class='e_red'>" . $this->table . "</b>\n";
        }
        if (isset($this->mysql_error)) {
            $error .= "<h3>MYSQL Says</h3>\n";
            $error .= "<b class='e_red'>" . $this->mysql_error . "</b>\n";
        }
        $error .= "<h3>Error</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $this->getFile() . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $this->getLine() . "\n";
        $error .= "<h3>Calling Files</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $last['file'] . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $last['line'] . "\n";
        $error .= "<b class='e_red'>Class:</b>" . $last['class'] . "\n";
        $error .= "<b class='e_red'>Function:</b>" . $last['function'] . "\n";
        $error .= "<b class='e_red'>Args:</b>" . print_r($last['args'], true) . "\n";
        $error .= "<h3>Trac String</h3>\n";
        $error .= $this->getTraceAsString() . "\n";
        $error .= "</pre></div>";

        $error .= "</div>";

        echo $error;
    }
}
