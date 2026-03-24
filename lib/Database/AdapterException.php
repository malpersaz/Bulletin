<?php
/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 11.04.2011 - 17:37
 * Sınıf İsmi: AdapterException
 */
class AdapterException extends Exception
{
    public function show()
    {
        echo "<h2>AdapterException</h2>";
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
