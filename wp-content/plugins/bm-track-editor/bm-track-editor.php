<?php
/**
 * Plugin Name: BM Track Editor
 * Plugin URI: https://poetrax.ru
 * Description: Профессиональный редактор треков с интеграцией кастомных таблиц
 * Version: 1.0.0
 * Author: BestMZ
 * Text Domain: bm-track-editor
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('BM_TE_VERSION', '1.0.0');
define('BM_TE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BM_TE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BM_TE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Класс автозагрузки для плагина
 */

spl_autoload_register(function ($class) {
    $prefix = 'BM_TE_';
    $base_dir = BM_TE_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    
    // ПРОВЕРЯЕМ ВСЕ ПАПКИ
    $paths = [
        $base_dir . 'class-' . str_replace('_', '-', strtolower($relative_class)) . '.php',
        $base_dir . 'services/class-' . str_replace('_', '-', strtolower($relative_class)) . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            break;
        }
    }
});


/**
 * Инициализация плагина
 */
function bm_te_init() {
    // Загрузка текстового домена
    load_plugin_textdomain('bm-track-editor', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    require_once WP_CONTENT_DIR . '/mu-plugins/bm-core-loader.php';

    // Инициализация компонентов
    if (is_admin()) {
        BM_TE_Admin::init();
        BM_TE_Settings::init();
    }
    
    BM_TE_Ajax::init();
}
add_action('plugins_loaded', 'bm_te_init');

/**
 * Активация плагина
 */
function bm_te_activate() {
    require_once BM_TE_PLUGIN_DIR . 'includes/class-installer.php';
    BM_TE_Installer::activate();
}
register_activation_hook(__FILE__, 'bm_te_activate');

/**
 * Деактивация плагина
 */
function bm_te_deactivate() {
    require_once BM_TE_PLUGIN_DIR . 'includes/class-installer.php';
    BM_TE_Installer::deactivate();
}
register_deactivation_hook(__FILE__, 'bm_te_deactivate');