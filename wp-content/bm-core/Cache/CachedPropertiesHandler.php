<?php
namespace BM\Cache;

use BM\Cache\AdvancedPropertiesCache;
use BM\Cache\UniversalPropertiesHandler;

class CachedPropertiesHandler extends UniversalPropertiesHandler {
    private $cache_manager;
    
    public function __construct(\PDO $pdo) {
        parent::__construct($pdo);
        $this->cache_manager = AdvancedPropertiesCache::getInstance($pdo);
    }
    
    public function handle_get_properties() {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'properties_nonce')) {
                throw new Exception('Security verification failed');
            }
            
            $type = sanitize_text_field($_POST['property_type'] ?? '');
            $force_refresh = (bool) ($_POST['force_refresh'] ?? false);
            
            if (empty($type) || !isset($this->config[$type])) {
                throw new Exception('Invalid property type');
            }
            
            // Генерируем ключ кэша
            $cache_key = $this->cache_manager->generate_key($type);
            
            // Пробуем получить из кэша (если не принудительное обновление)
            if (!$force_refresh) {
                $cached_data = $this->cache_manager->get($cache_key);
                if ($cached_data !== null) {
                    wp_send_json_success($cached_data);
                    wp_die();
                }
            }
            
            // Если нет в кэше - получаем из БД
            $config = $this->config[$type];
            $query = $this->build_query($config);
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'type' => $type,
                'data' => $properties,
                'total' => count($properties),
                'timestamp' => current_time('mysql'),
                'cached' => false
            ];
            
            // Сохраняем в кэш
            $this->cache_manager->set($cache_key, $response);
            $response['cached'] = true;
            
            wp_send_json_success($response);
            
        } catch (Exception $exception) {
            $this->send_error($exception->getMessage());
        }
        
        wp_die();
    }
    
    /**
     * Метод для принудительного обновления кэша
     */
    public function refresh_cache($type = null) {
        if ($type) {
            $cache_key = $this->cache_manager->generate_key($type);
            $this->cache_manager->clear($type);
        } else {
            $this->cache_manager->clear();
        }
        
        return ['success' => true, 'message' => 'Cache refreshed'];
    }
}
