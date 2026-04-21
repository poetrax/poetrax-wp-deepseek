<?php
// inc/setup.php
// Таксономии, константы, инициализация
// Регистрация таксономии "Поэты"
function register_poets_taxonomy() {
    register_taxonomy('poet', 'post', [
        'labels' => [
            'name' => 'Поэты',
            'singular_name' => 'Поэт',
            'menu_name' => 'Поэты',
            'all_items' => 'Все поэты',
            'edit_item' => 'Редактировать поэта',
            'view_item' => 'Смотреть поэта',
            'update_item' => 'Обновить поэта',
            'add_new_item' => 'Добавить нового поэта',
            'new_item_name' => 'Имя нового поэта',
            'search_items' => 'Искать поэта',
            'popular_items' => 'Популярные поэты',
            'parent_item' => 'Родительский поэт',
            'parent_item_colon' => 'Родительский поэт:',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'poet', 'with_front' => false, 'hierarchical' => true],
        'query_var' => 'poet',
    ]);
}
add_action('init', 'register_poets_taxonomy');

// Регистрация таксономии "Поэмы"
function register_poems_taxonomy() {
    register_taxonomy('poem', 'post', [
        'labels' => [
            'name' => 'Поэмы',
            'singular_name' => 'Поэма',
            'menu_name' => 'Поэмы',
            'all_items' => 'Все поэмы',
            'edit_item' => 'Редактировать поэму',
            'view_item' => 'Смотреть поэму',
            'update_item' => 'Обновить поэму',
            'add_new_item' => 'Добавить новую поэму',
            'new_item_name' => 'Имя новой поэмы',
            'search_items' => 'Искать поэму',
            'popular_items' => 'Популярные поэмы',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'poem', 'with_front' => false, 'hierarchical' => true],
        'query_var' => 'poem',
    ]);
}
add_action('init', 'register_poems_taxonomy');

// Регистрация таксономии "Треки"
function register_tracks_taxonomy() {
    register_taxonomy('track', 'post', [
        'labels' => [
            'name' => 'Треки',
            'singular_name' => 'Трек',
            'menu_name' => 'Треки',
            'all_items' => 'Все треки',
            'edit_item' => 'Редактировать трек',
            'view_item' => 'Смотреть трек',
            'update_item' => 'Обновить трек',
            'add_new_item' => 'Добавить новый трек',
            'new_item_name' => 'Имя нового трека',
            'search_items' => 'Искать трек',
            'popular_items' => 'Популярные треки',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'track', 'with_front' => false, 'hierarchical' => true],
        'query_var' => 'track',
    ]);
}
add_action('init', 'register_tracks_taxonomy');

// Регистрация таксономии "Документы"
function register_docs_taxonomy() {
    register_taxonomy('doc', 'post', [
        'labels' => [
            'name' => 'Документы',
            'singular_name' => 'Документ',
            'menu_name' => 'Документы',
            'all_items' => 'Все документы',
            'edit_item' => 'Редактировать документ',
            'view_item' => 'Смотреть документ',
            'update_item' => 'Обновить документ',
            'add_new_item' => 'Добавить новый документ',
            'new_item_name' => 'Имя нового документа',
            'search_items' => 'Искать документ',
            'popular_items' => 'Популярные документы',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'doc', 'with_front' => false, 'hierarchical' => true],
        'query_var' => 'doc',
    ]);
}
add_action('init', 'register_docs_taxonomy');

// Удаление BOM и буферов
function remove_utf8_bom($text) {
    $bom = pack('H*', 'EFBBBF');
    return preg_replace("/^$bom/", '', $text);
}

function remove_output_buffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
add_action('init', 'remove_output_buffers', 1);

// Константы
define('LOG_FILE', __DIR__ . '/error.log');
ini_set('error_log', LOG_FILE);
define('CHILD_JS_DIR', get_stylesheet_directory_uri() . '/js/');
define('FILE_TEXT_DIR', 'text_simple/');
define('CHILD_CF7_CLASSES_DIR', get_stylesheet_directory_uri() . '/cf7-classes/');