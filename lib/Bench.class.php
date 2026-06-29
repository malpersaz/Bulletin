<?php
/**
 * AKM Business İçerik Yönetim Sistemi
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Kullanıcı: a.k.mutlu
 * Zaman: 31.03.2011 - 13:28
 * Sınıf İsmi: Bench
 */
final class Bench
{
    private static $tests;


    public static function timer()
    {
        return array_sum(explode(" ", microtime()));
    }

    public static function start($name)
    {
        if (isset(self::$tests[$name]) === False) {
            self::$tests[$name] = [
                'stime' => self::timer(),
                'smemory' => memory_get_usage(true)
            ];
        }
    }

    public static function stop($name)
    {
        if (isset(self::$tests[$name]) === False) {
            throw new Exception($name . " Test Not Found");
        }
        self::$tests[$name]['etime'] = self::timer();
        self::$tests[$name]['ememory'] = memory_get_usage(true);
        self::$tests[$name]['time'] = self::$tests[$name]['etime'] - self::$tests[$name]['stime'];
        self::$tests[$name]['memory'] = self::echo_memory_usage(self::$tests[$name]['ememory'] - self::$tests[$name]['smemory']);


    }

    public static function get($name)
    {
        if (isset(self::$tests[$name]) === False) {
            throw new Exception($name . " Test Not Found");
        }
        return self::$tests[$name];
    }

    public static function echo_memory_usage($mem_usage)
    {
        if ($mem_usage < 1024) {
            return $mem_usage." bytes";
        }
        if ($mem_usage < 1048576) {
            return round($mem_usage/1024,2)." kilobytes";
        }
        return round($mem_usage/1048576,2)." megabytes";
    }
}
