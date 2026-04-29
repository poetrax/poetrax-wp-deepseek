<?php
/**
 * Plugin Name: BM Core Loader (Claude)
 * Description: Загружает ядро BM Core для Poetrax
 * Version: 1.0.0
 * Author: Poetrax
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Загружаем WordPress-совместимый слой
require_once __DIR__ . '/bm-shim/wordpress-shim.php';

// Пока просто тестовое сообщение
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BM Core Loader (Claude): загружен, shim активен');
    }
});

// Здесь потом будет подключение ядра
// require_once __DIR__ . '/bm-core/bootstrap.php';


/**
 * BM Core - Must-Use Plugin Loader

if (!defined('ABSPATH')) exit;

define('BM_CORE_PATH', WP_CONTENT_DIR . '/bm-core/');
define('BM_CORE_URL', WP_CONTENT_URL . '/bm-core/');
define('BM_CACHE_GROUP', 'bm_core');
define('BM_CACHE_TTL', 3600);

// PSR-4 АВТОЗАГРУЗКА
spl_autoload_register(function($class) {
    // Пространство имен BM\
    $prefix = 'BM\\';
    $base_dir = BM_CORE_PATH;
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});


// Инициализация с неймспейсом
add_action('plugins_loaded', function() {
    BM\Database\this->connection->init();
    BM\Database\this->cache->init();

});

// Регистрация таксономии
add_action('init', ['BM\Taxonomies\EntityTypeTaxonomy', 'register']);

//Теперь нужно научить WordPress понимать эти адреса
add_action('init', 'bm_custom_rewrite_rules');
function bm_custom_rewrite_rules() {
    add_rewrite_rule('^poet/([^/]+)/?$', 'index.php?bm_poet=$matches[1]', 'top');
    add_rewrite_rule('^poem/([^/]+)/?$', 'index.php?bm_poem=$matches[1]', 'top');
    add_rewrite_rule('^track/([0-9]+)/?$', 'index.php?bm_track_id=$matches[1]', 'top');
    add_rewrite_rule('^track/([^/]+)/?$', 'index.php?bm_track_slug=$matches[1]', 'top');

}

add_filter('query_vars', 'bm_query_vars');
function bm_query_vars($vars) {
    $vars[] = 'bm_poet';
    $vars[] = 'bm_poem';
    $vars[] = 'bm_track_id';
    $vars[] = 'bm_track_slug';
    return $vars;
}

add_filter('template_include', 'bm_template_include', 99);
function bm_template_include($template) {
    $poet_slug = get_query_var('bm_poet');
    $poem_slug = get_query_var('bm_poem');
    $track_id = get_query_var('bm_track_id');
    $track_slug = get_query_var('bm_track_slug');

    if ($poet_slug) {
        $poet_repo = new BM\Repositories\PoetRepository();
        $poet = $poet_repo->findBySlug($poet_slug);
        if ($poet) {
            set_query_var('bm_poet', $poet);
            return BM_CORE_PATH . 'Templates/poet-page.php';
        } else {
            return bm_handle_404();
        }
    }

    if ($poem_slug) {
        $poem_repo = new BM\Repositories\PoemRepository();
        $poem = $poem_repo->findBySlug($poem_slug);
        if ($poem) {
            set_query_var('bm_poem', $poem);
            return BM_CORE_PATH . 'Templates/poem-page.php';
        } else {
            return bm_handle_404();
        }
    }
    
    if ($track_id || $track_slug) {
        $track_repo = new BM\Repositories\TrackRepository();

        $bm_track='bm_track_id';

        if ($track_slug) { 

             $track = $track_repo->findBySlug($track_slug);
             $bm_track='bm_track_slug';

        } else if ($track_id) {
            $track = $track_repo->find($track_id);
        }

        if ($track) {
            set_query_var($bm_track, $track);
            return BM_CORE_PATH . 'Templates/track-page.php';
        } else {
            return bm_handle_404();
        }
    }

    return $template;

}

function bm_handle_404() {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return get_404_template();
}

//Теперь в любом месте можно вывести карточку одним вызовом: echo bm_render_card($item);
function bm_render_card($item) {
    ob_start();
    include BM_CORE_PATH . 'Templates/card.php';
    return ob_get_clean();
}

 */
// Автозагрузка классов BM Core
spl_autoload_register(function ($class) {
    $prefix = 'BM\\Core\\';
    $base_dir = __DIR__ . '/bm-core/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Инициализация при загрузке WordPress
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BM Core: Database layer loaded');
    }
});
