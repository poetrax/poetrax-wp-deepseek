<?php
//Установка и обновление плагина

class BM_TE_Installer {
    
   // Активация плагина
  
    public static function activate() {
        self::check_tables();
        self::create_default_options();
        self::create_upload_directory();
        self::schedule_cron();
        
        flush_rewrite_rules();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BM Track Editor: плагин активирован');
        }
    }
    
    //Деактивация плагина
  
    public static function deactivate() {
        self::clear_cron();
        flush_rewrite_rules();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BM Track Editor: плагин деактивирован');
        }
    }
    
    // Проверка наличия таблиц
 
    private static function check_tables() {
        global $wpdb;
        
        $required_tables = [
            BM_TE_TABLE_TRACK,
            BM_TE_TABLE_POEM,
            BM_TE_TABLE_POET,
            BM_TE_TABLE_MUSIC_DETAIL,
            'bm_ctbl000_mood',
            'bm_ctbl000_theme',
            'bm_ctbl000_music_genre',
        ];
        
        $missing = [];
        
        foreach ($required_tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($result !== $table) {
                $missing[] = $table;
            }
        }
        
        if (!empty($missing)) {
            update_option('bm_te_missing_tables', $missing);
            error_log('BM Track Editor: Отсутствуют таблицы - ' . implode(', ', $missing));
        } else {
            delete_option('bm_te_missing_tables');
        }
    }
    
    // Создание опций по умолчанию
   
    private static function create_default_options() {
        $defaults = [
            'tracks_per_page' => 20,
            'enable_player' => true,
            'enable_auto_slug' => true,
            'enable_poem_search' => true,
            'default_voice_gender' => 'male',
            'max_file_size' => 50,
            'allowed_audio_types' => ['mp3', 'wav', 'ogg'],
            'show_preview_player' => true,
            'enable_instrument_selection' => true,
            'enable_bpm_editor' => true,
            'interface_style' => 'modern',
            'use_media_library' => true,
        ];
        
        if (!get_option('bm_te_settings')) {
            add_option('bm_te_settings', $defaults);
        }
        
        if (!get_option('bm_te_version')) {
            add_option('bm_te_version', BM_TE_VERSION);
        }
        
        if (!get_option('bm_te_installed')) {
            add_option('bm_te_installed', current_time('mysql'));
        }
    }
    
   // Создание директории для загрузок
 
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $bm_dir = $upload_dir['basedir'] . '/bm-audio';
        
        if (!file_exists($bm_dir)) {
            wp_mkdir_p($bm_dir);
            
            // Создаем .htaccess для защиты
            $htaccess = $bm_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Deny from all");
            }
            
            // Создаем index.php для защиты
            $index = $bm_dir . '/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, "<?php // Silence is golden");
            }
        }
    }
    
   // Планирование cron задач
   
    private static function schedule_cron() {
        if (!wp_next_scheduled('bm_te_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'bm_te_daily_cleanup');
        }
    }
    
   
    //Очистка cron задач
   
    private static function clear_cron() {
        $timestamp = wp_next_scheduled('bm_te_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'bm_te_daily_cleanup');
        }
    }
}