<?php
class UltraMinimalUrlHandler {
    private $pdo;
    private RedisCache $cache;
    private array $config;
    
    public function __construct(PDO $pdo, array $config = []) {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'base_url' => 'https://bestmz.com',
            'param_name' => 'ti', // track_id parameter
            'cache_enabled' => true,
            'cache_ttl' => 7200, // 2 часа
            'cache_prefix' => 'track_full_',
            'prefetch_related' => true, // предзагрузка связанных данных
            'compress_url' => false, // сжатие URL
        ], $config);
        
        if ($this->config['cache_enabled']) {
            $this->cache = new RedisCache($config['redis'] ?? []);
        }
    }
    
    /**
     * Генерация URL - просто track_id
     */
    public function generateUrl(int $trackId, array $additionalParams = []): string {
        $params = ['ti' => $trackId];
        
        // Добавляем только валидные дополнительные параметры
        $allowedParams = ['ref', 'source', 'campaign', 'utm'];
        foreach ($additionalParams as $key => $value) {
            if (in_array($key, $allowedParams)) {
                $params[$key] = $value;
            }
        }
        
        $queryString = http_build_query($params);
        
        // Опциональное сжатие URL
        if ($this->config['compress_url'] && strlen($queryString) > 50) {
            $queryString = $this->compressParams($params);
        }
        
        return $this->config['base_url'] . '?' . $queryString;
    }
    
    /**
     * Чтение URL - получаем track_id и все данные из кэша/БД
     */
    public function parseUrl(string $url): array {
        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $params);
        
        $trackId = (int)($params['ti'] ?? $params['track_id'] ?? 0);
        
        if (!$trackId) {
            throw new InvalidArgumentException("Track ID not found in URL");
        }
        
        // Получаем полные данные трека
        $trackData = $this->getFullTrackData($trackId);
        
        if (!$trackData) {
            throw new RuntimeException("Track with ID {$trackId} not found");
        }
        
        return [
            'track_id' => $trackId,
            'track_data' => $trackData,
            'additional_params' => array_diff_key($params, ['ti' => 0, 'track_id' => 0]),
            'timestamp' => time()
        ];
    }
    
    /**
     * Получение полных данных трека с кэшированием
     */
    private function getFullTrackData(int $trackId): array {
        $cacheKey = $this->config['cache_prefix'] . $trackId;
        
        // Пытаемся получить из кэша
        if ($this->config['cache_enabled'] && $this->cache->isConnected()) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== false) {
                // Обновляем TTL для часто используемых данных
                $this->cache->set($cacheKey, $cached, $this->config['cache_ttl']);
                return $cached;
            }
        }
        
        // Получаем из базы
        $data = $this->fetchTrackWithAllData($trackId);
        
        if (!$data) {
            throw new RuntimeException("Track data not found for ID: {$trackId}");
        }
        
        // Обогащаем данными
        $data = $this->enrichTrackData($data);
        
        // Сохраняем в кэш
        if ($this->config['cache_enabled'] && $this->cache->isConnected()) {
            $this->cache->set($cacheKey, $data, $this->config['cache_ttl']);
        }
        
        return $data;
    }
    
    /**
     * Получение всех данных трека одним запросом
     */
    private function fetchTrackWithAllData(int $trackId): ?array {
        $query = "
            SELECT 
                t.*,
                u.user_email,
                u.display_name as user_display_name,
                GROUP_CONCAT(DISTINCT g.id ORDER BY g.id) as genre_ids,
                GROUP_CONCAT(DISTINCT g.name ORDER BY g.id) as genre_names,
                GROUP_CONCAT(DISTINCT s.id ORDER BY s.id) as style_ids,
                GROUP_CONCAT(DISTINCT s.name ORDER BY s.id) as style_names,
                GROUP_CONCAT(DISTINCT i.id ORDER BY i.id) as instrument_ids,
                GROUP_CONCAT(DISTINCT i.name ORDER BY i.id) as instrument_names,
                m.name as mood_name,
                th.name as theme_name,
                p.poet_name as poet_name_full,
                p2.poem_name as poem_name_full,
                img.guid as image_url
            FROM bm_ctbl000_track t
            LEFT JOIN wp_users u ON u.ID = t.user_id
            LEFT JOIN bm_ctbl000_track_genres tg ON tg.track_id = t.id
            LEFT JOIN bm_ctbl000_genres g ON g.id = tg.genre_id
            LEFT JOIN bm_ctbl000_track_styles ts ON ts.track_id = t.id
            LEFT JOIN bm_ctbl000_styles s ON s.id = ts.style_id
            LEFT JOIN bm_ctbl000_track_instruments ti ON ti.track_id = t.id
            LEFT JOIN bm_ctbl000_instruments i ON i.id = ti.instrument_id
            LEFT JOIN bm_ctbl000_moods m ON m.id = t.mood_id
            LEFT JOIN bm_ctbl000_themes th ON th.id = t.theme_id
            LEFT JOIN bm_ctbl000_poets p ON p.id = t.poet_id
            LEFT JOIN bm_ctbl000_poems p2 ON p2.id = t.poem_id
            LEFT JOIN wp_posts img ON img.ID = t.img_id AND img.post_type = 'attachment'
            WHERE t.id = ? AND t.status = 'completed'
            GROUP BY t.id
        ";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$trackId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                return null;
            }
            
            // Преобразуем строки в массивы
            $data['genre_ids'] = $data['genre_ids'] ? explode(',', $data['genre_ids']) : [];
            $data['genre_names'] = $data['genre_names'] ? explode(',', $data['genre_names']) : [];
            $data['style_ids'] = $data['style_ids'] ? explode(',', $data['style_ids']) : [];
            $data['style_names'] = $data['style_names'] ? explode(',', $data['style_names']) : [];
            $data['instrument_ids'] = $data['instrument_ids'] ? explode(',', $data['instrument_ids']) : [];
            $data['instrument_names'] = $data['instrument_names'] ? explode(',', $data['instrument_names']) : [];
            
            return $data;
            
        } catch (PDOException $e) {
            error_log("Error fetching track data: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Обогащение данных (дополнительная обработка)
     */
    private function enrichTrackData(array $data): array {
        // Форматирование длительности
        $data['duration_formatted'] = $this->formatDuration($data['track_duration']);
        
        // Форматирование размера файла
        $data['file_size_formatted'] = $this->formatFileSize($data['track_file_size']);
        
        // Генерация SEO-friendly slug
        $data['seo_slug'] = $this->generateSeoSlug(
            $data['track_name'],
            $data['poet_name'] ?? '',
            $data['id']
        );
        
        // URL для скачивания/прослушивания
        $data['listen_url'] = $this->generateMediaUrl($data['track_path'], 'listen');
        $data['download_url'] = $this->generateMediaUrl($data['track_path'], 'download');
        
        // Мета-данные для соцсетей
        $data['og_tags'] = $this->generateOpenGraphTags($data);
        $data['twitter_tags'] = $this->generateTwitterTags($data);
        
        // Статистика
        $data['stats'] = $this->getTrackStats($data['id']);
        
        return $data;
    }
    
    /**
     * Пакетная обработка URL
     */
    public function batchProcessUrls(array $urls): array {
        $results = [];
        $trackIds = [];
        
        // Извлекаем track_id из всех URL
        foreach ($urls as $url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $trackId = (int)($params['ti'] ?? 0);
            if ($trackId) {
                $trackIds[] = $trackId;
            }
        }
        
        // Получаем данные пакетно
        $tracksData = $this->getMultipleTracksData($trackIds);
        
        // Формируем результаты
        foreach ($urls as $url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $trackId = (int)($params['ti'] ?? 0);
            
            if ($trackId && isset($tracksData[$trackId])) {
                $results[] = [
                    'url' => $url,
                    'track_id' => $trackId,
                    'data' => $tracksData[$trackId],
                    'params' => array_diff_key($params, ['ti' => 0])
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Пакетное получение данных треков
     */
    private function getMultipleTracksData(array $trackIds): array {
        if (empty($trackIds)) {
            return [];
        }
        
        $results = [];
        $toFetch = [];
        
        // Проверяем кэш
        foreach ($trackIds as $trackId) {
            $cacheKey = $this->config['cache_prefix'] . $trackId;
            
            if ($this->config['cache_enabled'] && $this->cache->isConnected()) {
                $cached = $this->cache->get($cacheKey);
                if ($cached !== false) {
                    $results[$trackId] = $cached;
                    continue;
                }
            }
            
            $toFetch[] = $trackId;
        }
        
        // Загружаем недостающие из БД
        if (!empty($toFetch)) {
            $fetched = $this->fetchMultipleTracks($toFetch);
            
            foreach ($fetched as $trackId => $data) {
                if ($data) {
                    $data = $this->enrichTrackData($data);
                    $cacheKey = $this->config['cache_prefix'] . $trackId;
                    
                    if ($this->config['cache_enabled'] && $this->cache->isConnected()) {
                        $this->cache->set($cacheKey, $data, $this->config['cache_ttl']);
                    }
                    
                    $results[$trackId] = $data;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Статистика использования
     */
    public function getUsageStats(): array {
        if (!$this->config['cache_enabled'] || !$this->cache->isConnected()) {
            return ['cache_enabled' => false];
        }
        
        $stats = $this->cache->getStats();
        $prefix = $this->config['cache_prefix'];
        
        // Получаем ключи треков из Redis
        try {
            $keys = $this->cache->getKeys($prefix . '*');
            $stats['cached_tracks'] = count($keys);
            $stats['memory_per_track'] = $stats['cached_tracks'] > 0 
                ? round($stats['used_memory'] / $stats['cached_tracks']) 
                : 0;
        } catch (Exception $e) {
            $stats['cached_tracks'] = 'N/A';
        }
        
        return $stats;
    }
    
    /**
     * Вспомогательные методы
     */
    private function formatDuration(int $seconds): string {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    private function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function generateSeoSlug(string $trackName, string $poetName, int $id): string {
        $slug = $trackName . ' ' . $poetName;
        $slug = preg_replace('/[^a-z0-9а-яё]/iu', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        return $slug . '-' . $id;
    }
    
    private function generateMediaUrl(?string $path, string $type): ?string {
        if (!$path) return null;
        
        $base = 'https://media.bestmz.com/';
        
        switch ($type) {
            case 'listen':
                return $base . 'stream/' . basename($path);
            case 'download':
                return $base . 'download/' . basename($path);
            default:
                return $base . basename($path);
        }
    }
    
    private function generateOpenGraphTags(array $data): array {
        return [
            'og:title' => $data['track_name'],
            'og:description' => $data['track_theme'] ?? 'Музыкальная композиция',
            'og:type' => 'music.song',
            'og:url' => $this->config['base_url'] . '?ti=' . $data['id'],
            'og:image' => $data['image_url'] ?? $this->config['default_image'],
            'og:audio' => $data['listen_url'] ?? null,
            'music:duration' => $data['track_duration'],
            'music:musician' => $data['poet_name'] ?? null,
        ];
    }
    
    private function generateTwitterTags(array $data): array {
        return [
            'twitter:card' => 'player',
            'twitter:title' => $data['track_name'],
            'twitter:description' => substr($data['track_theme'] ?? '', 0, 200),
            'twitter:image' => $data['image_url'] ?? $this->config['default_image'],
            'twitter:player' => $data['listen_url'] ?? null,
            'twitter:player:width' => 480,
            'twitter:player:height' => 120,
        ];
    }
    
    private function getTrackStats(int $trackId): array {
        // Здесь можно получить статистику прослушиваний, лайков и т.д.
        return [
            'plays' => 0,
            'likes' => 0,
            'shares' => 0,
            'downloads' => 0,
        ];
    }
    
    /**
     * Сжатие параметров (опционально)
     */
    private function compressParams(array $params): string {
        // Простое base64 кодирование
        $json = json_encode($params);
        $compressed = base64_encode(gzcompress($json, 9));
        
        // Убираем символы = в конце
        $compressed = rtrim($compressed, '=');
        
        return 'c=' . $compressed;
    }
    
    /**
     * Распаковка параметров
     */
    private function decompressParams(string $compressed): array {
        $data = base64_decode($compressed . str_repeat('=', strlen($compressed) % 4));
        $json = gzuncompress($data);
        return json_decode($json, true);
    }
}