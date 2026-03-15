Обновленный UrlWriterReader с Redis
php
<?php
require_once 'redisCache.php';

class UrlWriterReader {
    private $pdo;
    private RedisCache $cache;
    private array $config;
    private array $fieldMap;
    
    public function __construct(PDO $pdo, array $redisConfig = [], array $config = []) {
        $this->pdo = $pdo;
        
        // Инициализация Redis
        $this->cache = new RedisCache($redisConfig);
        
        // Конфигурация
        $this->config = array_merge([
            'base_url' => 'https://poetrax.ru',
            'param_prefix' => '',
            'use_cache' => true,
            'cache_ttl' => 3600, // 1 час по умолчанию
            'cache_prefix' => 'track_'
        ], $config);
        
        $this->fieldMap = [
            // ... (остается как в предыдущем примере)
        ];
    }
    
    /**
     * Получение данных трека с Redis кэшированием
     */
    private function getTrackData(int $trackId): ?array {
        $cacheKey = "{$this->config['cache_prefix']}{$trackId}";
        
        // Пытаемся получить из кэша
        if ($this->config['use_cache'] && $this->cache->isConnected()) {
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== false) {
                // Обновляем TTL при каждом обращении (ленивое обновление)
                $this->cache->set($cacheKey, $cachedData, $this->config['cache_ttl']);
                return $cachedData;
            }
        }
        
        // Получаем из базы данных
        $data = $this->fetchTrackFromDatabase($trackId);
        
        if (!$data) {
            return null;
        }
        
        // Обогащаем данными из связанных таблиц
        $data = $this->enrichTrackData($data);
        
        // Сохраняем в кэш
        if ($this->config['use_cache'] && $this->cache->isConnected()) {
            $this->cache->set($cacheKey, $data, $this->config['cache_ttl']);
        }
        
