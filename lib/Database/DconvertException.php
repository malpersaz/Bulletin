<?php
 /**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 16.04.2011- 21:28
 * Sınıfı : DconvertException
 */
class DconvertException extends Exception
{
    public function show()
    {
        echo "<h2>DconvertException</h2>";
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
