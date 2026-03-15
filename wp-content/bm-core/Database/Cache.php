<?php
namespace BM\Database;

class Cache {
    private static $use_redis = false;
    private static $redis = null;
    private static $cache_dir = null;
    
    /**
     * Инициализация
     */
    public static function init() {
        self::$cache_dir = WP_CONTENT_DIR . '/cache/bm-core/';
        
        if (!file_exists(self::$cache_dir)) {
            wp_mkdir_p(self::$cache_dir);
        }
        
        // Проверка Redis
        self::$use_redis = class_exists('Redis') && defined('WP_REDIS_HOST');
        
        if (self::$use_redis) {
            try {
                self::$redis = new \Redis();
                self::$redis->connect(WP_REDIS_HOST, WP_REDIS_PORT ?? 6379);
            } catch (\Exception $e) {
                self::$use_redis = false;
            }
        }
    }
    
    /**
     * Получить из кэша
     */
    public static function get($key) {
        $cache_key = self::key($key);
        
        // Redis
        if (self::$use_redis) {
            $data = self::$redis->get($cache_key);
            return $data ? unserialize($data) : null;
        }
        
        // WordPress Object Cache
        if (function_exists('wp_cache_get')) {
            return wp_cache_get($cache_key, BM_CACHE_GROUP);
        }
        
        // File cache
        $file = self::$cache_dir . md5($cache_key) . '.cache';
        if (file_exists($file) && filemtime($file) > (time() - BM_CACHE_TTL)) {
            return unserialize(file_get_contents($file));
        }
        
        return null;
    }
    
    /**
     * Сохранить в кэш
     */
    public static function set($key, $data, $ttl = BM_CACHE_TTL) {
        $cache_key = self::key($key);
        
        // Redis
        if (self::$use_redis) {
            return self::$redis->setex($cache_key, $ttl, serialize($data));
        }
        
        // WordPress Object Cache
        if (function_exists('wp_cache_set')) {
            return wp_cache_set($cache_key, $data, BM_CACHE_GROUP, $ttl);
        }
        
        // File cache
        $file = self::$cache_dir . md5($cache_key) . '.cache';
        return file_put_contents($file, serialize($data));
    }
    
    /**
     * Удалить из кэша
     */
    public static function delete($key) {
        $cache_key = self::key($key);
        
        if (self::$use_redis) {
            return self::$redis->del($cache_key);
        }
        
        if (function_exists('wp_cache_delete')) {
            return wp_cache_delete($cache_key, BM_CACHE_GROUP);
        }
        
        $file = self::$cache_dir . md5($cache_key) . '.cache';
        return file_exists($file) ? unlink($file) : true;
    }
    
    /**
     * Очистить по паттерну
     */
    public static function flush_by_pattern($pattern = '*') {
        if (self::$use_redis) {
            $keys = self::$redis->keys(BM_CACHE_GROUP . ':' . $pattern);
            foreach ($keys as $key) {
                self::$redis->del($key);
            }
        }
    }
    
    /**
     * Сформировать ключ
     */
    private static function key($key) {
        return BM_CACHE_GROUP . ':' . md5(serialize($key));
    }
}