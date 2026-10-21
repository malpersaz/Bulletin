<?php
 /**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 16.04.2011- 21:28
 * Class: DbuilderException
 */
class DbuilderException extends Exception
{
    public function show()
    {
        echo "<h2>DbuilderException</h2>";
        echo "<pre>";
        echo "<h3>" . $this->getMessage() . "</h3>";

        echo "<b>File:</b>" . $this->getFile();
        echo "\n";
        echo "<b>Line:</b>" . $this->getLine();
        echo "\n";
        echo "<b>Trace:</b>" . $this->getTraceAsString();
        echo "</pre>";
    }
}
