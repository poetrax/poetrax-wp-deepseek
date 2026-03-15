<?php
namespace BM\Cache;

class AdvancedPropertiesCache implements CacheInterface {
    private const TABLE_NAME = 'bm_ctbl000_properties_cache';
    private const DEFAULT_TIMEOUT = 3600;
    private const REDIS_GROUP = 'properties';
    
    private \PDO $pdo;
    private array $memoryCache = [];
    private bool $cacheEnabled;
    private int $defaultTimeout;
    private ?array $stats = null;
    
    private static ?self $instance = null;
    
    public static function getInstance(\PDO $pdo): self {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }
    
    public function __construct(\PDO $pdo, int $defaultTimeout = self::DEFAULT_TIMEOUT) {
        $this->pdo = $pdo;
        $this->defaultTimeout = $defaultTimeout;
        $this->cacheEnabled = $this->initializeTable();
    }
    
    private function initializeTable(): bool {
        try {
            $queries = [
                "CREATE TABLE IF NOT EXISTS " . self::TABLE_NAME . " (
                    cache_key VARCHAR(255) PRIMARY KEY,
                    cache_data LONGTEXT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    property_type VARCHAR(100),
                    hit_count INT UNSIGNED NOT NULL DEFAULT 0,
                    INDEX idx_expires_at (expires_at),
                    INDEX idx_property_type (property_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            foreach ($queries as $query) {
                $this->pdo->exec($query);
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log('Cache table initialization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить данные из кэша
     */
    public function get(string $key) {
        // 1. Проверяем in-memory кэш (самый быстрый)
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        // 2. Проверяем Redis через плагин (если доступен)
        $redis_data = $this->get_from_redis($key);
        if ($redis_data !== null) {
            $this->memoryCache[$key] = $redis_data; // Сохраняем в memory
            return $redis_data;
        }
        
        // 3. Проверяем базу данных (резервный вариант)
        $db_data = $this->get_from_database($key);
        if ($db_data !== null) {
            $this->memoryCache[$key] = $db_data; // Сохраняем в memory
            $this->set_to_redis($key, $db_data); // Обновляем Redis
            return $db_data;
        }
        
        return null;
    }
    
    /**
     * Сохранить данные в кэш - ИСПРАВЛЕНО для соответствия интерфейсу
     */
    public function set(string $key, $data, ?int $ttl = null): bool {
        $timeout = $ttl ?? $this->defaultTimeout;
        $expires_at = date('Y-m-d H:i:s', time() + $timeout);
        
        // 1. Сохраняем в in-memory (самый быстрый)
        $this->memoryCache[$key] = $data;
        
        // 2. Сохраняем в Redis (быстрый, распределенный)
        $this->set_to_redis($key, $data, $timeout);
        
        // 3. Сохраняем в базу (надежный, persistent)
        return $this->set_to_database($key, $data, $expires_at);
    }
    
    /**
     * Удалить данные из кэша - НОВЫЙ метод для интерфейса
     */
    public function delete(string $key): bool {
        // Удаляем из memory cache
        unset($this->memoryCache[$key]);
        
        // Удаляем из Redis
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($key, self::REDIS_GROUP);
        }
        
        // Удаляем из базы
        try {
            $query = "DELETE FROM " . self::TABLE_NAME . " WHERE cache_key = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$key]);
        } catch (\PDOException $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверить наличие ключа в кэше - НОВЫЙ метод для интерфейса
     */
    public function has(string $key): bool {
        return $this->get($key) !== null;
    }
    
    /**
     * Очистить кэш
     */
    public function clear(?string $pattern = null): bool {
        // Очищаем memory cache
        if ($pattern) {
            foreach (array_keys($this->memoryCache) as $key) {
                if (strpos($key, $pattern) !== false) {
                    unset($this->memoryCache[$key]);
                }
            }
        } else {
            $this->memoryCache = [];
        }
        
        // Очищаем Redis
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::REDIS_GROUP);
        }
        
        // Очищаем базу
        try {
            if ($pattern) {
                $query = "DELETE FROM " . self::TABLE_NAME . " WHERE cache_key LIKE ?";
                $stmt = $this->pdo->prepare($query);
                return $stmt->execute(['%' . $pattern . '%']);
            } else {
                $query = "TRUNCATE TABLE " . self::TABLE_NAME;
                $this->pdo->exec($query);
                return true;
            }
        } catch (\PDOException $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Работа с Redis через WordPress (если плагин установлен)
     */
    private function get_from_redis($key) {
        if (function_exists('wp_cache_get')) {
            $data = wp_cache_get($key, self::REDIS_GROUP);
            if ($data !== false) {
                return $data;
            }
        }
        return null;
    }
    
    private function set_to_redis($key, $data, $timeout = null) {
        if (function_exists('wp_cache_set')) {
            $timeout = $timeout ?: $this->defaultTimeout;
            return wp_cache_set($key, $data, self::REDIS_GROUP, $timeout);
        }
        return false;
    }
    
    /**
     * Работа с базой данных (надежное хранение)
     */
    private function get_from_database($key) {
        if (!$this->cacheEnabled) return null;
        
        try {
            $query = "SELECT cache_data, expires_at, hit_count 
                      FROM " . self::TABLE_NAME . " 
                      WHERE cache_key = ? AND expires_at > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$key]);
            
            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                // Увеличиваем счетчик использования
                $this->increment_hit_count($key);
                
                return unserialize($row['cache_data']);
            }
        } catch (\PDOException $e) {
            error_log("Cache DB read error: " . $e->getMessage());
        }
        
        return null;
    }
    
    private function set_to_database($key, $data, $expires_at) {
        if (!$this->cacheEnabled) return false;
        
        try {
            // Определяем тип свойства для статистики
            $property_type = $this->extract_property_type($key);
            
            $query = "INSERT INTO " . self::TABLE_NAME . " 
                      (cache_key, cache_data, expires_at, property_type, hit_count) 
                      VALUES (?, ?, ?, ?, 0)
                      ON DUPLICATE KEY UPDATE 
                      cache_data = VALUES(cache_data), 
                      expires_at = VALUES(expires_at),
                      hit_count = 0";
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([
                $key, 
                serialize($data), 
                $expires_at,
                $property_type
            ]);
            
        } catch (\PDOException $e) {
            error_log("Cache DB write error: " . $e->getMessage());
            return false;
        }
    }
    
    private function increment_hit_count($key) {
        try {
            $query = "UPDATE " . self::TABLE_NAME . " SET hit_count = hit_count + 1 WHERE cache_key = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$key]);
        } catch (\PDOException $e) {
            // Игнорируем ошибки счетчика
        }
    }
    
    /**
     * Генерация ключа кэша
     */
    public function generate_key($property_type, $filters = []) {
        $key_parts = ['properties', $property_type];
        
        if (!empty($filters)) {
            $key_parts[] = md5(serialize($filters));
        }
        
        return implode('_', $key_parts);
    }
    
    private function extract_property_type($key) {
        $parts = explode('_', $key);
        return $parts[1] ?? 'unknown';
    }
    
    /**
     * Очистка просроченных записей
     */
    public function clean_expired() {
        try {
            $query = "DELETE FROM " . self::TABLE_NAME . " WHERE expires_at <= NOW()";
            $this->pdo->exec($query);
        } catch (\PDOException $e) {
            error_log("Cache cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Статистика кэша
     */
    public function get_stats() {
        try {
            $query = "
                SELECT 
                    property_type,
                    COUNT(*) as total_entries,
                    SUM(hit_count) as total_hits,
                    AVG(hit_count) as avg_hits,
                    MAX(created_at) as last_created
                FROM " . self::TABLE_NAME . " 
                WHERE expires_at > NOW()
                GROUP BY property_type
            ";
            
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            return [];
        }
    }
}