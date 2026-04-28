<?php
class TrackRewriteRules {
    
    public function __construct() {
        // Инициализация хуков
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_track_redirect']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('generate_rewrite_rules', [$this, 'add_custom_rewrite_rules']);
        
        // Для админки
        add_action('admin_init', [$this, 'flush_rewrite_rules_if_needed']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
   
    /**
     * Добавление rewrite rules
     */
    public function add_rewrite_rules() {
        // Основное правило для треков
        add_rewrite_rule('^t/([0-9]+)/?$','index.php?track_id=$matches[1]','top');
        
        // Для track_slug
        add_rewrite_rule('^t/([^/]+)/?$','index.php?track_slug=$matches[1]','top');

        // С дополнительными параметрами
        add_rewrite_rule('^t/([0-9]+)/([^/]+)/?$','index.php?track_id=$matches[1]&track_slug=$matches[2]','top');
        
        // Для категорий треков
        add_rewrite_rule('^t/category/([^/]+)/?$','index.php?track_category=$matches[1]','top');
        
        add_rewrite_rule('^t/category/([^/]+)/page/([0-9]+)/?$','index.php?track_category=$matches[1]&paged=$matches[2]','top');
        
        // Для поиска треков
        add_rewrite_rule('^t/search/([^/]+)/?$','index.php?track_search=$matches[1]','top');
        
        add_rewrite_rule('^t/search/([^/]+)/page/([0-9]+)/?$','index.php?track_search=$matches[1]&paged=$matches[2]','top');
    }
    
    /**
     * Добавление query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'track_id';
        $vars[] = 'track_slug';
        $vars[] = 'track_category';
        $vars[] = 'track_search';
        $vars[] = 'track_action'; // play, download, share и т.д.
        $vars[] = 'track_format'; // mp3, wav и т.д.
        
        return $vars;
    }
    
    /**
     * Обработка редиректа на трек
     */
    public function handle_track_redirect() {
        global $wp_query;
        
        $track_id = get_query_var('track_id');
        
        if (!$track_id) {
            return;
        }
        
        // Валидация track_id
        $track_id = absint($track_id);
        if ($track_id <= 0) {
            $this->show_404();
            return;
        }
        
        // Проверяем существование трека
        $track_exists = $this->check_track_exists($track_id);
        
        if (!$track_exists) {
            $this->show_404();
            return;
        }
        
        // Получаем данные трека
        $track_data = $this->get_track_data($track_id);
        
        if (is_wp_error($track_data)) {
            $this->show_404();
            return;
        }
        
        // Проверяем slug (если есть в URL)
        $track_slug = get_query_var('track_slug');
        if ($track_slug) {
            $expected_slug = $this->generate_track_slug($track_data);
            
            if ($track_slug !== $expected_slug) {
                // Делаем 301 редирект на правильный URL
                $correct_url = $this->get_track_url($track_id, $track_data);
                wp_redirect($correct_url, 301);
                exit;
            }
        }
        
        // Рендерим страницу трека
        $this->render_track_page($track_data);
        exit;
    }
    
    /**
     * Проверка существования трека
     */
    private function check_track_exists($track_id) {
      
        
        $table_name = 'bm_ctbl000_track';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND status = 'completed'",
            $track_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Получение данных трека
     */
    private function get_track_data($track_id) {
        // Используем кэширование
        $cache_key = 'track_data_' . $track_id;
        $cached = wp_cache_get($cache_key, 'tracks');
        
        if ($cached !== false) {
            return $cached;
        }
        
       
        $table_name = 'bm_ctbl000_track';
        
        $track = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $track_id
        ), ARRAY_A);
        
        if (!$track) {
            return new WP_Error('track_not_found', 'Track not found');
        }
        
        // Обогащаем данные
        $track = $this->enrich_track_data($track);
        
        // Кэшируем на 1 час
        wp_cache_set($cache_key, $track, 'tracks', HOUR_IN_SECONDS);
        
        return $track;
    }
    
    /**
     * Генерация slug для трека
     */
    private function generate_track_slug($track_data) {
        $slug = sanitize_title($track_data['track_name']);
        
        // Добавляем ID для уникальности
        $slug .= '-' . $track_data['id'];
        
        return $slug;
    }
    
    /**
     * Получение URL трека
     */
    public function get_track_url($track_id, $track_data = null) {
        if (!$track_data) {
            $track_data = $this->get_track_data($track_id);
        }
        
        if (is_wp_error($track_data)) {
            return home_url('/t/' . $track_id);
        }
        
        $slug = $this->generate_track_slug($track_data);
        
        return home_url('/t/' . $track_id . '/' . $slug . '/');
    }
    
    /**
     * Рендеринг страницы трека
     */
    private function render_track_page($track_data) {
        // Устанавливаем заголовки
        status_header(200);
        
        // Устанавливаем мета-теги
        add_filter('wp_title', function($title) use ($track_data) {
            return $track_data['track_name'] . ' - ' . $track_data['poet_name'] . ' | ' . get_bloginfo('name');
        });
        
        add_action('wp_head', function() use ($track_data) {
            $this->output_track_meta_tags($track_data);
        });
        
        // Загружаем наш кастомный шаблон
        $template = locate_template(['single-track.php']);
        
        if (!$template) {
            // Используем дефолтный шаблон
            $template = __DIR__ . '/templates/single-track.php';
        }
        
        // Передаем данные в шаблон
        $GLOBALS['track_data'] = $track_data;
        
        // Рендерим
        include $template;
    }
    
    /**
     * Вывод мета-тегов для трека
     */
    private function output_track_meta_tags($track_data) {
        ?>
        <!-- Open Graph мета-теги -->
        <meta property="og:title" content="<?php echo esc_attr($track_data['track_name']); ?>">
        <meta property="og:description" content="<?php echo esc_attr($track_data['track_theme'] ?? ''); ?>">
        <meta property="og:url" content="<?php echo esc_url($this->get_track_url($track_data['id'], $track_data)); ?>">
        <meta property="og:type" content="music.song">
        
        <?php if (!empty($track_data['image_url'])): ?>
        <meta property="og:image" content="<?php echo esc_url($track_data['image_url']); ?>">
        <?php endif; ?>
        
        <?php if (!empty($track_data['listen_url'])): ?>
        <meta property="og:audio" content="<?php echo esc_url($track_data['listen_url']); ?>">
        <meta property="og:audio:type" content="audio/mpeg">
        <?php endif; ?>
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="player">
        <meta name="twitter:title" content="<?php echo esc_attr($track_data['track_name']); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr(substr($track_data['track_theme'] ?? '', 0, 200)); ?>">
        
        <?php if (!empty($track_data['image_url'])): ?>
        <meta name="twitter:image" content="<?php echo esc_url($track_data['image_url']); ?>">
        <?php endif; ?>
        
        <?php if (!empty($track_data['listen_url'])): ?>
        <meta name="twitter:player" content="<?php echo esc_url($track_data['listen_url']); ?>">
        <meta name="twitter:player:width" content="480">
        <meta name="twitter:player:height" content="120">
        <?php endif; ?>
        
        <!-- Canonical URL -->
        <link rel="canonical" href="<?php echo esc_url($this->get_track_url($track_data['id'], $track_data)); ?>">
        
        <!-- JSON-LD структурированные данные -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "MusicComposition",
            "name": "<?php echo esc_attr($track_data['track_name']); ?>",
            "composer": {
                "@type": "Person",
                "name": "<?php echo esc_attr($track_data['poet_name'] ?? ''); ?>"
            },
            "url": "<?php echo esc_url($this->get_track_url($track_data['id'], $track_data)); ?>",
            "datePublished": "<?php echo esc_attr($track_data['created_at']); ?>"
            <?php if (!empty($track_data['duration_formatted'])): ?>,
            "duration": "<?php echo esc_attr($track_data['duration_formatted']); ?>"
            <?php endif; ?>
        }
        </script>
        <?php
    }
    
