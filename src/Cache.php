<?php
// src/Cache.php

class Cache {
    private static $redis = null;
    private static $cacheDir = null;
    private static $useRedis = null;

    private static function init() {
        if (self::$useRedis === null) {
            if (class_exists('Redis')) {
                try {
                    self::$redis = new Redis();
                    // Connect to local Redis with a 0.5s timeout
                    if (self::$redis->connect('127.0.0.1', 6379, 0.5)) {
                        self::$useRedis = true;
                    } else {
                        self::$useRedis = false;
                    }
                } catch (Exception $e) {
                    self::$useRedis = false;
                }
            } else {
                self::$useRedis = false;
            }

            if (!self::$useRedis) {
                self::$cacheDir = dirname(__DIR__) . '/cache/';
                if (!is_dir(self::$cacheDir)) {
                    mkdir(self::$cacheDir, 0755, true);
                }
            }
        }
    }

    /**
     * Get value from cache if it exists and has not expired
     */
    public static function get($key) {
        self::init();
        if (self::$useRedis) {
            $data = self::$redis->get($key);
            if ($data !== false) {
                return unserialize($data);
            }
            return null;
        }

        $safeKey = md5($key);
        $cacheFile = self::$cacheDir . $safeKey . '.cache';

        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            if ($data !== false) {
                $cachedObj = unserialize($data);
                if ($cachedObj && $cachedObj['expires_at'] > time()) {
                    return $cachedObj['value'];
                }
                @unlink($cacheFile);
            }
        }
        return null;
    }

    /**
     * Set value in cache with a TTL (default 5 minutes)
     */
    public static function set($key, $value, $ttl = 300) {
        self::init();
        if (self::$useRedis) {
            return self::$redis->setex($key, intval($ttl), serialize($value));
        }

        $safeKey = md5($key);
        $cacheFile = self::$cacheDir . $safeKey . '.cache';

        $cachedObj = [
            'value' => $value,
            'expires_at' => time() + intval($ttl)
        ];

        return file_put_contents($cacheFile, serialize($cachedObj)) !== false;
    }

    /**
     * Delete a cache key
     */
    public static function delete($key) {
        self::init();
        if (self::$useRedis) {
            return self::$redis->del($key) > 0;
        }

        $safeKey = md5($key);
        $cacheFile = self::$cacheDir . $safeKey . '.cache';
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        return false;
    }

    /**
     * Clear all cache files
     */
    public static function clear() {
        self::init();
        if (self::$useRedis) {
            return self::$redis->flushAll();
        }

        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
}
