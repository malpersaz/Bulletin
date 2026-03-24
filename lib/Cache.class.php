<?php

class Cache {

    private static $cachedir = 'cache/sql/';
    private static $kaynak = '';
    private static $file = '';
    public static $sure = NULL;

    public static function set($veri, $key) {
        self::$kaynak = is_object($veri) || is_array($veri) ? serialize($veri) : $veri;
        self::$file = root_dir . self::$cachedir . $key . '.sql';
        return self::save_cache();
    }

    public static function get_cache() {
        //self::$file = root_dir . self::$cachedir . $key . '.sql';
        if (self::open_cache()) {
            return unserialize(self::$kaynak);
        }
        return False;
    }

    public static function delete_cache() {
        return @unlink(self::$file);
    }

    public static function exists_cache($key) {
        self::$file = root_dir . self::$cachedir . $key . '.sql';
        if (file_exists(self::$file)) {
            if (self::$sure != NULL) {
                if (filemtime(self::$file) + self::$sure < time()) {
                    self::delete_cache();
                    return False;
                }
                return True;
            }
            return True;
        }
        return False;
    }

    public static function save_cache() {
        $dir = dirname(self::$file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ac = fopen(self::$file, "w") or die(self::$file . ' Yazılabilinir Değil');
        flock($ac, LOCK_EX);
        fwrite($ac, self::$kaynak ?? '');
        flock($ac, LOCK_UN);
        fclose($ac);
    }

    public static function open_cache() {
        if (file_exists(self::$file)) {
            // Eğer Dosya Boşsa
            if (filesize(self::$file) === 0) {
                return FALSE;
            }
            /*
              $ac = fopen ( self::$file, "r" );
              self::$kaynak = fread ( $ac, filesize ( self::$file ) );
              fclose ( $ac ); */
            self::$kaynak = file_get_contents(self::$file);
            return True;
        }
        return False;
    }

}
