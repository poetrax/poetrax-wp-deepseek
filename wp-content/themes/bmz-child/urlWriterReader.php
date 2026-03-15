<?php
class UrlWriterReader {
    private $pdo;
    private array $config;
    private array $fieldMap;
    
    public function __construct(PDO $pdo, array $config = []) {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'base_url' => 'https://poetrax.ru',
            'param_prefix' => '',
            'use_cache' => true,
            'cache_ttl' => 3600
        ], $config);
        
        // Карта соответствия параметров URL полям таблицы
        $this->fieldMap = [
            'it'  => ['field' => 'id', 'type' => 'int'],
            'iu'  => ['field' => 'user_id', 'type' => 'int'],
            'ia'  => ['field' => 'poet_id', 'type' => 'int'],
            'ip'  => ['field' => 'poem_id', 'type' => 'int'],
            'igs' => ['field' => 'genre_ids', 'type' => 'string'], // предполагается связь с доп. таблицей
            'iss' => ['field' => 'style_ids', 'type' => 'string'],
            'iis' => ['field' => 'instrument_ids', 'type' => 'string'],
            'nt'  => ['field' => 'track_name', 'type' => 'string'],
            'na'  => ['field' => 'poet_name', 'type' => 'string'],
            'np'  => ['field' => 'poem_name', 'type' => 'string'],
            'ng'  => ['field' => 'voice_gender', 'type' => 'string'],
            'nm'  => ['field' => 'mood_name', 'type' => 'string'], // предполагается связь
            'ith' => ['field' => 'theme_name', 'type' => 'string'], // предполагается связь
            'dc'  => ['field' => 'created_at', 'type' => 'timestamp']
        ];
    }
    
    /**
     * Чтение и валидация параметров из URL
     */
    public function readFromUrl(string $url): array {
        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $params);
        
        $result = [
            'track_data' => [],
            'additional_data' => [],
            'errors' => []
        ];
        
        foreach ($params as $key => $value) {
            if (!isset($this->fieldMap[$key])) {
                $result['errors'][] = "Неизвестный параметр: $key";
                continue;
            }
            
            $mapping = $this->fieldMap[$key];
            $processedValue = $this->processValue($value, $mapping['type']);
            
            if ($processedValue === null) {
                $result['errors'][] = "Некорректное значение для параметра $key";
                continue;
            }
            
            // Разделяем основные данные трека и дополнительные параметры
            if (in_array($key, ['it', 'iu', 'ia', 'ip', 'nt', 'ng', 'dc'])) {
                $result['track_data'][$mapping['field']] = $processedValue;
            } else {
                $result['additional_data'][$key] = $processedValue;
            }
        }
        
        return $result;
    }
    
    /**
     * Генерация URL по ID трека
     */
    public function writeUrl(int $trackId, array $additionalParams = []): string {
        // Получаем данные трека из БД
        $trackData = $this->getTrackData($trackId);
        
        if (!$trackData) {
            throw new InvalidArgumentException("Трек с ID $trackId не найден");
        }
        
        // Подготавливаем параметры
        $params = [];
        
        // Основные параметры из данных трека
        $params['it'] = $trackData['id'];
        $params['iu'] = $trackData['user_id'];
        $params['ia'] = $trackData['poet_id'] ?? '';
        $params['ip'] = $trackData['poem_id'] ?? '';
        $params['nt'] = $this->encodeForUrl($trackData['track_name']);
        $params['na'] = $this->encodeForUrl($trackData['poet_name'] ?? '');
        $params['np'] = $this->encodeForUrl($trackData['poem_name'] ?? '');
        $params['ng'] = $trackData['voice_gender'] ?? '';
        $params['dc'] = strtotime($trackData['created_at']);
        
        // Добавляем дополнительные параметры
        foreach ($additionalParams as $key => $value) {
            if (isset($this->fieldMap[$key])) {
                $params[$key] = $value;
            }
        }
        
        // Фильтруем пустые значения и строим URL
        $params = array_filter($params);
        $queryString = http_build_query($params);
        
        return $this->config['base_url'] . '?' . $queryString;
    }
    
    /**
     * Получение данных трека с кэшированием
     */
    private function getTrackData(int $trackId): ?array {
        $cacheKey = "track_{$trackId}";
        
        // Проверка кэша (можно использовать Redis, Memcached или файловый кэш)
        if ($this->config['use_cache']) {
            $cached = apcu_fetch($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $stmt = $this->pdo->prepare("
            SELECT t.*, 
                   m.name as mood_name,
                   th.name as theme_name,
                   GROUP_CONCAT(DISTINCT g.id ORDER BY g.id SEPARATOR '_') as genre_ids,
                   GROUP_CONCAT(DISTINCT s.id ORDER BY s.id SEPARATOR '_') as style_ids
            FROM bm_ctbl000_track t
            LEFT JOIN moods m ON m.id = t.mood_id
            LEFT JOIN themes th ON th.id = t.theme_id
            LEFT JOIN track_genres tg ON tg.track_id = t.id
            LEFT JOIN genres g ON g.id = tg.genre_id
            LEFT JOIN track_styles ts ON ts.track_id = t.id
            LEFT JOIN styles s ON s.id = ts.style_id
            WHERE t.id = ?
            GROUP BY t.id
        ");
        
        $stmt->execute([$trackId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data && $this->config['use_cache']) {
            apcu_store($cacheKey, $data, $this->config['cache_ttl']);
        }
        
        return $data ?: null;
    }
    
    /**
     * Обработка значений в зависимости от типа
     */
    private function processValue($value, string $type) {
        if ($value === '' || $value === null) {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT);
                
            case 'string':
                return $this->decodeFromUrl($value);
                
            case 'timestamp':
                $timestamp = filter_var($value, FILTER_VALIDATE_INT);
                return ($timestamp && $timestamp > 0) ? date('Y-m-d H:i:s', $timestamp) : null;
                
            case 'array': // Для ID через подчеркивание
                $items = explode('_', $value);
                return array_map('intval', array_filter($items, 'is_numeric'));
                
            default:
                return $value;
        }
    }
    
    /**
     * Кодирование для URL (безопасная обработка)
     */
    private function encodeForUrl(string $value): string {
        // Заменяем пробелы на подчеркивания, кодируем остальное
        $value = str_replace(' ', '_', $value);
        return rawurlencode($value);
    }
    
    /**
     * Декодирование из URL
     */
    private function decodeFromUrl(string $value): string {
        $decoded = rawurldecode($value);
        return str_replace('_', ' ', $decoded);
    }
    
    /**
     * Валидация всех параметров
     */
    public function validateParameters(array $params): array {
        $errors = [];
        
        foreach ($params as $key => $value) {
            if (!isset($this->fieldMap[$key])) {
                continue;
            }
            
            $mapping = $this->fieldMap[$key];
            
            switch ($mapping['type']) {
                case 'int':
                    if (!is_numeric($value)) {
                        $errors[$key] = 'Должно быть числом';
                    }
                    break;
                    
                case 'string':
                    if (strlen($value) > 500) {
                        $errors[$key] = 'Слишком длинное значение';
                    }
                    break;
            }
        }
        
        return $errors;
    }
    
    /**
     * Создание параметров для AJAX запросов (JSON)
     */
    public function getAjaxParams(array $params): string {
        $filtered = [];
        
        foreach ($params as $key => $value) {
            if (isset($this->fieldMap[$key])) {
                $filtered[$key] = $value;
            }
        }
        
        return json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }
}

/*
Пример использования:
php
<?php
// Инициализация
require_once 'UrlWriterReader.php';

$pdo = new PDO('mysql:host=localhost;dbname=database', 'user', 'password');
$urlHandler = new UrlWriterReader($pdo, [
    'base_url' => 'https://poetrax.ru',
    'use_cache' => true
]);

// Пример 1: Чтение параметров из URL
$url = 'https://poetrax.ru?it=1234&iu=25&ia=77&ip=5678&igs=77_17&nt=Зимний_вечер_(r&b)&na=Александр_Пушкин';
$result = $urlHandler->readFromUrl($url);

// Использование данных
if (empty($result['errors'])) {
    $trackId = $result['track_data']['id'];
    $userId = $result['track_data']['user_id'];
    $genreIds = $result['additional_data']['igs']; // [77, 17]
}

// Пример 2: Генерация URL
try {
    $url = $urlHandler->writeUrl(1234, [
        'igs' => '77_17',
        'iss' => '45_11',
        'nm' => 'dark'
    ]);
    // Результат: https://poetrax.ru?it=1234&iu=25&ia=77&ip=5678&nt=Зимний_вечер_(r&b)&na=Александр_Пушкин&np=Зимний_вечер&ng=female&dc=1760745741&igs=77_17&iss=45_11&nm=dark
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}

// Пример 3: Для AJAX запросов
$ajaxParams = $urlHandler->getAjaxParams([
    'it' => 1234,
    'iu' => 25,
    'igs' => '77_17_45'
]);
*/
