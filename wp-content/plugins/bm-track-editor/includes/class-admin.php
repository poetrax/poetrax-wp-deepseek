<?php
use BM\Services\StatsService;

/**
 * Административный интерфейс плагина
 */
class BM_TE_Admin {
    

    /**
     * Инициализация
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_pages']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_filter('admin_body_class', [__CLASS__, 'add_body_class']);
    }
    
    /**
     * Добавление страниц в админку
     */
    public static function add_admin_pages() {
    // Главная страница со списком треков
        add_menu_page(
            __('Управление треками', 'bm-track-editor'),
            __('Треки', 'bm-track-editor'),
            'edit_posts',
            'bm-tracks',
            [__CLASS__, 'render_track_list'],
            'dashicons-format-audio',
            25
        );
        
        // Страница добавления/редактирования
        add_submenu_page(
            'bm-tracks',
            __('Редактор трека', 'bm-track-editor'),
            __('Новый трек', 'bm-track-editor'),
            'edit_posts',
            'bm-track-editor',
            [__CLASS__, 'render_track_editor']
        );
        
        // Страница со стихами
        add_submenu_page(
            'bm-tracks',
            __('Стихотворения', 'bm-track-editor'),
            __('Стихи', 'bm-track-editor'),
            'edit_posts',
            'bm-poems',
            [__CLASS__, 'render_poem_list']
        );
        
        // Страница с поэтами
        add_submenu_page(
            'bm-tracks',
            __('Поэты', 'bm-track-editor'),
            __('Поэты', 'bm-track-editor'),
            'edit_posts',
            'bm-poets',
            [__CLASS__, 'render_poet_list']
        );
      

        // Страница статистики
        add_submenu_page(
            'bm-tracks',
            __('Статистика', 'bm-track-editor'),
            __('Статистика', 'bm-track-editor'),
            'manage_options',
            'bm-stats',
            [__CLASS__, 'render_stats']
        );

         // Страница комменарии
        add_submenu_page(
            'bm-tracks',
            __('Комменарии', 'bm-track-editor'),
            __('Комменарии', 'bm-track-editor'),
            'manage_options',
            'bm-comments',
            [__CLASS__, 'render_track_comments']
        );


        // Настройки (добавляются из класса Settings)
    }
   

    /**
     * Рендеринг списка треков
     */
    public static function render_track_list() {
        $tracks_per_page = BM_TE_Settings::get('tracks_per_page', 20);
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $tracks_per_page;
        
        // Получение треков
        global $wpdb;
        $tracks = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.short_name as poet_name 
            FROM " . BM_TE_TABLE_TRACK . " t
            LEFT JOIN " . BM_TE_TABLE_POET . " p ON t.poet_id = p.id
            WHERE 1=1
            ORDER BY t.created_at DESC
            LIMIT %d OFFSET %d",
            $tracks_per_page,
            $offset
        ));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM " . BM_TE_TABLE_TRACK);
        $total_pages = ceil($total / $tracks_per_page);
        
        include BM_TE_PLUGIN_DIR . 'admin/partials/track-list.php';
    }
    
    /**
     * Рендеринг редактора трека
     */
    public static function render_track_editor() {
        $track_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Получение данных для выпадающих списков
        global $wpdb;
        
        $poets = $wpdb->get_results("SELECT id, short_name FROM " . BM_TE_TABLE_POET . " WHERE is_active = 1 ORDER BY last_name");
        
        $moods = $wpdb->get_results("SELECT * FROM bm_ctbl000_mood WHERE is_active = 1");
        $themes = $wpdb->get_results("SELECT * FROM bm_ctbl000_theme WHERE is_active = 1");
        $genres = $wpdb->get_results("SELECT * FROM bm_ctbl000_music_genre WHERE is_active = 1");
        $instruments = $wpdb->get_results("SELECT * FROM bm_ctbl000_music_instrument WHERE is_active = 1");
        
        include BM_TE_PLUGIN_DIR . 'admin/partials/track-editor.php';
    }

    /**
    * Рендеринг страницы статистики
    */
    public static function render_stats() {
        // Проверяем права доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('Доступ запрещен', 'bm-track-editor'));
        }
    
        // Получаем статистику
        $global_stats = StatsService::get_global_stats();
    
        // Подключаем шаблон
        include BM_TE_PLUGIN_DIR . 'admin/partials/stats.php';
    }


 /**
 * Рендеринг списка стихов
 */
