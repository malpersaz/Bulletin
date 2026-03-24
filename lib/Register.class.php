<?php
/**
 * Rojname İçerik Yönetim Sistemi
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Kullanıcı: a.k.mutlu
 * Zaman: 11.04.2011 - 20:38
 * Sınıf İsmi: Register
 */
class Register
{

    static private $data = [];

    static public function get($key)
    {
        return (isset(self::$data[$key]) ? self::$data[$key] : NULL);
    }

    static public function set($key, $value)
    {
        self::$data[$key] = $value;
    }

    static public function exists($key)
    {
        return isset(self::$data[$key]);
    }

    static public function delete($key)
    {
        unset(self::$data[$key]);
    }

    static public function renew($key, $value)
    {
        if (self::exists($key)) {
            self::delete($key);
        }
        self::set($key, $value);
    }

    static public function all()
    {
        require_once __DIR__ . '/dBug.php';
        new dBug(self::$data);
    }


}
