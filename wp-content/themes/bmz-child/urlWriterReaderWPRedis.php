<?php
class UrlWriterReaderWPRedis {
    private wpdb $wpdb;
    private array $config;
    private array $fieldMap;
    private string $cache_group = 'track_url_data';
    
    public function __construct(array $config = []) {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->config = wp_parse_args($config, [
            'base_url' => site_url(),
            'use_cache' => true,
            'cache_ttl' => HOUR_IN_SECONDS * 2, // 2 часа
            'cache_salt' => 'track_', // соль для кэша
        ]);
        
        $this->fieldMap = [
            'it'  => ['field' => 'id', 'type' => 'int'],
            'iu'  => ['field' => 'user_id', 'type' => 'int'],
            'ia'  => ['field' => 'poet_id', 'type' => 'int'],
            'ip'  => ['field' => 'poem_id', 'type' => 'int'],
            'igs' => ['field' => 'genre_ids', 'type' => 'string'],
            'iss' => ['field' => 'style_ids', 'type' => 'string'],
            'iis' => ['field' => 'instrument_ids', 'type' => 'string'],
            'nt'  => ['field' => 'track_name', 'type' => 'string'],
            'na'  => ['field' => 'poet_name', 'type' => 'string'],
            'np'  => ['field' => 'poem_name', 'type' => 'string'],
            'ng'  => ['field' => 'voice_gender', 'type' => 'string'],
            'nm'  => ['field' => 'mood_name', 'type' => 'string'],
            'ith' => ['field' => 'theme_name', 'type' => 'string'],
            'dc'  => ['field' => 'created_at', 'type' => 'timestamp']
        ];
        
        // Инициализация хуков для очистки кэша
        $this->init_hooks();
    }
    
    /**
     * Инициализация WordPress хуков
     */
    private function init_hooks(): void {
        // Очистка кэша при сохранении трека
        add_action('save_post_bm_track', [$this, 'clear_track_cache'], 10, 3);
        add_action('wp_trash_post', [$this, 'clear_track_cache_on_delete'], 10, 1);
        
        // Очистка при обновлении связанных терминов
        add_action('set_object_terms', [$this, 'clear_cache_on_term_update'], 10, 6);
    }
    
    /**
     * Получение данных трека с использованием WP Redis Object Cache
     */
    private function get_track_data(int $track_id): ?array {
        $cache_key = $this->get_cache_key($track_id);
        
        // Пытаемся получить из кэша WordPress (через Redis Object Cache)
        if ($this->config['use_cache']) {
            $cached_data = wp_cache_get($cache_key, $this->cache_group);
            
            if ($cached_data !== false) {
                // Обновляем TTL для часто используемых данных
                wp_cache_set($cache_key, $cached_data, $this->cache_group, $this->config['cache_ttl']);
                return $cached_data;
            }
        }
        
        // Получаем данные из базы
        $data = $this->fetch_track_from_database($track_id);
        
        if (!$data) {
            return null;
        }
        
        // Обогащаем данными
        $data = $this->enrich_track_data($data);
        
        // Сохраняем в кэш
        if ($this->config['use_cache']) {
            wp_cache_set($cache_key, $data, $this->cache_group, $this->config['cache_ttl']);
        }
        
        return $data;
    }
    
    /**
     * Генерация ключа кэша
     */
    private function get_cache_key(int $track_id): string {
        return $this->config['cache_salt'] . $track_id;
    }
    