public static function render_poem_list() {
    // Получение стихов с пагинацией
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    global $wpdb;
    $poems = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, pt.short_name as poet_name 
        FROM " . BM_TE_TABLE_POEM . " p
        LEFT JOIN " . BM_TE_TABLE_POET . " pt ON p.poet_id = pt.id
        WHERE p.is_active = 1
        ORDER BY p.name
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM " . BM_TE_TABLE_POEM . " WHERE is_active = 1");
    $total_pages = ceil($total / $per_page);
    
    include BM_TE_PLUGIN_DIR . 'admin/partials/poem-list.php';
}

/**
 * Рендеринг списка поэтов
 */
public static function render_poet_list() {
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    global $wpdb;
    $poets = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . BM_TE_TABLE_POET . " 
        WHERE is_active = 1 
        ORDER BY last_name, first_name
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM " . BM_TE_TABLE_POET . " WHERE is_active = 1");
    $total_pages = ceil($total / $per_page);
    
    include BM_TE_PLUGIN_DIR . 'admin/partials/poet-list.php';
}
/**
 * Рендеринг вкладок в редакторе трека
 */
public static function render_track_tabs($track_id) {
    ?>
    <h2 class="nav-tab-wrapper">
        <a href="#main" class="nav-tab nav-tab-active">Основное</a>
        <a href="#comments" class="nav-tab">Комментарии</a>
        <a href="#stats" class="nav-tab">Статистика</a>
    </h2>
    
    <div id="main" class="tab-content active">
        <?php self::render_track_editor_main($track_id); ?>
    </div>
    
    <div id="comments" class="tab-content">
        <?php self::render_track_comments($track_id); ?>
    </div>
    
    <div id="stats" class="tab-content">
        <?php self::render_track_stats($track_id); ?>
    </div>
    <?php
}

/**
 * Рендеринг комментариев к треку
 */
public static function render_track_comments($track_id) {
    $comment_service = new \BM\Services\CommentService();
    $comments = $comment_service->getTree($track_id);
    $stats = $comment_service->getStats($track_id);
    
    include BM_TE_PLUGIN_DIR . 'admin/partials/track-comments.php';
}
    
    /**
     * Подключение стилей и скриптов
     */
    public static function enqueue_assets($hook) {
        // Только на страницах плагина
        if (strpos($hook, 'bm-') === false && strpos($hook, 'bm-track') === false) {
            return;
        }
        
        // Основные стили
        wp_enqueue_style(
            'bm-te-admin',
            BM_TE_PLUGIN_URL . 'admin/css/admin.css',
            [],
            BM_TE_VERSION
        );
        
        // Стиль интерфейса из настроек
        $style = BM_TE_Settings::get('interface_style', 'modern');
        if ($style !== 'modern') {
            wp_enqueue_style(
                'bm-te-style-' . $style,
                BM_TE_PLUGIN_URL . 'admin/css/style-' . $style . '.css',
                ['bm-te-admin'],
                BM_TE_VERSION
            );
        }
        
        // Подключаем медиабиблиотеку если нужно
        if (BM_TE_Settings::get('use_media_library', true)) {
            wp_enqueue_media();
        }
        
        // Основной скрипт
        wp_enqueue_script(
            'bm-te-admin',
            BM_TE_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            BM_TE_VERSION,
            true
        );
        
        // Передача данных в JavaScript
        wp_localize_script('bm-te-admin', 'bmTE', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bm_te_nonce'),
            'settings' => BM_TE_Settings::get_all(),
            'strings' => [
                'save_success' => __('Трек сохранен', 'bm-track-editor'),
                'save_error' => __('Ошибка сохранения', 'bm-track-editor'),
                'confirm_delete' => __('Удалить этот трек?', 'bm-track-editor'),
                'upload' => __('Загрузить файл', 'bm-track-editor'),
                'search_poems' => __('Поиск стихов...', 'bm-track-editor'),
            ]
        ]);
    }
    
    /**
     * Добавление класса к body
     */
    public static function add_body_class($classes) {
        if (strpos(get_current_screen()->id, 'bm-') !== false) {
            $classes .= ' bm-te-admin bm-te-style-' . BM_TE_Settings::get('interface_style', 'modern');
        }
        return $classes;
    }
    
    /**
     * Получение количества треков
     */
    public static function get_tracks_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM " . BM_TE_TABLE_TRACK);
    }
    
    /**
     * Проверка наличия FULLTEXT индексов
     */
    public static function check_fulltext_index() {
        global $wpdb;
        $result = $wpdb->get_results("SHOW INDEX FROM " . BM_TE_TABLE_TRACK . " WHERE Index_type = 'FULLTEXT'");
        return !empty($result);
    }
}