Пример использования:

<?php
// Инициализация (например, в functions.php)
use BM\Cache\AdvancedPropertiesCache;
use BM\Cache\PropertiesConfig;
use BM\Cache\PropertiesCacheManager;
use BM\Cache\AjaxPropertiesHandler;

// Создаем PDO соединение
$pdo = new \PDO('mysql:host=localhost;dbname=your_db', 'user', 'password');

// Инициализируем компоненты
$cache = AdvancedPropertiesCache::getInstance($pdo);
$config = new PropertiesConfig();
$cacheManager = new PropertiesCacheManager($cache, $config, $pdo);
$ajaxHandler = new AjaxPropertiesHandler($cacheManager);

// Пример 1: Получение свойств с кэшированием
function get_properties($type) {
    global $cacheManager;
    return $cacheManager->getProperties($type);
}

$instruments = get_properties('instruments');

// Пример 2: Принудительное обновление кэша
function refresh_properties_cache($type = null) {
    global $cacheManager;
    return $cacheManager->invalidateCache($type);
}

// Пример 3: Разогрев кэша для всех типов
function warmup_all_caches() {
    global $cacheManager, $config;
    
    foreach ($config->getTypes() as $type) {
        $cacheManager->warmupCache($type);
    }
}

// Пример 4: Очистка просроченных записей (можно запускать по крону)
function clean_expired_cache() {
    global $cache;
    return $cache->cleanExpired();
}

// Пример 5: Получение статистики
function get_cache_stats() {
    global $cache;
    return $cache->getStats();
}

// Пример 6: Использование в шаблоне
?>
<script>
jQuery(document).ready(function($) {
    $('#load-properties').on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_properties',
                property_type: 'instruments',
                nonce: properties_nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Loaded from cache:', response.data.cached);
                    // Обработка данных
                }
            }
        });
    });
});
</script>

<?php
// Пример 7: Крон-задача для очистки кэша
if (!wp_next_scheduled('clean_properties_cache')) {
    wp_schedule_event(time(), 'hourly', 'clean_properties_cache');
}
add_action('clean_properties_cache', 'clean_expired_cache');