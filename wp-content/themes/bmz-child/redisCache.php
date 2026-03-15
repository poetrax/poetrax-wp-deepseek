<?php
class RedisCache {
    private ?Redis $redis = null;
    private string $prefix;
    private bool $enabled;
    
    public function __construct(array $config = []) {
        $this->enabled = $config['enabled'] ?? true;
        $this->prefix = $config['prefix'] ?? 'track_url:';
        
        if ($this->enabled) {
            $this->connect($config);
        }
    }
    
    private function connect(array $config): void {
        try {
            $this->redis = new Redis();
            
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 6379;
            $timeout = $config['timeout'] ?? 2.5;
            $password = $config['password'] ?? null;
            $database = $config['database'] ?? 0;
            
            $connected = $this->redis->connect($host, $port, $timeout);
            
            if (!$connected) {
                throw new RedisException("Could not connect to Redis");
            }
            
            if ($password) {
                $this->redis->auth($password);
            }
            
            $this->redis->select($database);
            
            // Тестовый пинг
            if (!$this->redis->ping()) {
                throw new RedisException("Redis ping failed");
            }
            
        } catch (RedisException $e) {
            error_log("Redis Connection failed: " . $e->getMessage());
            $this->redis = null;
            $this->enabled = false;
        }
    }
    
    public function get(string $key): mixed {
        if (!$this->isConnected()) {
            return false;
        }
        
        try {
            $data = $this->redis->get($this->prefix . $key);
            return $data ? unserialize($data) : false;
        } catch (RedisException $e) {
            error_log("Redis get error: " . $e->getMessage());
            return false;
        }
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        if (!$this->isConnected()) {
            return false;
        }
        
        try {
            return $this->redis->setex(
                $this->prefix . $key,
                $ttl,
                serialize($value)
            );
        } catch (RedisException $e) {
            error_log("Redis set error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete(string $key): bool {
        if (!$this->isConnected()) {
            return false;
        }
        
        try {
            return (bool)$this->redis->del($this->prefix . $key);
        } catch (RedisException $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deletePattern(string $pattern): int {
        if (!$this->isConnected()) {
            return 0;
        }
        
        try {
            $keys = $this->redis->keys($this->prefix . $pattern . '*');
            if (empty($keys)) {
                return 0;
            }
            
            return $this->redis->del($keys);
        } catch (RedisException $e) {
            error_log("Redis deletePattern error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function clearTrackCache(int $trackId = null): int {
        if ($trackId) {
            return $this->delete("track_{$trackId}") ? 1 : 0;
        }
        return $this->deletePattern("track_");
    }
    
    public function isConnected(): bool {
        return $this->enabled && $this->redis !== null && $this->redis->isConnected();
    }
    
    public function getStats(): array {
        if (!$this->isConnected()) {
            return ['connected' => false];
        }
        
        try {
            $info = $this->redis->info();
            return [
                'connected' => true,
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'keys_count' => $info['db0']['keys'] ?? 'N/A',
                'hits' => $info['keyspace_hits'] ?? 'N/A',
                'misses' => $info['keyspace_misses'] ?? 'N/A',
                'hit_rate' => isset($info['keyspace_hits'], $info['keyspace_misses']) 
                    ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2)
                    : 0
            ];
        } catch (RedisException $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function __destruct() {
        if ($this->redis) {
            try {
                $this->redis->close();
            } catch (RedisException $e) {
                // Игнорируем ошибки при закрытии
            }
        }
    }
}

/*


*/
