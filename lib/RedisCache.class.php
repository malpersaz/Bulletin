<?php
/**
 * Amed Framework
 * User: Antigravity Developer
 * Date: 7/15/26
 * Description: Redis client wrapper for caching and session storage
 */

class RedisCache {
    private static $client = null;
    private static $enabled = null;

    private static function init() {
        if (self::$client !== null) {
            return;
        }

        $config = Registry::get('google_config') ?? [];
        $redisSettings = $config['redis'] ?? [];

        $host = $redisSettings['host'] ?? Registry::get('redis_host') ?: 'redis';
        $port = $redisSettings['port'] ?? Registry::get('redis_port') ?: 6379;
        self::$enabled = $redisSettings['enabled'] ?? Registry::get('redis_enabled') ?? false;

        if (self::$enabled) {
            try {
                if (class_exists('\Predis\Client')) {
                    self::$client = new \Predis\Client([
                        'scheme' => 'tcp',
                        'host'   => $host,
                        'port'   => $port,
                        'timeout' => 1.5,
                    ]);
                    self::$client->connect();
                } else {
                    self::$enabled = false;
                }
            } catch (\Exception $e) {
                self::$client = null;
                self::$enabled = false;
            }
        }
    }

    public static function isEnabled() {
        self::init();
        return self::$enabled;
    }

    public static function set($key, $value, $ttl = null) {
        self::init();
        if (!self::$enabled) {
            return false;
        }
        try {
            $serialized = serialize($value);
            if ($ttl !== null && $ttl > 0) {
                self::$client->setex($key, $ttl, $serialized);
            } else {
                self::$client->set($key, $serialized);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function get($key) {
        self::init();
        if (!self::$enabled) {
            return false;
        }
        try {
            $value = self::$client->get($key);
            if ($value === null) {
                return false;
            }
            return unserialize($value);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function delete($key) {
        self::init();
        if (!self::$enabled) {
            return false;
        }
        try {
            self::$client->del($key);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function exists($key) {
        self::init();
        if (!self::$enabled) {
            return false;
        }
        try {
            return (bool) self::$client->exists($key);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function flush() {
        self::init();
        if (!self::$enabled) {
            return false;
        }
        try {
            self::$client->flushdb();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