    /**
     * Показ 404 страницы
     */
    private function show_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        
        // Загружаем 404 шаблон
        $template = locate_template(['404.php']);
        if ($template) {
            include $template;
        } else {
            // Простой fallback
            echo '<h1>Трек не найден</h1>';
            echo '<p>Запрошенный трек не существует или был удален.</p>';
        }
        
        exit;
    }
    
    /**
     * Обогащение данных трека
     */
    private function enrich_track_data($track) {
        // Форматирование длительности
        $track['duration_formatted'] = $this->format_duration($track['track_duration']);
        
        // Форматирование размера файла
        $track['file_size_formatted'] = size_format($track['track_file_size'], 2);
        
        // Генерация URL для прослушивания и скачивания
        $track['listen_url'] = $this->get_media_url($track, 'listen');
        $track['download_url'] = $this->get_media_url($track, 'download');
        
        // Получение изображения
        if (!empty($track['img_id'])) {
            $image = wp_get_attachment_image_src($track['img_id'], 'full');
            $track['image_url'] = $image[0] ?? '';
        }
        
        // Получение дополнительных данных
        $track['genres'] = $this->get_track_terms($track['id'], 'genre');
        $track['styles'] = $this->get_track_terms($track['id'], 'style');
        $track['instruments'] = $this->get_track_terms($track['id'], 'instrument');
        
        return $track;
    }
    
    /**
     * Получение терминов для трека
     */
    private function get_track_terms($track_id, $taxonomy) {
        // Если используете кастомную таблицу
   
        
        $table_name = 'bm_ctbl000_track_' . $taxonomy . 's';
        $term_table = 'bm_ctbl000_' . $taxonomy . 's';
        
        $terms = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$term_table} t
             INNER JOIN {$table_name} rel ON rel.{$taxonomy}_id = t.id
             WHERE rel.track_id = %d
             ORDER BY t.name",
            $track_id
        ), ARRAY_A);
        
        return $terms;
    }
    
    /**
     * Получение URL медиа-файла
     */
    private function get_media_url($track, $type = 'listen') {
        if (empty($track['track_path'])) {
            return '';
        }
        
        $base_url = 'https://media.bestmz.com/';
        $filename = basename($track['track_path']);
        
        switch ($type) {
            case 'download':
                return $base_url . 'download/' . $filename;
            case 'listen':
            default:
                return $base_url . 'stream/' . $filename;
        }
    }
    
    /**
     * Форматирование длительности
     */
    private function format_duration($seconds) {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    /**
     * Перегенерация rewrite rules при необходимости
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('track_rewrite_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('track_rewrite_rules_flushed', '1');
        }
    }
    
    /**
     * Добавление кастомных rewrite rules для WP_Rewrite
     */
    public function add_custom_rewrite_rules($wp_rewrite) {
        $new_rules = [
            't/([0-9]+)/?$' => 'index.php?track_id=' . $wp_rewrite->preg_index(1),
            't/([0-9]+)/([^/]+)/?$' => 'index.php?track_id=' . $wp_rewrite->preg_index(1) . '&track_slug=' . $wp_rewrite->preg_index(2),
            't/category/([^/]+)/?$' => 'index.php?track_category=' . $wp_rewrite->preg_index(1),
            't/category/([^/]+)/page/([0-9]+)/?$' => 'index.php?track_category=' . $wp_rewrite->preg_index(1) . '&paged=' . $wp_rewrite->preg_index(2),
        ];
        
        $wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);
    }
    
    /**
     * Регистрация REST API маршрутов
     */
    public function register_rest_routes() {
        register_rest_route('bestmz/v1', '/track/url', [
            'methods' => 'GET',
            'callback' => [$this, 'get_track_url_api'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => 'is_numeric'
                ]
            ]
        ]);
        
        register_rest_route('bestmz/v1', '/track/redirect', [
            'methods' => 'GET',
            'callback' => [$this, 'redirect_to_track'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * API для получения URL трека
     */
    public function get_track_url_api(WP_REST_Request $request) {
        $track_id = $request->get_param('id');
        $url = $this->get_track_url($track_id);
        
        return new WP_REST_Response([
            'success' => true,
            'url' => $url,
            'short_url' => wp_get_shortlink($track_id),
        ], 200);
    }
    
    /**
     * Редирект на трек (для старых URL)
     */
    public function redirect_to_track(WP_REST_Request $request) {
        $track_id = $request->get_param('track_id');
        $url = $this->get_track_url($track_id);
        
        wp_redirect($url, 301);
        exit;
    }
}

// Инициализация
new TrackRewriteRules();