    /**
     * Получение данных трека из базы WordPress
     */
    private function fetch_track_from_database(int $track_id): ?array {
        // Используем глобальный $wpdb для безопасности
        $table_name = $this->wpdb->prefix . 'ctbl000_track';
        
        $query = $this->wpdb->prepare("
            SELECT 
                id, wp_id, is_payable, user_id, track_name, track_theme, 
                track_lang, track_path, track_format, track_duration, 
                track_bitrate, track_file_size, suno_version, status, 
                admin_message, is_self_made, performance_type, mood_id, 
                theme_id, temp_id, presentation_id, suno_style_id, 
                voice_gender, voice_character_id, is_site_placement, 
                is_send_email, ip, guid_track, poet_slug, poet_name, 
                poem_slug, poem_name, poet_id, poem_id, img_id, caption, 
                `age restriction`, is_show_img, is_show_caption, 
                is_advertising, is_offtop, is_video, is_approved,
                created_at, updated_at
            FROM {$table_name} 
            WHERE id = %d AND status = 'completed'
        ", $track_id);
        
        return $this->wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Обогащение данных трека
     */
    private function enrich_track_data(array $track_data): array {
        $track_id = $track_data['id'];
        
        // Получение жанров (таксономия)
        $track_data['genre_ids'] = $this->get_track_terms($track_id, 'genre');
        
        // Получение стилей (таксономия)
        $track_data['style_ids'] = $this->get_track_terms($track_id, 'style');
        
        // Получение инструментов (таксономия)
        $track_data['instrument_ids'] = $this->get_track_terms($track_id, 'instrument');
        
        // Получение настроения
        if ($track_data['mood_id']) {
            $mood_term = get_term($track_data['mood_id'], 'mood');
            $track_data['mood_name'] = $mood_term ? $mood_term->name : null;
        }
        
        // Получение темы
        if ($track_data['theme_id']) {
            $theme_term = get_term($track_data['theme_id'], 'theme');
            $track_data['theme_name'] = $theme_term ? $theme_term->name : null;
        }
        
        // Получение информации о пользователе
        $user_data = get_userdata($track_data['user_id']);
        if ($user_data) {
            $track_data['user_display_name'] = $user_data->display_name;
            $track_data['user_email'] = $user_data->user_email;
        }
        
        return $track_data;
    }
    
    /**
     * Получение терминов таксономии для трека
     */
    private function get_track_terms(int $track_id, string $taxonomy): string {
        $terms = wp_get_object_terms($track_id, $taxonomy, [
            'fields' => 'ids',
            'orderby' => 'term_id',
        ]);
        
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }
        
        return implode('_', $terms);
    }
    
    /**
     * Генерация URL по ID трека
     */
    public function write_url(int $track_id, array $additional_params = []): string {
        // Получаем данные с кэшированием через Redis
        $track_data = $this->get_track_data($track_id);
        
        if (!$track_data) {
            return new WP_Error('track_not_found', 'Трек не найден', ['status' => 404]);
        }
        
        // Подготавливаем параметры
        $params = $this->prepare_url_params($track_data, $additional_params);
        
        // Строим URL
        return add_query_arg($params, $this->config['base_url']);
    }
    
    /**
     * Подготовка параметров для URL
     */
    private function prepare_url_params(array $track_data, array $additional_params): array {
        $params = [];
        
        // Основные параметры
        $params['it'] = $track_data['id'];
        $params['iu'] = $track_data['user_id'];
        $params['ia'] = $track_data['poet_id'] ?? '';
        $params['ip'] = $track_data['poem_id'] ?? '';
        $params['nt'] = $this->encode_for_url($track_data['track_name']);
        $params['na'] = $this->encode_for_url($track_data['poet_name'] ?? '');
        $params['np'] = $this->encode_for_url($track_data['poem_name'] ?? '');
        $params['ng'] = $track_data['voice_gender'] ?? '';
        $params['dc'] = strtotime($track_data['created_at']);
        
        // Параметры из таксономий
        if (!empty($track_data['genre_ids'])) {
            $params['igs'] = $track_data['genre_ids'];
        }
        
        if (!empty($track_data['style_ids'])) {
            $params['iss'] = $track_data['style_ids'];
        }
        
        if (!empty($track_data['instrument_ids'])) {
            $params['iis'] = $track_data['instrument_ids'];
        }
        
        if (!empty($track_data['mood_name'])) {
            $params['nm'] = $this->encode_for_url($track_data['mood_name']);
        }
        
        if (!empty($track_data['theme_name'])) {
            $params['ith'] = $this->encode_for_url($track_data['theme_name']);
        }
        
        // Дополнительные параметры
        foreach ($additional_params as $key => $value) {
            if (isset($this->fieldMap[$key])) {
                $params[$key] = $value;
            }
        }
        
        // Фильтруем пустые значения
        return array_filter($params);
    }
    
    /**
     * Чтение параметров из URL
     */
    public function read_from_url(string $url): array {
        $url_parts = parse_url($url);
        parse_str($url_parts['query'] ?? '', $params);
        
        return $this->process_url_params($params);
    }
    
    /**
     * Обработка параметров URL
     */
    private function process_url_params(array $params): array {
        $result = [
            'track_data' => [],
            'meta_data' => [],
            'errors' => []
        ];
        
        foreach ($params as $key => $value) {
            if (!isset($this->fieldMap[$key])) {
                continue;
            }
            
            $mapping = $this->fieldMap[$key];
            $processed_value = $this->process_value($value, $mapping['type']);
            
            if ($processed_value === null) {
                $result['errors'][$key] = 'Некорректное значение';
                continue;
            }
            
            // Разделяем данные
            if (in_array($key, ['it', 'iu', 'ia', 'ip', 'nt', 'ng', 'dc'])) {
                $result['track_data'][$mapping['field']] = $processed_value;
            } else {
                $result['meta_data'][$key] = $processed_value;
            }
        }
        
        return $result;
    }
    
    /**
     * Хук: очистка кэша при сохранении трека
     */
    public function clear_track_cache(int $post_id, WP_Post $post, bool $update): void {
        if ($post->post_type !== 'bm_track') {
            return;
        }
        
        $this->invalidate_cache($post_id);
        
        // Также очищаем кэш для связанных данных
        wp_cache_delete('track_stats', $this->cache_group);
        wp_cache_delete('recent_tracks', $this->cache_group);
    }
    
    /**
     * Хук: очистка кэша при удалении трека
     */
    public function clear_track_cache_on_delete(int $post_id): void {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'bm_track') {
            $this->invalidate_cache($post_id);
        }
    }
    
