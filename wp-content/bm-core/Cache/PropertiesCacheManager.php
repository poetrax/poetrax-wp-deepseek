<?php
namespace BM\Cache;

use BM\Cache\CacheInterface;
use BM\Cache\PropertiesConfig;

class PropertiesCacheManager {
    private CacheInterface $cache;
    private PropertiesConfig $config;
    private \PDO $pdo;
    
    public function __construct(CacheInterface $cache, PropertiesConfig $config, \PDO $pdo) {
        $this->cache = $cache;
        $this->config = $config;
        $this->pdo = $pdo;
    }
    
    public function getProperties(string $type, bool $forceRefresh = false): array {
        if (!$this->config->has($type)) {
            throw new \InvalidArgumentException("Invalid property type: $type");
        }
        
        $cacheKey = $this->cache->generate_key($type);
        
        if (!$forceRefresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $properties = $this->fetchProperties($type);
        $response = $this->buildResponse($type, $properties);
        
        $this->cache->set($cacheKey, $response);
        
        return $response;
    }
    
    private function fetchProperties(string $type): array {
        $config = $this->config->get($type);
        $query = sprintf(
            "SELECT %s FROM %s WHERE %s ORDER BY %s",
            $config['columns'],
            $config['table'],
            $config['where'],
            $config['order']
        );
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function buildResponse(string $type, array $properties): array {
        return [
            'type' => $type,
            'data' => $properties,
            'total' => count($properties),
            'timestamp' => current_time('mysql'),
            'cached' => false
        ];
    }
    
    public function invalidateCache(?string $type = null): bool {
        if ($type) {
            return $this->cache->delete($this->cache->generate_key($type));
        }
        
        return $this->cache->clear();
    }
    
    public function warmupCache(string $type): bool {
        try {
            $properties = $this->fetchProperties($type);
            $response = $this->buildResponse($type, $properties);
            
            return $this->cache->set(
                $this->cache->generate_key($type),
                $response
            );
        } catch (\Exception $e) {
            error_log("Cache warmup failed for $type: " . $e->getMessage());
            return false;
        }
    }
}