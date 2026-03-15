<?php
/**
 * WordPress Compatibility Shim
 * 
 * Этот файл эмулирует основные функции WordPress для работы вне WP
 * Версия: 1.0.0
 */

// Предотвращаем прямой вызов
if (!defined('ABSPATH') && !defined('BM_SHIM_LOADED')) {
    define('BM_SHIM_LOADED', true);
}

// =============================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЯМИ
// =============================================

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        return 0;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return get_current_user_id() > 0;
    }
}

// =============================================
// ФУНКЦИИ ДЛЯ САНИТАЙЗАЦИИ
// =============================================

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        if (is_object($str) || is_array($str)) {
            return '';
        }
        $str = trim($str);
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        return $str;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '#';
    }
}

// =============================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С БД
// =============================================

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        static $upload_dir = null;
        
        if ($upload_dir === null) {
            $base_dir = defined('UPLOADS_DIR') ? UPLOADS_DIR : ABSPATH . 'uploads';
            $base_url = defined('UPLOADS_URL') ? UPLOADS_URL : home_url('uploads');
            
            $upload_dir = [
                'path' => $base_dir,
                'url' => $base_url,
                'subdir' => '',
                'basedir' => $base_dir,
                'baseurl' => $base_url,
                'error' => false
            ];
        }
        
        return $upload_dir;
    }
}

// =============================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $args = get_object_vars($args);
        }
        if (is_array($args)) {
            return array_merge($defaults, $args);
        }
        return $defaults;
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        static $base_url = null;
        
        if ($base_url === null) {
            $base_url = defined('SITE_URL') ? SITE_URL : 'http://localhost';
        }
        
        return rtrim($base_url, '/') . '/' . ltrim($path, '/');
    }
}

// Логирование для отладки
if (!function_exists('error_log')) {
    function error_log($message) {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL, FILE_APPEND);
    }
}