    /**
     * Хук: очистка кэша при обновлении терминов
     */
    public function clear_cache_on_term_update(
        int $object_id, 
        array $terms, 
        array $tt_ids, 
        string $taxonomy, 
        bool $append, 
        array $old_tt_ids
    ): void {
        $post_type = get_post_type($object_id);
        
        if ($post_type === 'bm_track') {
            $this->invalidate_cache($object_id);
        }
    }
    
    /**
     * Инвалидация кэша для трека
     */
    public function invalidate_cache(int $track_id): bool {
        $cache_key = $this->get_cache_key($track_id);
        return wp_cache_delete($cache_key, $this->cache_group);
    }
    
    /**
     * Пакетное получение треков
     */
    public function get_multiple_tracks(array $track_ids): array {
        $result = [];
        $to_fetch = [];
        
        // Пытаемся получить из кэша
        foreach ($track_ids as $track_id) {
            $cache_key = $this->get_cache_key($track_id);
            $cached = wp_cache_get($cache_key, $this->cache_group);
            
            if ($cached !== false) {
                $result[$track_id] = $cached;
            } else {
                $to_fetch[] = $track_id;
            }
        }
        
        // Получаем недостающие из базы
        if (!empty($to_fetch)) {
            $fetched = $this->fetch_multiple_tracks($to_fetch);
            
            foreach ($fetched as $track_id => $data) {
                if ($data) {
                    $cache_key = $this->get_cache_key($track_id);
                    wp_cache_set($cache_key, $data, $this->cache_group, $this->config['cache_ttl']);
                    $result[$track_id] = $data;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Получение статистики кэша
     */
    public function get_cache_stats(): array {
        if (!function_exists('wp_redis_get_info')) {
            return ['redis_available' => false];
        }
        
        $info = wp_redis_get_info();
        
        return [
            'redis_available' => true,
            'hits' => $info['keyspace_hits'] ?? 0,
            'misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => isset($info['keyspace_hits'], $info['keyspace_misses']) 
                ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2)
                : 0,
            'memory' => $info['used_memory_human'] ?? 'N/A',
            'uptime' => isset($info['uptime_in_seconds']) 
                ? human_time_diff(time() - $info['uptime_in_seconds']) 
                : 'N/A'
        ];
    }
    
    /**
     * Вспомогательные методы
     */
    private function encode_for_url(string $value): string {
        $value = str_replace(' ', '_', $value);
        return rawurlencode($value);
    }
    
    private function process_value($value, string $type) {
        if (empty($value)) {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return absint($value);
                
            case 'string':
                return sanitize_text_field($this->decode_from_url($value));
                
            case 'timestamp':
                $timestamp = absint($value);
                return ($timestamp > 0) ? date_i18n('Y-m-d H:i:s', $timestamp) : null;
                
            default:
                return $value;
        }
    }
    
    private function decode_from_url(string $value): string {
        $decoded = rawurldecode($value);
        return str_replace('_', ' ', $decoded);
    }
}
