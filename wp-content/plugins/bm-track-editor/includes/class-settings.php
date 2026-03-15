<?php
/**
 * Класс для управления настройками плагина
 */
class BM_TE_Settings {
    
    public static $options;
    public static $option_name = 'bm_te_settings';
    
    /**
     * Инициализация
     */
    public static function init() {
        self::$options = get_option(self::$option_name, self::get_defaults());
        
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    /**
     * Значения по умолчанию
     */
    private static function get_defaults() {
        return [
            'tracks_per_page' => 20,
            'enable_player' => true,
            'enable_auto_slug' => true,
            'enable_poem_search' => true,
            'default_voice_gender' => 'male',
            'enable_advanced_audio' => false,
            'max_file_size' => 50, // MB
            'allowed_audio_types' => ['mp3', 'wav', 'ogg'],
            'show_preview_player' => true,
            'enable_instrument_selection' => true,
            'enable_bpm_editor' => true,
            'enable_tonality_editor' => true,
            'interface_style' => 'modern', // modern, classic, compact
            'use_media_library' => true,
            'enable_revisions' => false,
        ];
    }
    
    /**
     * Добавление страницы настроек
     */
    public static function add_settings_page() {
        add_submenu_page(
            'bm-tracks',
            __('Настройки редактора треков', 'bm-track-editor'),
            __('Настройки', 'bm-track-editor'),
            'manage_options',
            'bm-track-settings',
            [__CLASS__, 'render_settings_page']
        );
    }
    
    /**
     * Регистрация настроек
     */
    public static function register_settings() {
        register_setting(
            'bm_te_settings_group',
            self::$option_name,
            [__CLASS__, 'sanitize']
        );
        
        // Основные настройки
        add_settings_section(
            'bm_te_general',
            __('Основные настройки', 'bm-track-editor'),
            [__CLASS__, 'render_general_section'],
            'bm-track-settings'
        );
        
        add_settings_field(
            'tracks_per_page',
            __('Треков на странице', 'bm-track-editor'),
            [__CLASS__, 'render_number_field'],
            'bm-track-settings',
            'bm_te_general',
            [
                'name' => 'tracks_per_page',
                'min' => 5,
                'max' => 100,
                'step' => 5
            ]
        );
        
        add_settings_field(
            'enable_player',
            __('Встроенный плеер', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_general',
            [
                'name' => 'enable_player',
                'label' => __('Показывать плеер в редакторе', 'bm-track-editor')
            ]
        );
        
        add_settings_field(
            'enable_auto_slug',
            __('Автоматический URL', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_general',
            [
                'name' => 'enable_auto_slug',
                'label' => __('Генерировать slug из названия', 'bm-track-editor')
            ]
        );
        
        // Настройки интерфейса
        add_settings_section(
            'bm_te_interface',
            __('Настройки интерфейса', 'bm-track-editor'),
            [__CLASS__, 'render_interface_section'],
            'bm-track-settings'
        );
        
        add_settings_field(
            'interface_style',
            __('Стиль интерфейса', 'bm-track-editor'),
            [__CLASS__, 'render_select_field'],
            'bm-track-settings',
            'bm_te_interface',
            [
                'name' => 'interface_style',
                'options' => [
                    'modern' => __('Современный', 'bm-track-editor'),
                    'classic' => __('Классический', 'bm-track-editor'),
                    'compact' => __('Компактный', 'bm-track-editor')
                ]
            ]
        );
        
        add_settings_field(
            'show_preview_player',
            __('Предпросмотр', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_interface',
            [
                'name' => 'show_preview_player',
                'label' => __('Показывать плеер в списке треков', 'bm-track-editor')
            ]
        );
        
        // Настройки аудио
        add_settings_section(
            'bm_te_audio',
            __('Настройки аудио', 'bm-track-editor'),
            [__CLASS__, 'render_audio_section'],
            'bm-track-settings'
        );
        
        add_settings_field(
            'max_file_size',
            __('Максимальный размер файла (MB)', 'bm-track-editor'),
            [__CLASS__, 'render_number_field'],
            'bm-track-settings',
            'bm_te_audio',
            [
                'name' => 'max_file_size',
                'min' => 1,
                'max' => 500,
                'step' => 1
            ]
        );
        
        add_settings_field(
            'allowed_audio_types',
            __('Разрешённые форматы', 'bm-track-editor'),
            [__CLASS__, 'render_multicheck_field'],
            'bm-track-settings',
            'bm_te_audio',
            [
                'name' => 'allowed_audio_types',
                'options' => [
                    'mp3' => 'MP3',
                    'wav' => 'WAV',
                    'ogg' => 'OGG',
                    'm4a' => 'M4A',
                    'flac' => 'FLAC'
                ]
            ]
        );
        
        add_settings_field(
            'use_media_library',
            __('Медиабиблиотека', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_audio',
            [
                'name' => 'use_media_library',
                'label' => __('Использовать медиабиблиотеку WordPress', 'bm-track-editor')
            ]
        );
        
        // Расширенные настройки
        add_settings_section(
            'bm_te_advanced',
            __('Расширенные настройки', 'bm-track-editor'),
            [__CLASS__, 'render_advanced_section'],
            'bm-track-settings'
        );
        
        add_settings_field(
            'enable_instrument_selection',
            __('Инструменты', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_advanced',
            [
                'name' => 'enable_instrument_selection',
                'label' => __('Включить выбор инструментов', 'bm-track-editor')
            ]
        );
        
        add_settings_field(
            'enable_bpm_editor',
            __('BPM редактор', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_advanced',
            [
                'name' => 'enable_bpm_editor',
                'label' => __('Включить редактирование BPM', 'bm-track-editor')
            ]
        );
        
        add_settings_field(
            'enable_revisions',
            __('История изменений', 'bm-track-editor'),
            [__CLASS__, 'render_checkbox_field'],
            'bm-track-settings',
            'bm_te_advanced',
            [
                'name' => 'enable_revisions',
                'label' => __('Сохранять историю изменений', 'bm-track-editor')
            ]
        );
    }
    
    /**
     * Санитизация данных
     */
    public static function sanitize($input) {
        $output = self::get_defaults();
        
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'tracks_per_page':
                    $output[$key] = absint($value);
                    break;
                    
                case 'max_file_size':
                    $output[$key] = absint($value);
                    break;
                    
                case 'allowed_audio_types':
                    $output[$key] = is_array($value) ? $value : [];
                    break;
                    
                case 'interface_style':
                    $output[$key] = in_array($value, ['modern', 'classic', 'compact']) ? $value : 'modern';
                    break;
                    
                default:
                    $output[$key] = $value === '1' ? true : false;
            }
        }
        
        return $output;
    }
    
    /**
     * Получить значение настройки
     */
    public static function get($key, $default = null) {
        if (self::$options === null) {
            self::$options = get_option(self::$option_name, self::get_defaults());
        }
        
        return isset(self::$options[$key]) ? self::$options[$key] : $default;
    }
    
    /**
     * Рендеринг страницы настроек
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap bm-te-settings">
            <h1><?php _e('Настройки редактора треков', 'bm-track-editor'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('bm_te_settings_group');
                do_settings_sections('bm-track-settings');
                submit_button();
                ?>
            </form>
            
            <div class="bm-te-info-box">
                <h3><?php _e('Информация о системе', 'bm-track-editor'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <tr>
                        <td><strong><?php _e('Версия плагина', 'bm-track-editor'); ?></strong></td>
                        <td><?php echo BM_TE_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Таблица треков', 'bm-track-editor'); ?></strong></td>
                        <td><?php echo BM_TE_TABLE_TRACK; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Всего треков', 'bm-track-editor'); ?></strong></td>
                        <td><?php echo BM_TE_Admin::get_tracks_count(); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Поддержка FULLTEXT', 'bm-track-editor'); ?></strong></td>
                        <td><?php echo BM_TE_Admin::check_fulltext_index() ? '✅' : '❌'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <style>
            .bm-te-settings {
                max-width: 1200px;
            }
            
            .bm-te-info-box {
                margin-top: 30px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }
            
            .bm-te-info-box h3 {
                margin-top: 0;
            }
            
            .form-table th {
                width: 250px;
            }
        </style>
        <?php
    }
    
    /**
     * Рендеринг полей
     */
    public static function render_general_section() {
        echo '<p>' . __('Основные настройки работы редактора.', 'bm-track-editor') . '</p>';
    }
    
    public static function render_interface_section() {
        echo '<p>' . __('Настройки внешнего вида интерфейса.', 'bm-track-editor') . '</p>';
    }
    
    public static function render_audio_section() {
        echo '<p>' . __('Настройки загрузки и обработки аудиофайлов.', 'bm-track-editor') . '</p>';
    }
    
    public static function render_advanced_section() {
        echo '<p>' . __('Расширенные функции редактора.', 'bm-track-editor') . '</p>';
    }
    
    public static function render_number_field($args) {
        $name = $args['name'];
        $value = self::get($name, 20);
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 100;
        $step = $args['step'] ?? 1;
        
        printf(
            '<input type="number" name="%s[%s]" value="%s" min="%d" max="%d" step="%d" class="small-text">',
            self::$option_name,
            $name,
            $value,
            $min,
            $max,
            $step
        );
    }
    
    public static function render_checkbox_field($args) {
        $name = $args['name'];
        $value = self::get($name, false);
        $label = $args['label'] ?? '';
        
        printf(
            '<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>',
            self::$option_name,
            $name,
            checked($value, true, false),
            $label
        );
    }
    
    public static function render_select_field($args) {
        $name = $args['name'];
        $value = self::get($name);
        $options = $args['options'];
        
        printf('<select name="%s[%s]">', self::$option_name, $name);
        
        foreach ($options as $opt_value => $opt_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                $opt_value,
                selected($value, $opt_value, false),
                $opt_label
            );
        }
        
        echo '</select>';
    }
    
    public static function render_multicheck_field($args) {
        $name = $args['name'];
        $selected = self::get($name, []);
        $options = $args['options'];
        
        foreach ($options as $opt_value => $opt_label) {
            printf(
                '<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="%s[%s][]" value="%s" %s> %s</label>',
                self::$option_name,
                $name,
                $opt_value,
                checked(in_array($opt_value, $selected), true, false),
                $opt_label
            );
        }
    }

    public static function get_all() {
        if (self::$options === null) {
            self::$options = get_option(self::$option_name, self::get_defaults());
        }
        return self::$options;
    }
}