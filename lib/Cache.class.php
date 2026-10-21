<?php

if (!class_exists('RedisCache', false)) {
    if (defined('lib_dir') && file_exists(lib_dir . 'RedisCache.class.php')) {
        require_once lib_dir . 'RedisCache.class.php';
    }
}

class Cache {

    private static $cachedir = 'cache/sql/';
    private static $source = '';
    private static $file = '';
    public static $ttl = NULL;
    private static $current_key = '';

    public static function getCacheDir(): string {
        return self::$cachedir;
    }

    public static function setCacheDir(string $dir): void {
        self::$cachedir = $dir;
    }

    public static function set($data, $key, $ttl = null) {
        $effective_ttl = $ttl ?? self::$ttl;
        self::$current_key = $key;
        if (RedisCache::isEnabled()) {
            return RedisCache::set($key, $data, $effective_ttl);
        }
        self::$source = is_object($data) || is_array($data) ? serialize($data) : $data;
        self::$file = root_dir . self::$cachedir . $key . '.sql';
        return self::save_cache();
    }

    public static function get($key = null) {
        if ($key !== null) {
            self::$current_key = $key;
            self::$file = root_dir . self::$cachedir . $key . '.sql';
        }
        if (RedisCache::isEnabled()) {
            return RedisCache::get(self::$current_key);
        }
        if (self::open_cache()) {
            return unserialize(self::$source, ['allowed_classes' => false]);
        }
        return false;
    }

    public static function get_cache($key = null) {
        return self::get($key);
    }

    public static function delete($key = null) {
        if ($key !== null) {
            self::$current_key = $key;
            self::$file = root_dir . self::$cachedir . $key . '.sql';
        }
        if (RedisCache::isEnabled()) {
            return RedisCache::delete(self::$current_key);
        }
        return @unlink(self::$file);
    }

    public static function delete_cache($key = null) {
        return self::delete($key);
    }

    public static function exists($key) {
        self::$current_key = $key;
        if (RedisCache::isEnabled()) {
            return RedisCache::exists($key);
        }
        self::$file = root_dir . self::$cachedir . $key . '.sql';
        if (file_exists(self::$file)) {
            $effective_ttl = self::$ttl;
            if ($effective_ttl !== null) {
                if (filemtime(self::$file) + $effective_ttl < time()) {
                    self::delete($key);
                    return false;
                }
                return true;
            }
            return true;
        }
        return false;
    }

    public static function exists_cache($key) {
        return self::exists($key);
    }

    public static function save_cache() {
        $dir = dirname(self::$file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $handle = fopen(self::$file, "w") or die(self::$file . ' is not writable');
        flock($handle, LOCK_EX);
        fwrite($handle, self::$source ?? '');
        flock($handle, LOCK_UN);
        fclose($handle);
        return true;
    }

    public static function open_cache() {
        if (file_exists(self::$file)) {
            if (filesize(self::$file) === 0) {
                return false;
            }
            self::$source = file_get_contents(self::$file);
            return true;
        }
        return false;
    }

}