        return $data;
    }
    
    /**
     * Получение данных из базы данных
     */
    private function fetchTrackFromDatabase(int $trackId): ?array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, wp_id, is_payable, user_id, track_name, track_theme, 
                    track_lang, track_format, track_duration, track_bitrate,
                    track_file_size, suno_version, status, admin_message,
                    is_self_made, performance_type, mood_id, theme_id, temp_id,
                    presentation_id, suno_style_id, voice_gender, voice_character_id,
                    is_site_placement, is_send_email, ip, guid_track, poet_slug,
                    poet_name, poem_slug, poem_name, poet_id, poem_id, img_id,
                    caption, `age restriction`, is_show_img, is_show_caption,
                    is_advertising, is_offtop, is_video, is_approved,
                    created_at, updated_at
                FROM bm_ctbl000_track 
                WHERE id = ? AND status = 'completed'
            ");
            
            $stmt->execute([$trackId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (PDOException $e) {
            error_log("Database error fetching track {$trackId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Обогащение данных трека информацией из связанных таблиц
     */
    private function enrichTrackData(array $trackData): array {
        $trackId = $trackData['id'];
        
        // Получение жанров
        $trackData['genre_ids'] = $this->getRelatedIds(
            'bm_ctbl000_track_genres',
            'genre_id',
            $trackId
        );
        
        // Получение стилей
        $trackData['style_ids'] = $this->getRelatedIds(
            'bm_ctbl000_track_styles',
            'style_id',
            $trackId
        );
        
        // Получение инструментов
        $trackData['instrument_ids'] = $this->getRelatedIds(
            'bm_ctbl000_track_instruments',
            'instrument_id',
            $trackId
        );
        
        // Получение настроения
        if ($trackData['mood_id']) {
            $trackData['mood_name'] = $this->getLookupName(
                'bm_ctbl000_moods',
                $trackData['mood_id']
            );
        }
        
        // Получение темы
        if ($trackData['theme_id']) {
            $trackData['theme_name'] = $this->getLookupName(
                'bm_ctbl000_themes',
                $trackData['theme_id']
            );
        }
        
        return $trackData;
    }
    
    /**
     * Получение ID связанных записей
     */
    private function getRelatedIds(string $table, string $idField, int $trackId): string {
        try {
            $stmt = $this->pdo->prepare("
                SELECT {$idField} 
                FROM {$table} 
                WHERE track_id = ? 
                ORDER BY {$idField}
            ");
            
            $stmt->execute([$trackId]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return implode('_', $ids);
            
        } catch (PDOException $e) {
            error_log("Error fetching related IDs from {$table}: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Получение имени из справочника
     */
    private function getLookupName(string $table, int $id): ?string {
        try {
            $stmt = $this->pdo->prepare("
                SELECT name 
                FROM {$table} 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            return $stmt->fetchColumn() ?: null;
            
        } catch (PDOException $e) {
            error_log("Error fetching name from {$table}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Инвалидация кэша для трека
     */
    public function invalidateCache(int $trackId): bool {
        if (!$this->cache->isConnected()) {
            return false;
        }
        
        return $this->cache->delete("{$this->config['cache_prefix']}{$trackId}");
    }
    
    /**
     * Пакетное получение треков с кэшированием
     */
    public function getMultipleTracks(array $trackIds): array {
        $result = [];
        $toFetch = [];
        
        // Пытаемся получить из кэша
        foreach ($trackIds as $trackId) {
            $cacheKey = "{$this->config['cache_prefix']}{$trackId}";
            
            if ($this->config['use_cache'] && $this->cache->isConnected()) {
                $cached = $this->cache->get($cacheKey);
                if ($cached !== false) {
                    $result[$trackId] = $cached;
                    continue;
                }
            }
            
            $toFetch[] = $trackId;
        }
        
        // Получаем оставшиеся из базы
        if (!empty($toFetch)) {
            $fetched = $this->fetchMultipleTracksFromDatabase($toFetch);
            
            // Сохраняем в кэш и добавляем к результату
            foreach ($fetched as $trackId => $data) {
                if ($data) {
                    $cacheKey = "{$this->config['cache_prefix']}{$trackId}";
                    
                    if ($this->config['use_cache'] && $this->cache->isConnected()) {
                        $this->cache->set($cacheKey, $data, $this->config['cache_ttl']);
                    }
                    
                    $result[$trackId] = $data;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Массовое получение треков из базы
     */
    private function fetchMultipleTracksFromDatabase(array $trackIds): array {
        if (empty($trackIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($trackIds) - 1) . '?';
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM bm_ctbl000_track 
                WHERE id IN ({$placeholders}) AND status = 'completed'
            ");
            
            $stmt->execute($trackIds);
            $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($tracks as $track) {
                $result[$track['id']] = $this->enrichTrackData($track);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error fetching multiple tracks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение статистики кэша
     */
    public function getCacheStats(): array {
        return $this->cache->getStats();
    }
    
    /**
     * Очистка всего кэша треков
     */
    public function clearAllCache(): int {
        return $this->cache->clearTrackCache();
    }
    
    // ... остальные методы из urlWriterReader.php остаются без изменений
}

Пример конфигурации и использования
php
<?php
// config/redis_config.php
return [
    'redis' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null, // если есть пароль
        'database' => 1, // отдельная база для треков
        'timeout' => 2.5,
        'prefix' => 'bestmz:'
    ],
    
    'url_handler' => [
        'base_url' => 'https://poetrax.ru',
        'use_cache' => true,
        'cache_ttl' => 7200, // 2 часа
        'cache_prefix' => 'track_data:'
    ]
];
php
<?php
// index.php - пример использования
require_once 'vendor/autoload.php';
require_once 'UrlWriterReader.php';

// Конфигурация
$config = require 'config/redis_config.php';

// Подключение к базе данных
$pdo = new PDO(
    'mysql:host=localhost;dbname=bestmz;charset=utf8mb4',
    'username',
    'password',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

// Создание обработчика с Redis
$urlHandler = new UrlWriterReader(
    $pdo,
    $config['redis'],
    $config['url_handler']
);

// Пример 1: Генерация URL с кэшированием
$trackId = 1234;
$url = $urlHandler->writeUrl($trackId, [
    'igs' => '77_17',
    'nm' => 'dark'
]);

echo "Сгенерированный URL: " . $url . "<br>";

// Пример 2: Получение нескольких треков (пакетное кэширование)
$trackIds = [1234, 1235, 1236];
$tracks = $urlHandler->getMultipleTracks($trackIds);

foreach ($tracks as $id => $trackData) {
    echo "Трек {$id}: " . htmlspecialchars($trackData['track_name']) . "<br>";
}

// Пример 3: Инвалидация кэша при обновлении трека
if ($_POST['update_track']) {
    // Обновляем трек в базе
    $query = "UPDATE bm_ctbl000_track SET track_name = ? WHERE id = ?";
    Pdo::query($query,[$_POST['new_name'], [$trackId]);
    
    // Инвалидируем кэш
    $urlHandler->invalidateCache($trackId);
    echo "Кэш для трека {$trackId} очищен<br>";
}

// Пример 4: Просмотр статистики кэша
$stats = $urlHandler->getCacheStats();
echo "<pre>Статистика Redis: ";
print_r($stats);
echo "</pre>";

// Пример 5: AJAX endpoint для получения данных трека
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_track'])) {
    header('Content-Type: application/json');
    
    $trackId = (int)$_GET['track_id'];
    $trackData = $urlHandler->getTrackData($trackId);
    
    if ($trackData) {
        echo json_encode([
            'success' => true,
            'data' => $trackData,
            'cache_hit' => $stats['hits'] ?? 0
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Трек не найден'
        ]);
    }
    exit;
}

