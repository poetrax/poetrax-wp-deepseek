<?php
use BM\Database\Connection;
use BM\Cache\AdvancedPropertiesCache;
use BM\Cache\PropertiesConfig;
use BM\Cache\PropertiesCacheManager;
use BM\Cache\AjaxPropertiesHandler;
use BM\Cache\CacheInterface;
use BM\Database\Pdo;

// Регистрация таксономии "Поэты" (poets)
function register_poets_taxonomy() {
    register_taxonomy(
        'poet',  // машинное имя (будет в URL)
        'post',   // к какому типу записей привязываем
        array(
            'labels' => array(
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
            ),
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true, // true = как рубрики (с родителями), false = как метки
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true, // для блочного редактора
            'rewrite' => array(
                'slug' => 'poet', // URL: /poet/pushkin/
                'with_front' => false,
                'hierarchical' => true // для вложенных URL
            ),
            'query_var' => 'poet', // переменная в URL: ?poet=pushkin
        )
    );
}
add_action('init', 'register_poets_taxonomy');

// Регистрация таксономии  "Поэмы" (poems)
function register_poems_taxonomy() {
    register_taxonomy(
        'poem',
        'post',
        array(
            'labels' => array(
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
            ),
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false, // для поэм обычно не нужна иерархия
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true, // для блочного редактора
            'rewrite' => array(
                'slug' => 'poem', // URL: /poem/vesna/
                'with_front' => false,
                'hierarchical' => true // для вложенных URL
            ),
            'query_var' => 'poem', // переменная в URL: ?poem=vesna
        )
    );
}
add_action('init', 'register_poems_taxonomy');

// Регистрация таксономии "Треки" (tracks)
function register_tracks_taxonomy() {
    register_taxonomy(
        'track',
        'post',
        array(
            'labels' => array(
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
            ),
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false, // для треков обычно не нужна иерархия
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true, // для блочного редактора
            'rewrite' => array(
                'slug' => 'track', // URL: /track/vesna/
                'with_front' => false,
                'hierarchical' => true // для вложенных URL
            ),
            'query_var' => 'track', // переменная в URL: ?track=vesna
        )
    );
}
add_action('init', 'register_tracks_taxonomy');

// Регистрация таксономии  "Документы" (docs)
function register_docs_taxonomy() {
    register_taxonomy(
        'doc',
        'post',
        array(
            'labels' => array(
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
            ),
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false, // для документов обычно не нужна иерархия
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true, // для блочного редактора
            'rewrite' => array(
                'slug' => 'doc', // URL: /track/vesna/
                'with_front' => false,
                'hierarchical' => true // для вложенных URL
            ),
            'query_var' => 'doc', // переменная в URL: ?track=vesna
        )
    );
}
add_action('init', 'register_docs_taxonomy');


//TEST
if (function_exists('opcache_reset')) {
    opcache_reset();
}

//TEST
//add_action('wp', function() {
    //if (is_front_page()) {
        error_log('Шаблон главной: ' . get_page_template());
    //}
//});

// Удаляем BOM если есть
function remove_utf8_bom($text) {
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}


function remove_output_buffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
add_action('init', 'remove_output_buffers', 1);


//if (ob_get_level()) ob_end_clean();
define('LOG_FILE', __DIR__ . '/error.log');
ini_set('error_log', LOG_FILE); // Указываем файл для логов
define('CHILD_JS_DIR', get_stylesheet_directory_uri() . '/js/');
define('FILE_TEXT_DIR', 'text_simple/');
define('CHILD_CF7_CLASSES_DIR', get_stylesheet_directory_uri() . '/cf7-classes/');


function enqueue_child_theme_styles()
{
    // Подключение стилей родительской темы
    if (!wp_style_is('parent-style', 'registered')) {
        wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    }
    // Подключение стилей дочерней темы
    if (!wp_style_is('child-style', 'registered')) {
        wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_styles');




//TEST
//wp_cache_flush();

//WP global user data get one time only
global $user_data_global;
$current_user = wp_get_current_user();
$user_data_global = array(
	'user_id' => 0,
    'first_name' => '',
	'last_name' => '',
	'display_name' => '',
	'user_email' => '',
	'user_url' => '',
	'user_phone' => ''
);

if (is_user_logged_in()) {
	$user_data_global['user_id'] = get_current_user_id();
    $user_data_global['first_name'] = $current_user->first_name;
	$user_data_global['last_name'] = $current_user->last_name;
	$user_data_global['display_name'] = $current_user->display_name;
	$user_data_global['user_email'] = $current_user->user_email;
	$user_data_global['user_url'] = $current_user->user_url;
    
	// Ищем телефон в разных метаполях
	$phone_fields = ['phone', 'billing_phone', '_billing_phone'];
	foreach ($phone_fields as $field) {
		$phone = get_user_meta($current_user->ID, $field, true);
		if (!empty($phone)) {
			$user_data_global['user_phone'] = $phone;
			break;
		}
	}
}

add_filter('wp_nav_menu_args', function($args) {
    if(is_admin()) {
        @ini_set('max_input_vars', 5000);
    }
    return $args;
});


$url = $_SERVER['REQUEST_URI'];
$path = parse_url($url, PHP_URL_PATH);
global $slug;
$slug ='';
if($path) {
    $slug = trim($path, '/');
}

//Регистрация jQuery
add_action( 'init', 'true_jquery_register' );
function true_jquery_register() {
	if ( !is_admin() ) {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', ( 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js' ), false, null, true );
		wp_enqueue_script( 'jquery' );
	}
}

global $pdo;

$db = new BM\Database\Pdo();
$pdo = Pdo::getPDO();
//require_once 'db.php';
//$db = new Db();
//$pdo = $db->getPDO();

require_once 'bot-detector.php';
global $searchBot;
global $showToBot;
$detector = new BotDetector();

$searchBot = false;
$showToBot = false;

if ($detector->isBot()) {
    // Логируем или обрабатываем бота
    // error_log("Bot detected: " . strtolower($_SERVER['HTTP_USER_AGENT']));
    // Для поисковых ботов - разрешаем доступ
    if ($detector->verifySearchEngineBot()) {
        $searchBot=true;
    } 
    /*
    else {
        // Блокировать или ограничить
        http_response_code(429);
        exit;
    }
    */
    $showToBot=true;
}

/* start TEST */
// Исправление URL в скриптах и стилях
add_action('wp_enqueue_scripts', 'fix_https_urls', 9999);
function fix_https_urls() {
    // Удаляем некорректно зарегистрированные стили
    wp_deregister_style('font-awesome');
    wp_deregister_style('search-filter-style');
    
    // Регистрируем правильные версии
    if (is_ssl()) {
        $upload_dir = wp_upload_dir();
        $site_url = site_url();
        
        // Исправляем URL сайта
        if (strpos($site_url, 'http://') === 0) {
            $site_url = str_replace('http://', 'https://', $site_url);
        }
    }
}

// Отключение проблемных скриптов
add_action('wp_enqueue_scripts', 'remove_problematic_scripts', 999);
function remove_problematic_scripts() {
    // Отключаем ipapi.co
    wp_dequeue_script('ipapi-script');
    
    // Отключаем OneSignal если нужно
    wp_dequeue_script('onesignal-sdk');
}

// Отключаем скрипты, использующие ipapi.co
add_action('wp_enqueue_scripts', 'remove_ipapi_scripts', 9999);
function remove_ipapi_scripts() {
    // Отключаем jQuery если он вызывает ipapi
    wp_dequeue_script('jquery');
    wp_deregister_script('jquery');
    
    // Перерегистрируем чистый jQuery
    wp_enqueue_script('jquery', 
        'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', 
        array(), '3.6.0', true
    );
    
    // Ищем и отключаем другие скрипты, использующие ipapi
    global $wp_scripts;
    foreach ($wp_scripts->queue as $handle) {
        $script = $wp_scripts->registered[$handle];
        if (strpos($script->src, 'ipapi.co') !== false) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}

// Отключить все внешние геолокационные сервисы
add_action('wp_head', 'remove_geolocation_scripts', 1);
function remove_geolocation_scripts() {
    ?>
    <script type="text/javascript">
    // Блокируем вызовы к ipapi.co
    window.fetch = new Proxy(window.fetch, {
        apply: function(target, thisArg, args) {
            const url = args[0];
            if (typeof url === 'string' && url.includes('ipapi.co')) {
                console.log('Blocked ipapi.co request:', url);
                return Promise.reject(new Error('ipapi.co blocked by CORS policy'));
            }
            return target.apply(thisArg, args);
        }
    });
    
    // Блокируем jQuery AJAX вызовы к ipapi.co
    if (window.jQuery) {
        jQuery.ajaxPrefilter(function(options) {
            if (options.url && options.url.includes('ipapi.co')) {
                console.log('Blocked jQuery ipapi.co request');
                options.url = ''; // Отменяем запрос
            }
        });
    }
    </script>
    <?php
}


/*

// Для тестирования
add_shortcode('text_file_debug', 'text_file_debug_shortcode');
function text_file_debug_shortcode() {
    $output = '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">';
    $output .= '<h3>Text File Debug Info</h3>';
    $output .= '<p>AJAX URL: <code>' . admin_url('admin-ajax.php') . '</code></p>';
    $output .= '<p>Nonce: <code>' . wp_create_nonce('text_file_nonce') . '</code></p>';
    $output .= '<p>Uploads dir: <code>' . wp_upload_dir()['basedir'] . '</code></p>';
    $output .= '</div>';
    return $output;
}

*/

/* end TEST */


//TODO may be not /enter
//TODO zakaz-treka no need prohibit there is registration form/ may to do for lichnyj-zal too
if(!empty($slug))
{
    if(($slug ==='lichnyj-zal' || $slug === 'zakaz-treka') && is_user_logged_in()!=1) 
    {
        header("HTTP/1.1 301 Moved Permanently");  
        header("Location: /enter", true, 301);  
        die(); 
    }
}

// Инициализируем компоненты
$cache = AdvancedPropertiesCache::getInstance($pdo);
$config = new PropertiesConfig();
$cacheManager = new PropertiesCacheManager($cache, $config, $pdo);
$ajaxHandler = new AjaxPropertiesHandler($cacheManager);

/**
 * Шорткод для инициализации JS конфигурации
 */
 //HACK do not work

function properties_js_config() {
    global $config;
    $js_config = $config->getClientConfig();
    
    $output = '<script type="text/javascript">';
    $output .= 'window.propertiesConfig = ' . json_encode($js_config) . ';';
    $output .= 'window.ajaxurl = "' . admin_url('admin-ajax.php') . '";';
    $output .= 'window.propertiesNonce = "' . wp_create_nonce('properties_nonce') . '";';
    $output .= '</script>';
    
    return $output;
}
add_shortcode('properties_js_config', 'properties_js_config');


// Пример 1: Получение свойств с кэшированием
function get_properties($type) {
    global $cacheManager;
    return $cacheManager->getProperties($type);
}

$instruments = get_properties('instruments');

// Пример 2: Принудительное обновление кэша
function refresh_properties_cache($type = null) {
    global $cacheManager;
    return $cacheManager->invalidateCache($type);
}

// Пример 3: Разогрев кэша для всех типов
function warmup_all_caches() {
    global $cacheManager, $config;
    
    foreach ($config->getTypes() as $type) {
        $cacheManager->warmupCache($type);
    }
}

// Пример 4: Очистка просроченных записей (можно запускать по крону)
function clean_expired_cache() {
    global $cache;
    return $cache->clean_expired();
}

// Пример 5: Получение статистики
function get_cache_stats() {
    global $cache;
    return $cache->get_stats();
}

// Пример 6: Использование в шаблоне
?>

<script>
jQuery(document).ready(function($) {
    $('#load-properties').on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_properties',
                property_type: 'instruments',
                nonce: properties_nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Loaded from cache:', response.data.cached);
                    // Обработка данных
                }
            }
        });
    });
});
</script>

<?php
// Пример 7: Крон-задача для очистки кэша
if (!wp_next_scheduled('clean_properties_cache')) {
    wp_schedule_event(time(), 'hourly', 'clean_properties_cache');
}
add_action('clean_properties_cache', 'clean_expired_cache');




require_once 'musicSelectorsSystem.php';
$musicSelectors = new MusicSelectorsSystem($pdo);
$musicSelectors->register_shortcodes();

require_once 'universalCascadeSystem.php';
$universal_cascade_system = new UniversalCascadeSystem($pdo);
$universal_cascade_system->register_shortcodes();

//example
//const loader = new UniversalPropertiesLoader();
//const instruments = await loader.loadProperties('instruments');


// TODO только для админов
// Шорткод для статистики кэша (только для админов)
function cache_stats_shortcode() {
    global $cache;
    if (!current_user_can('manage_options')) {
        return '';
    }
    $stats = $cache->get_stats();
    $output = '<div class="cache-stats"><h3>Cache Statistics</h3>';
    
    foreach ($stats as $stat) {
        $output .= sprintf(
            '<p>%s: %d entries, %d hits (avg: %.1f)</p>',
            $stat['property_type'],
            $stat['total_entries'],
            $stat['total_hits'],
            $stat['avg_hits']
        );
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('cache_stats', 'cache_stats_shortcode');


/**
 * функция подключения cascade js
 */
function enqueue_cascade_assets() {
    // Подключаем JS
    wp_enqueue_script('cascade-manager', CHILD_JS_DIR . 'cascade-manager.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_cascade_assets');


//TEST
add_action('init', function() {
    if (!taxonomy_exists('wp_pattern_category')) {
        register_taxonomy('wp_pattern_category', 'wp_block', [
            'public' => true,
            'show_in_rest' => true, // Важно для REST API!
        ]);
    }
});

add_action('rest_api_init', function() {
    register_rest_route('wp/v2', '/wp_pattern_category/', [
        'methods'  => 'GET',
        'callback' => 'get_wp_pattern_categories',
        'permission_callback' => '__return_true'
    ]);
});

function get_wp_pattern_categories() {
    return rest_ensure_response([]); // временный ответ
}

add_action('wp_enqueue_scripts', function() {
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://code.jquery.com/jquery-3.6.0.min.js', [], null, true);
}, 100);

//TEST
define('DISABLE_WP_REST_API', false); 


function my_custom_music_band_scripts()
{
    if (!wp_script_is('custom-audio-control', 'registered')) {
        wp_enqueue_script('custom-audio-control', CHILD_JS_DIR . 'audio-control.js', array(), '0.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'my_custom_music_band_scripts');

function render_doc_shortcode_cached($atts) {
    $atts = shortcode_atts(['file' => ''], $atts, 'doc');
    
    if (empty($atts['file'])) {
        return '<p class="doc-error">Ошибка: не указан файл</p>';
    }
    
    // Безопасная обработка имени файла
    $filename = sanitize_file_name($atts['file']);
    $docs_dir = trailingslashit(WP_CONTENT_DIR) . 'docs/';
    $file_path = $docs_dir . $filename;
    
    // Проверки безопасности
    $real_docs_path = realpath($docs_dir);
    $real_file_path = realpath($file_path);
    
    if ($real_file_path === false || 
        !$real_docs_path || 
        strpos($real_file_path, $real_docs_path) !== 0 ||
        !is_file($real_file_path)) {
        return '<p class="doc-error">Ошибка доступа к файлу</p>';
    }
    
    // Проверяем расширение файла
    $file_ext = strtolower(pathinfo($real_file_path, PATHINFO_EXTENSION));
    if (!in_array($file_ext, ['html', 'htm'], true)) {
        return '<p class="doc-error">Недопустимый тип файла</p>';
    }
    
    // Ключ для кеша с учетом времени модификации файла
    $cache_key = 'doc_' . md5($real_file_path . '_' . filemtime($real_file_path));
    $content = get_transient($cache_key);
    
    if ($content === false) {
        $content = file_get_contents($real_file_path);
        if ($content === false) {
            return '<p class="doc-error">Не удалось прочитать файл</p>';
        }
        set_transient($cache_key, $content, HOUR_IN_SECONDS);
        // Логируем создание кеша (для отладки)
        // error_log('Doc shortcode cache created for: ' . $filename);
    }
    
    return do_shortcode($content);
    //Пример вызова [doc file="example.html"]
}
add_shortcode('doc', 'render_doc_shortcode_cached');

/**
 * Автоматическая регистрация doc-шорткодов из папки docs
 * Автоматически создает шорткоды вида [doc_имя_файла]
 * из всех .html файлов в папке wp-content/docs/
 */
add_action('init', function() {
    $html_files = glob(trailingslashit(WP_CONTENT_DIR) . 'docs/*.html') ?: [];
    
    foreach ($html_files as $file_path) {
        $file_name = basename($file_path, '.html'); // Убираем расширение
        $shortcode = 'doc_' . $file_name;
        
        // Регистрируем только если шорткод еще не существует
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, function() use ($file_name) {
                return do_shortcode('[doc file="' . esc_attr($file_name . '.html') . '"]');
            });
        }
    }
});

require_once 'draw-links.php';
add_shortcode('draw_links', [DrawLinks::class, 'shortcodeHandler']);

add_action('init', function() {
    add_shortcode('site_links', ['DrawLinks', 'shortcodeHandler']);
});

function ta_music_selector_code($type) {
    try {
        global $pdo;
        // Validate type parameter
        $valid_types = ['instrument', 'style'];
        if (!in_array($type, $valid_types)) {
            throw new InvalidArgumentException('Invalid type specified');
        }
        
        // Determine table and column names based on type
        $table_map = [
            'instrument' => 'bm_ctbl000_music_instrument',
            'style' => 'bm_ctbl000_music_style'
        ];
        
        $name_group_map = [
            'instrument' => 'instruments[]',
            'style' => 'styles[]'
        ];

        $table = $table_map[$type];
        $query = "SELECT id, name, suno_prompt FROM {$table} WHERE is_active = 1 ORDER BY name";
        
        $items = Pdo::query($query);
       
        $name_group = $name_group_map[$type];

        return build_ta($items, $type, $name_group);
        
    } catch(PDOException $exception) {
        error_log('Database error in ta_music_selector: ' . $exception->getMessage());
        wp_send_json_error('Ошибка получения данных: ' . $exception->getMessage());
        return '';
    } catch(Exception $exception) {
        error_log('Error in ta_music_selector: ' . $exception->getMessage());
        return '';
    }
}

/* Individual shortcode functions */
/*ta_instruments*/
function ta_instruments_code() {
    return ta_music_selector_code('instrument');
}
add_shortcode('ta_instruments', 'ta_instruments_code');
/*ta_styles*/
function ta_styles_code() {
    return ta_music_selector_code('style');
}
add_shortcode('ta_styles', 'ta_styles_code');


/*poet_poem_shortcode*/
/*
function handle_get_poems() {
    try {
        global $pdo;
        $query = "SELECT 
        pm.id AS poem_id, 
        pm.name as poem_name,
        pt.id AS poet_id, 
        CONCAT(pt.first_name, ' ',pt.last_name) as poet_name 
        FROM bm_ctbl000_poem pm 
        INNER JOIN bm_ctbl000_poet pt ON pm.poet_id=pt.id
        WHERE 
        pm.is_active=1 
        AND pm.is_approved=1 
        AND pt.is_active=1 
        AND pt.is_approved=1;";

      
        $properties = [];
        $properties = Pdo::query($query);
   

        // Используем только один метод вывода
        wp_send_json_success($properties);
    
    } catch(PDOException $exception) {
        wp_send_json_error('Ошибка получения данных: ' . $exception->getMessage());
    }

    wp_die();

}

function poet_poem_code() {
    $selector='<div id="categoriesList" class="categories-list"></div>';
    $selector.='<div id="propertiesGrid" class="properties-grid"></div>';
}
add_shortcode('poet_poem_shortcode', 'poet_poem_code');
*/
/*poet_poem_shortcode*/

/*suno_style_shortcode*/
/*
function suno_style_code() {
    $query = "SELECT 
    ss.id, 
    ss.name,
    ss.category,
    ss.suno_prompt 
    FROM bm_ctbl000_music_suno_style ss 
    WHERE 
    ss.is_active=1 
    AND ss.is_approved=1;";

}
add_shortcode('suno_style_shortcode', 'suno_style_code');
*/
/*suno_style_shortcode*/

function get_audio_tracks_ajax() {
    check_ajax_referer('audio_tracks_nonce', 'security');
    
    $tracks = get_audio_tracks_from_db_wp(10,'common');
    wp_send_json($tracks);
}
add_action('wp_ajax_get_audio_tracks', 'get_audio_tracks_ajax');
add_action('wp_ajax_nopriv_get_audio_tracks', 'get_audio_tracks_ajax');

function enqueue_audio_controller() {
      if (!wp_script_is('audio-controller', 'registered')) {
              wp_enqueue_script('audio-controller', CHILD_JS_DIR . 'audio-playlist.js', array(), '1.0', true );
      }
}
add_action('wp_enqueue_scripts', 'enqueue_audio_controller');

function social_share_assets() {
    // Font Awesome 6.5.1
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');
    // Наш скрипт
    wp_enqueue_script('social-share', CHILD_JS_DIR .  'social-share.js', array(), '1.0', true);
    wp_enqueue_script('save-like-bookmark', CHILD_JS_DIR .  'save-like-bookmark.js', array(), '1.0', true);
}
add_action('wp_enqueue_scripts', 'social_share_assets');


 // Убеждаемся что нет BOM
function sure_and_remove_BOM($content) {
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    return $content;
}

function encoding_content($content) {
    // Обработка кодировки
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1251'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    return $content;
}

/* text to Popup */
// Регистрируем AJAX обработчики
add_action('wp_ajax_load_text_file', 'load_text_file_content');
add_action('wp_ajax_nopriv_load_text_file', 'load_text_file_content');
function load_text_file_content() {
    try {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'text_file_nonce')) {
            throw new Exception('Security check failed');
        }
        
        $file_path = sanitize_text_field($_POST['file_path'] ?? '');
        
        if (empty($file_path)) {
            throw new Exception('File path is empty');
        }
        
        $upload_dir = wp_upload_dir();
        $full_path = path_join($upload_dir['basedir'], $file_path);
        
        // Дополнительная проверка безопасности
        if (strpos(realpath($full_path), realpath($upload_dir['basedir'])) !== 0) {
            throw new Exception('Invalid file path');
        }
        
        if (!file_exists($full_path) || !is_readable($full_path)) {
            throw new Exception('File not found or not readable');
        }
        
        $content = file_get_contents($full_path);
        
        
        if ($content === false) {
            throw new Exception('Could not read file');
        }
        
        $content = encoding_content($content);
               
        $content = sure_and_remove_BOM($content); 
       
        // Экранирование и возврат
        $content = esc_html($content);
        
        // Отправляем чистый JSON
        wp_send_json_success([
            'content' => $content,
            'file_size' => strlen($content)
        ], 200);
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
    
    wp_die(); // Всегда завершаем
}

// Регистрируем скрипты
add_action('wp_enqueue_scripts', 'text_file_popup_scripts');
function text_file_popup_scripts() {
    // Проверяем, что Popup Maker активен
    if (function_exists('pum')) {
        wp_enqueue_script('text-file-popup', CHILD_JS_DIR . 'text-file-popup.js', array('jquery'), '1.0', true);
        
        // Передаем данные в JavaScript
        wp_localize_script('text-file-popup', 'textFileAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('text_file_nonce')
        ));
        
        error_log('Text File Popup: Scripts enqueued');
    } else {
        error_log('Text File Popup: Popup Maker not active');
    }
}

/*TEST*/
function text_file_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'file' => '',
        'name' => '',
    ), $atts);
    
    // Проверяем, что файл указан
    if (empty($atts['file'])) {
        return '<span style="color: red;">Ошибка: не указан файл</span>';
    }
    
    return sprintf(
        '<i class="fas fa-feather-alt text-file-trigger" aria-hidden="true" title="Стихотворение" data-text-file="%s" data-name-poem="%s" data-popup-id="1247" role="button" style="cursor: pointer"></i>',
        esc_attr($atts['file']),
        esc_attr($atts['name'])

    );
}
add_shortcode('text_file_button', 'text_file_button_shortcode');
/*TEST*/

//TEST
function debug_file_path() {
    $file_path = 'text_simple/1_1_aleksandr_blok_vkhozhu_ja_v_tyomnye_hramy.txt';
    
    echo '<h3>Отладка пути к файлу:</h3>';
    
    $locations = [
        'Относительный путь' => $file_path,
        'Папка uploads' => WP_CONTENT_DIR . '/uploads/' . $file_path,
        'Дочерняя тема' => get_stylesheet_directory() . '/' . $file_path,
        'Родительская тема' => get_template_directory() . '/' . $file_path,
        'Корень WordPress' => ABSPATH . $file_path
    ];
    
    foreach ($locations as $label => $path) {
        $exists = file_exists($path) ? '<span style="color:green">✓ Существует</span>' : '<span style="color:red">✗ Не найден</span>';
        echo "<p><strong>{$label}:</strong> {$path} - {$exists}</p>";
    }
}

/* text to Popup */


// Шорткод социальные ссылки
function social_share_shortcode() {
    return '<div style="text-align:center"><div class="social-share-container" id="socialShareContainer"></div><div id=""></div></div>';
}
add_shortcode('social_share', 'social_share_shortcode');

//audio ----------------------------//
add_action('template_redirect', function() {
    if (strpos($_SERVER['REQUEST_URI'], '.mp3') !== false) {
        if (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], WP_SITEURL) === false) {
            wp_die('Доступ запрещен', 403);
        }
    }
});

// Генерируем временный токен (действителен 1 час)
function generate_audio_token() {
    $secret = 'qazwsx12345!'; //TODO сделать строгим
    return md5($secret . date('Y-m-d-H')); // Токен меняется каждый час
}

function donate_shortcode() {
      global $slug;

      if($slug!=='lichnyj-zal') 
      {
        $donate_sum=MIN_DONATE;
        
        /* recomment and switch when no wordpress
        global $pdo;
        $query = '
        SELECT 
        * 
        FROM 
        bm_ctbl000_user 
        WHERE 
        id=?
        ';
        $current_user_id = get_current_user_id();
     
        $user = Pdo::query($query,[$current_user_id])

        $user_email = empty($user['user_email']) ? '' : $user['user_email'];
        $user_phone = empty($user['user_phone']) ? '' : $user['user_phone'];
        $user_fio = $user['user_first_name']. ' ' . $user['user_last_name'];
        $user_fio = empty($user_fio) ? '' : $user_fio;
        */

        //this for WP
        global $user_data_global;
        $current_user_id = $user_data_global['user_id'];
        $user_email = empty($user_data_global['user_email']) ? '' : $user_data_global['user_email'];
        $user_phone = empty($user_data_global['user_phone']) ? '' : $user_data_global['user_phone'];
        $user_fio = $user_data_global['first_name']. ' ' . $user_data_global['last_name'];
        $user_fio = empty($user_fio) ? '' : $user_fio;
        //this for WP

        $d_e='<div class="donate-block">';
        
        $d_e.='<div class="donate-text">';
        $d_e.='<p>Здесь и сейчас рождается и растёт сервис переложения стихов русских поэтов «забытых времен» на современную музыку и публикации созданных треков повсеместно в мире.<br>Хотите вписать себя в скрижали благородного почина?<br>Пожалуйста, приглашаем вас.</p>';
        
        //TODO make it block
        $d_e.='<div id="do-invite" class="menu-wrapper"><b><a href="#" class="popmake-1351 pum-trigger" style="cursor: pointer;">О проекте</a> <a href="#" class="popmake-1352 pum-trigger" style="cursor: pointer;">Жертвователям</a> <a href="#" class="popmake-1353 pum-trigger" style="cursor: pointer;">Спонсорам</a>';
        $d_e.=' <a href="#" class="popmake-1478 pum-trigger" style="cursor: pointer;">О пользе</a> <a href="#" class="popmake-1476 pum-trigger" style="cursor: pointer;">Конкурс</a> <a href="#" class="popmake-1501 pum-trigger">Тонкости ИИ музыки</a> <a href="#" class="popmake-5903 pum-trigger" style="cursor: pointer;">Поддержать проект</a>'; 
       
        if($slug!=='zakaz-treka'){
            $d_e.=' <a href="/zakaz-treka" target="_blank">Заказ трека</a>';
        }
        //make it block
        
        $d_e.='</b>';
        $d_e.='</div>';//--do-invite
        $d_e.='</div>';//--donate-text
        $d_e.='<br><p><b id="donate-title">На поддержание и развитие сайта по продвижению русского языка и культуры</b></p>';
        
        $d_e.='<link rel="stylesheet" href="https://yookassa.ru/integration/simplepay/css/yookassa_construct_form.css?v=1.27.0">';
        $d_e.='<form target="_blank" class="yoomoney-payment-form" action="https://yookassa.ru/integration/simplepay/payment" method="post" accept-charset="utf-8">';
        $d_e.='<div class="max-donate-text">Разовая сумма - не более ' . MAX_DONATE . '&nbsp;₽</div>'; //--max-donate-text
        $d_e.='<div class="ym-products ym-display-none">';
	    $d_e.='<div class="ym-block-title ym-products-title">Товары</div>';
        $d_e.='<div class="ym-product">';
        $d_e.='<div class="ym-product-line">';
        $d_e.='<span class="ym-product-description"><span class="ym-product-count">1×</span>Дарение на поддержку и развитие сайта poetrax.ru по продвижению русского языка и культуры</span>';
        $d_e.='<span class="ym-product-price" data-price="'.$donate_sum.'" data-id="83" data-count="1">'.$donate_sum.'&nbsp;₽</span>';
        $d_e.='</div>';
        $d_e.='<input disabled="" type="hidden" name="text" value="Дарение на поддержку и развитие сайта poetrax.ru по продвижению русского языка и культуры">';
	    $d_e.='<input disabled="" type="hidden" name="price" value="'.$donate_sum.'">';
	    $d_e.='<input disabled="" type="hidden" name="quantity" value="1">';
	    $d_e.='<input disabled="" type="hidden" name="paymentSubjectType" value="commodity">';
	    $d_e.='<input disabled="" type="hidden" name="paymentMethodType" value="full_prepayment">';
	    $d_e.='<input disabled="" type="hidden" name="tax" value="1">';
	    $d_e.='</div>';
	    $d_e.='</div>';
        $d_e.='<input value="" type="hidden" name="ym_merchant_receipt"\>';
        $d_e.='<div class="ym-customer-info">';
        $d_e.='<div class="ym-block-title ym-display-none">О покупателе';
        $d_e.='</div>';
        $d_e.='<input name="cps_email" class="ym-input" placeholder="Email" type="text" value="'.$user_email.'">';
        $d_e.='<input name="cps_phone" class="ym-input" placeholder="Телефон" type="text" value="'.$user_phone.'">';
        $d_e.='<input name="custName" class="ym-input" placeholder="ФИО" type="text" value="'.$user_fio.'">';
        $d_e.='<textarea class="ym-textarea " name="orderDetails" placeholder="Комментарий" value=""></textarea>';
        $d_e.='</div>';//--ym-customer-info
        $d_e.='<div class="ym-hidden-inputs">';
	    $d_e.='<input name="shopSuccessURL" type="hidden" value="https://poetrax.ru/uspeshnaya-oplata">';
        $d_e.='<input name="shopFailURL" type="hidden" value="https://poetrax.ru/oshibka-oplaty">';
        $d_e.='</div>'; //--ym-hidden-inputs
	    $d_e.='<input name="customerNumber" type="hidden" value="Дарение на развитие сайта poetrax.ru по продвижению русского языка и культуры">';
        $d_e.='<div class="d_agreed"><input type="checkbox" id="chb_agreed" name="chb_agreed" value=""><label for="chb_agreed"> Я принимаю </label> <a href="/dogovor-pozhertvovanija" target="_blank">Публичную оферту о заключении договора пожертвования</a></div>'; 
        $d_e.='<div class="ym-payment-btn-block ym-before-line ym-align-space-between">';
        $d_e.='<div class="ym-input-icon-rub ym-display-none">';
        $d_e.='<input name="sum" placeholder="0.00" class="ym-input ym-sum-input ym-required-input" type="number" step="any" value="'.$donate_sum.'.00">';
        $d_e.='</div>';//--ym-input-icon-rub
        $d_e.='<button data-text="Пожертвовать" class="ym-btn-pay ym-result-price"><span class="ym-text-crop">Пожертвовать</span>'; 
        $d_e.='<span class="ym-price-output">'.$donate_sum.'&nbsp;₽</span></button><img src="https://yookassa.ru/integration/simplepay/img/iokassa-gray.svg?v=1.27.0" class="ym-logo" width="114" height="27" alt="ЮKassa">';
        $d_e.='</div>'; //--ym-payment-btn-block
        $d_e.='<input name="shopId" type="hidden" value="1142597">';
        $d_e.='</form>';
        $d_e.='<script src="https://yookassa.ru/integration/simplepay/js/yookassa_construct_form.js?v=1.27.0"></script>';
	    $d_e.='<br><br></div>';//--donate-block
      return $d_e;
   }
   else {
        return '';
   }
}




add_shortcode('donate', 'donate_shortcode');

//audio in circle----------------------------//

//TODO here is probem with AND t.performance_type = 'song' not only song ";  AND user_id =1 //$time,$limit
//$datetime, $limit
function get_audio_tracks_from_db($limit, $type) {
    $current_user_id = get_current_user_id();
    
    //HACK
    $current_user_id=1;
    //HACK
   
    global $pdo;
    //HACK проверить запрос pt и добавить новые поля
    $query = "
     SELECT 
     t.id as track_id,
     t.wp_id,
     t.poet_id,
     t.poem_id,
     t.img_name,
     t.track_name,
     t.track_path,
     t.track_format,
     t.track_duration,
     t.suno_version,
     t.voice_gender,
     t.created_at,
     t.updated_at,
     m.name as name_mood,
     s.name as name_style,
     g.name as name_genre,
     th.name as name_theme,
     r.name as name_register,
     c.name as name_character, 
     pt.poet_name,
     pt.poem_title,
     md.bpm,
     u.display_name,

     (SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE track_id = t.id AND type = 'like') as like_count,
     (SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE track_id = p.id AND type = 'bookmark') as bookmark_count,
     (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM bm_ctbl000_interaction WHERE track_id = t.id AND user_id =? AND type = 'bookmark') as is_bookmarked
     
     FROM bm_ctbl000_track t
     INNER JOIN bm_ctbl000_mood m ON t.mood_id = m.id
     INNER JOIN bm_ctbl000_track_music_detail md ON t.id = md.track_id
     INNER JOIN bm_ctbl000_voice_register r ON r.id = md.voice_register_id
     INNER JOIN bm_ctbl000_user u ON t.user_id = u.id
     INNER JOIN bm_ctbl000_music_style s ON md.style_id = s.id 
     INNER JOIN bm_ctbl000_music_genre g ON md.genre_id = g.id 
     INNER JOIN bm_ctbl000_theme th ON t.theme_id = th.id 
     INNER JOIN bm_ctbl000_voice_character c ON t.voice_character_id = c.id 	 
     LEFT JOIN bm_ctbl000_track_self_text st ON t.id = st.track_id ";

    if($type === 'my') {
        
        $query .= " ";
    }

    if($type === 'bookmark') {
        $query .= " ";
    }
     if($type === 'like') {
        $query .= " ";
    }
     $query .= "WHERE  t.status <> 'cancelled'
     AND t.is_approved = 1 
     AND t.is_active = 1 
     AND t.performance_type = 'song' ";

    if($type === 'bookmark') {
        $query .= " ";
    }
    if($type === 'my') {
        $query .= " ";
    }
    if($type === 'like') {
        $query .= " ";
    }
     $query .= " ORDER BY t.updeted_at DESC";
    if($limit != 0) {
        $query .=" LIMIT {$limit}";
    }
    $query .=";";
    //AND  t.updeted_at >= ?     
       
     $time = strtotime('-1 month', time());
    //$time = date('d.m.Y H:i:s', $time); // 03.09.2025 07:50:07 
     
    $results = Pdo::query($query,[$current_user_id]);
    return $results;
}

//HACK author_name (автор трека) и poet_name (автор стихотворения) разные сущности

//TODO here is probem with AND t.performance_type = 'song' not only song ";  AND user_id =$current_user_id
function get_audio_tracks_from_db_wp($limit, $type) {
    global $wpdb;
    $current_user_id = get_current_user_id();

    //HACK
    $current_user_id=1;
    //HACK
    
    //HACK проверить и полностью изменить запрос использовать только таблицу track
    $query = "SELECT 
    trk.id AS track_id,
    trk.poet_id,
    trk.poem_id,
    trk.img_name,
    trk.poet_name AS author_name,
    p.id AS wp_id,
    p.guid AS guid_track, 
    t.slug AS slug_author,
    SUBSTRING_INDEX(SUBSTRING_INDEX(p.post_content,'src=\"',-1),'\"', 1) AS file_path,
    p.post_date,
    p.post_title AS track_name,

    (SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE track_id = p.id AND type = 'like') as like_count,
    (SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE track_id = p.id AND type = 'bookmark') as bookmark_count,
    (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM bm_ctbl000_interaction WHERE track_id = p.id AND user_id =".$current_user_id." AND type = 'bookmark') as is_bookmarked,
    pm.poem_text
    
    FROM bm_posts p INNER JOIN 
    bm_term_relationships tr ON p.id = tr.object_id
    INNER JOIN bm_term_taxonomy tt ON 
        tr.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'category'
    INNER JOIN bm_terms t ON t.term_id=tt.term_id 
    INNER JOIN bm_ctbl000_track trk ON p.id=trk.wp_id
    INNER JOIN bm_ctbl000_poem pm ON pm.id=trk.poem_id
";

    if($type==='my') {
        $query .= " ";
    }

    if($type==='bookmark') {
        $query .= " ";
    }

    if($type==='like') {
        $query .= " ";
    }

    $query .= " WHERE p.post_content LIKE '%src=\"%.mp3%' 
    AND (p.post_title IS NOT NULL AND p.post_title != '') 
    AND (tt.description IS NOT NULL AND tt.description != '') 
    AND trk.is_active = 1 
    AND p.post_status = 'publish'"; 

    if($type === 'my') {
        $query .= " ";
    }

    if($type === 'bookmark') {
        $query .= " ";
    }

    if($type === 'like') {
        $query .= " ";
    }

    $query .= " ORDER BY p.post_date DESC";
       if($limit!=0) {
        $query .=" LIMIT {$limit}";
    }
    $query .=";";
    $results = $wpdb->get_results($query);
    //TO DO условие если нет резульата возвращает ''
    return $results;
}


//Не верно (?) audio_tracks_my_shortcode вызывется с параметрами и выводится на одной и той же странице
function audio_tracks_my_shortcode(){
     //TODO исправить на get_audio_tracks_from_db и запрос с my
    $tracks = get_audio_tracks_from_db_wp(10,'common');
    return drow_audio_tracks_shortcode($tracks,'my');
}
add_shortcode('audio_my_playlist', 'audio_tracks_my_shortcode');

function audio_tracks_bookmark_shortcode(){
     //TODO исправить на get_audio_tracks_from_db и запрос с bookmark
    $tracks = get_audio_tracks_from_db_wp(10,'common');
    return drow_audio_tracks_shortcode($tracks,'bookmark');
}
add_shortcode('audio_bookmark_playlist', 'audio_tracks_bookmark_shortcode');

function audio_tracks_like_shortcode(){
     //TODO исправить на get_audio_tracks_from_db и запрос с bookmark
    $tracks = get_audio_tracks_from_db_wp(10,'common');
    return drow_audio_tracks_shortcode($tracks,'like');
}
add_shortcode('audio_like_playlist', 'audio_tracks_like_shortcode');

function audio_tracks_shortcode(){
    //TODO исправить на get_audio_tracks_from_db
    $tracks = get_audio_tracks_from_db_wp(10,'common');
    return drow_audio_tracks_shortcode($tracks,'common');
}
add_shortcode('audio_playlist', 'audio_tracks_shortcode');


add_shortcode('audio_top_short_code', 'drow_audio_tracks_shortcode');
function drow_audio_tracks_shortcode($tracks, $type) {
    
    if(count($tracks)!==0) {
        $classDisplayOrNot = 'display-audio-list';
    } else {
        $classDisplayOrNot = '';
    }
    
    $output = '<div class="audio-playlist" id="audio-playlist">';
        switch ($type) {
            case 'my':
                $output .= '<h2 class="'.$classDisplayOrNot.'" id="id-here-for-my-tracks">Мои треки</h2>';
            break;
            case 'bookmark':
                $output .= '<h2 class="'.$classDisplayOrNot.'" id="id-here-for-bookmark-tracks">Треки в закладках</h2>';
            break;
            case 'like':
                $output .= '<h2 class="'.$classDisplayOrNot.'" id="id-here-for-like-tracks">Лайк треки</h2>';
            break;
            case 'common':
                $output .= '<h2 class="'.$classDisplayOrNot.'" id="id-here-for-common-tracks">Новые поступления</h2>';
                if(count($tracks)!==0) {
                    $output .= '<p style="display:none" class="new-instruction">Здесь можно прослушать новые треки по выбору или в цикле. Переходите на конкретный трек или автора</p>';
                }
            break;
        }

        if (count($tracks)==0) {
            $output .='<p>Таких треков нет</p>';
            $output .= '</div>';
            return $output;
        }

        switch ($type) {
            case 'my':
                $output .= '<div id="id-audio-playlist-my">';
            break;
            case 'bookmark':
                $output .= '<div style="display:none" id="id-audio-playlist-bookmark">';
            break;
              case 'like':
                $output .= '<div style="display:none" id="id-audio-playlist-like">';
            break;
            case 'common':
                $output .= '<div style="display:none" id="id-audio-playlist-common">';
            break;
            default:
               $output .= '<div style="display:none" id="id-audio-playlist-common">';
            break;
        }

        //TODO Для Bookmark закладок убрать закладки добавить убрать из закладок
        //TODO Для My лайк убрать лайк оставить только количество лайк убрать закладки
        //TODO Для My убрать лайк убрать закладки оставить только количество лайк
        //TODO Для Common проверка на лайк и закладки если есть убрать оставить только количество лайк
        //TODO Добавить количество прослушиваний и общее время прослушиваний
        //TODO Добавить скачивание

        foreach ($tracks as $index => $track) {
            $track_file_path = $track->file_path; 
            $track_name = $track->track_name; 
            $poet_id = $track->poet_id;  
            $poem_id = $track->poem_id; 
            //$img_name = $track->img_name; 
            
           
            $img_name = str_replace('audio/mp3', 'img/jpeg', $img_name);
         
            $img_name = str_replace('.mp3','-50x50.jpeg', $img_name);
           

            $bookmark_count = $track->bookmark_count; 
            $like_count = $track->like_count;
            $poem_text = get_current_poem_text($poem_id);
         

            $output .= '
            <div  class="audio-track" data-track-id="' . esc_attr($index) . '">
                <div class="title-author"><h3><a href="'.esc_html($track->guid_track) .'">'. esc_html($track_name) . '</a></h3>
                    <p class="author">
                        <a href="/category/'.$track->slug_author .'/">' . esc_html($track->author_name) . '</a>';
                        if($type!=='like' && $type!=='my') {
                          $output .= '<i class="far fa-thumbs-up like-btn" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
                        } 

                        if($like_count!=0){
                            $output .= '<span title="Лайки" class="like-count">'. esc_html($like_count) .'</span>';
                        }
                    
                        $output .= '<i class="far fa-bookmark bookmark-btn" aria-hidden="true" data-track-id="' . esc_html($like_count) . '"></i>';
                        
                        if($bookmark_count!=0){
                            $output .= '<span title="Закладки" class="bookmark-count">'. esc_html($bookmark_count) .'</span>';
                        }

                        $output .= '<i class="fa fa-window-close-o" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
                      
                        $feather = sprintf(
                            '<i class="fas fa-feather-alt text-file-trigger" aria-hidden="true" title="Стихотворение" data-text-file="%s" data-name-poem="%s" data-popup-id="1247" role="button" style="cursor: pointer"></i>',
                            $poem_text,' ', $track_name
                        );
                        $output .= $feather;

                        //<i class="fa-light fa-download"></i>
                        $output .= '<a href="'. esc_url($track_file_path) .'"><i class="fa-regular fa-download" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i></a>';

                $output .= '</p>
                </div>

                <img  
                src="'.$img_name.'"  
                alt="" class="image-simple" 
                decoding="async">

                <audio controls="" controlslist="nodownload noplaybackrate" onplay="window.handleAudioPlay(this)" data-track-id="' . esc_attr($index) . '">
                    <source src="'  . esc_url($track_file_path) . '" type="audio/mpeg">
                    Ваш браузер не поддерживает элемент audio.
                </audio>
            </div>';
        }
    $output .= '</div></div>';

    return $output;
}


//Исправленный HTML шаблон
// wp-content/themes/your-theme/track-template.php
function get_track_html($track_id, $track_data) {
    $user_id = get_current_user_id();
    
    // Получаем текущее состояние лайков/закладок для пользователя
    $user_has_liked = check_user_has_liked($track_id, $user_id); 
    $user_has_bookmarked = check_user_has_bookmarked($track_id, $user_id); 
    
    ob_start();
    ?>
    <div class="track-item" 
         data-track-id="<?php echo esc_attr($track_id); ?>" 
         data-user-id="<?php echo esc_attr($user_id); ?>"
         data-user-has-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
         data-user-has-bookmarked="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>">
        
    <img 
        width="50" 
        height="50" 
        src="<?php echo esc_url($track_data['img_name']); ?>" 
        class="" 
        alt="" 
        decoding="async">

        <div class="track-header">
            <h3 class="track-title"><?php echo esc_html($track_data['track_name']); ?></h3>
            <p class="track-artist"><?php echo esc_html($track_data['author_name']); ?></p>
        </div>
        
        <div class="track-interactions">
            <!-- Like Button -->
            <button class="interaction-btn like-btn" 
                    data-action="like" 
                    data-active="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
                    aria-label="Like this track">
                <span class="icon">♥</span>
                <span class="counter" data-counter="likes">0</span>
            </button>
            
            <!-- Bookmark Button -->
            <button class="interaction-btn bookmark-btn" 
                    data-action="bookmark" 
                    data-active="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>"
                    aria-label="Bookmark this track">
                <span class="icon">⭐</span>
                <span class="counter" data-counter="bookmarks">0</span>
            </button>
            
            <!-- Play Counter -->
            <span class="interaction-counter">
                <span class="icon">👂</span>
                <span class="counter" data-counter="plays">0</span>
            </span>
            
            <!-- Listening Time -->
            <span class="interaction-counter">
                <span class="icon">⏱️</span>
                <span class="counter" data-counter="listening-time">0:00</span>
            </span>
        </div>
        
        <!-- Audio Player -->
        <div class="track-player">
            <audio controls preload="metadata" data-track-id="<?php echo esc_attr($track_id); ?>">
                <source src="<?php echo esc_url($track_data['track_path']); ?>" type="audio/mpeg">
            </audio>
        </div>у
    </div>
    <?php
    return ob_get_clean();
}


//100-41-ne_zhizn-leonid_germanovich_krylov-50x50
//100-41-ne_zhizn-leonid_germanovich_krylov-100x100
//100-41-ne_zhizn-leonid_germanovich_krylov-150x150
//100-41-ne_zhizn-leonid_germanovich_krylov-180x180
//100-41-ne_zhizn-leonid_germanovich_krylov-300x300


function get_current_poem_text($poem_id) {
    // TODO not only for one but for list too.
    $query = "SELECT poem_text, poem_slug FROM bm_ctbl000_poem WHERE 
    is_active = 1 AND is_approved = 1 AND id=?";
    
    $row = Pdo::row($query,[$poem_id]);
  
    if ($row['poem_text']) {
        return $row['poem_text'];
    }
}

add_shortcode('poem_text', 'poem_text_shortcode');
function poem_text_shortcode() {
   //HACK
   $poem_text = get_current_poem_text(1, 4);
   $poem_text = str_replace('\n', '<br>', $poem_text);
   return $poem_text;
}

// Шорткод для отображения плеера
function track_player_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'title' => '',
        'artist' => '',
        'url' => '',
        'img_name' => '',
    ), $atts);
    
    if (!$atts['id'] || !$atts['url']) return '';
    
    $track_data = array(
        'track_name' => $atts['title'],
        'author_name' => $atts['artist'],
        'track_path' => $atts['url'],
        'img_name' => $atts['img_name'],
    );
    
    return get_track_html($atts['id'], $track_data);
}
add_shortcode('track_player', 'track_player_shortcode');


//audio in circle----------------------------//

/* Poet-track */

add_action('wp_enqueue_scripts', 'bmz_enqueue_poet_tracks_scripts', 30);
function bmz_enqueue_poet_tracks_scripts() {
    // Регистрируем скрипт
    wp_register_script(
        'bmz-poet-tracks',
        CHILD_JS_DIR . 'poet-tracks.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Подключаем на всех страницах (или можно проверить наличие блока)
    wp_enqueue_script('bmz-poet-tracks');
    
    // Передаем данные в JavaScript
    wp_localize_script('bmz-poet-tracks', 'poetTracks', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bmz_poet_tracks_nonce'),
        //'theme_url' => get_stylesheet_directory_uri(),
        //'site_url' => site_url()
    ));
}


add_action('wp_ajax_get_poet_tracks', 'get_poet_tracks_callback');
add_action('wp_ajax_nopriv_get_poet_tracks', 'get_poet_tracks_callback');

function get_poet_tracks_callback() {
    // Очищаем ВСЕ буферы
    while (ob_get_level()) {
        ob_end_clean();
    }

    global $wpdb;
    
    // Логирование для отладки
    error_log('AJAX запрос get_poet_tracks получен');
    error_log('POST данные: ' . print_r($_POST, true));
    

    // Проверяем nonce для безопасности
    if (!check_ajax_referer('bmz_poet_tracks_nonce', 'security', false)) {
        error_log('Nonce проверка не пройдена');
        wp_send_json_error('Security check failed');
    }
    
    $poet_id = isset($_POST['poet_id']) ? intval($_POST['poet_id']) : 0;
    $poet_name = isset($_POST['poet_name']) ? sanitize_text_field($_POST['poet_name']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 10;
        
    error_log("Поиск треков для: poet_name='{$poet_name}', page={$page}");
    
    if ($poet_id <= 0 && empty($poet_name)) {
        error_log('Не указан поэт');
        wp_send_json_error('Не указан поэт');
    }
    
    $offset = ($page - 1) * $per_page;
    $tracks = array();
    
    // Проверяем существование таблиц
    $track_table = 'bm_ctbl000_track';
    $poem_table = 'bm_ctbl000_poem';
    
    error_log("Используемые таблицы: {$track_table}, {$poem_table}");
    
    // Проверяем, существуют ли таблицы
    $track_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$track_table}'") === $track_table;
    $poem_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$poem_table}'") === $poem_table;
    
    error_log("Таблица треков существует: " . ($track_table_exists ? 'да' : 'нет'));
    error_log("Таблица стихов существует: " . ($poem_table_exists ? 'да' : 'нет'));
    
    if (!$track_table_exists || !$poem_table_exists) {
        error_log('Одна или обе таблицы не существуют');
        wp_send_json_error('Tables not found');
    }
    
    // Пробуем разные варианты запроса
    if ($poet_id > 0) {
        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS t.track_name, t.track_path, t.poem_slug 
             FROM {$track_table} t 
             WHERE t.poet_id = %d 
             AND t.track_path IS NOT NULL 
             AND t.track_path != '' 
             ORDER BY t.track_name
             LIMIT %d OFFSET %d",
            $poet_id, $per_page, $offset
        );
    } else {
         $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS t.track_name, t.track_path, t.poem_slug 
             FROM {$track_table} t 
             WHERE t.poet_name = %s 
             AND t.track_path IS NOT NULL 
             AND t.track_path != '' 
             ORDER BY t.track_name
             LIMIT %d OFFSET %d",
            $poet_name, $per_page, $offset
        );
        
        error_log("Выполняем запрос: " . $query);
    }
    
    $results = $wpdb->get_results($query);
    error_log("Найдено результатов: " . count($results));
    
    if ($wpdb->last_error) {
        error_log("Ошибка SQL: " . $wpdb->last_error);
        
        // Пробуем альтернативный запрос
        if (empty($poet_name)) {
            wp_send_json_error('Поэт не найден');
        }
        
        // Ищем poet_id по имени поэта
        $poet_id_from_name = $wpdb->get_var($wpdb->prepare(
            "SELECT poet_id FROM {$poem_table} WHERE poet_name = %s LIMIT 1",
            $poet_name
        ));
        

        error_log("Найден poet_id: " . $poet_id_from_name);
        
        if ($poet_id_from_name) {
            $query = $wpdb->prepare(
                "SELECT SQL_CALC_FOUND_ROWS 
                 t.track_name, 
                 t.track_path, 
                 t.poem_slug
                 FROM {$track_table} t 
                 WHERE t.poet_id = %d 
                 AND t.track_path IS NOT NULL 
                 AND t.track_path != '' 
                 ORDER BY t.track_name
                 LIMIT %d OFFSET %d",
                $poet_id_from_name, $per_page, $offset
            );
            
            $results = $wpdb->get_results($query);
            error_log("Найдено результатов после поиска по poet_id: " . count($results));
        }
    }
    
    $total_rows = $wpdb->get_var("SELECT FOUND_ROWS()");
    $total_pages = ceil($total_rows / $per_page);
    
    error_log("Всего строк: {$total_rows}, Всего страниц: {$total_pages}");
    
    foreach ($results as $row) {
        $tracks[] = array(
            'track_name' => $row->track_name,
            'audio' => $row->track_path,
            'poem_slug' => $row->poem_slug
        );
    }

    error_log("BMZ Найден poet_id для '{$poet_name}': " . ($poet_id ? $poet_id : 'не найден'));
    
    $response = array(
        'tracks' => $tracks,
        'page' => $page,
        'total_pages' => $total_pages,
        'has_more' => $page < $total_pages,
        'total' => $total_rows,
        'debug' => array(
            'query' => $query,
            'results_count' => count($results)
        )
    );
    
    error_log("Отправляем ответ: " . json_encode($response));
    
    wp_send_json_success($response);
}

/* Poet-track */


/*last settings*/
remove_action('wp_head', 'wp_generator');
add_filter('rest_enabled', '__return_false');
remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
remove_action( 'wp_head', 'rest_output_link_wp_head', 10, 0 );
remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
remove_action( 'auth_cookie_malformed', 'rest_cookie_collect_status' );
remove_action( 'auth_cookie_expired', 'rest_cookie_collect_status' );
remove_action( 'auth_cookie_bad_username', 'rest_cookie_collect_status' );
remove_action( 'auth_cookie_bad_hash', 'rest_cookie_collect_status' );
remove_action( 'auth_cookie_valid', 'rest_cookie_collect_status' );
remove_filter( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 );
remove_action( 'init', 'rest_api_init' );
remove_action( 'rest_api_init', 'rest_api_default_filters', 10, 1 );
remove_action( 'parse_request', 'rest_api_loaded' );
remove_action( 'rest_api_init', 'wp_oembed_register_route' );
remove_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rsd_link');
//отключение Emoji
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );



/* Redirect and hide mp3 */

add_action('init', 'smart_mp3_redirect');
function smart_mp3_redirect() 
{
    // Проверяем, что это прямой запрос к MP3 файлу
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/store/audio/mp3/') !== false 
        && preg_match('/\.mp3$/i', $_SERVER['REQUEST_URI'])) 
        {
        
        $filename = basename($_SERVER['REQUEST_URI']);
        
        // ИЗВЛЕКАЕМ НУЖНУЮ ЧАСТЬ:
        // 1. Убираем расширение .mp3
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // 2. Разбиваем по дефисам
        $parts = explode('-', $name_without_ext);
        
        // 3. Формат "число-число-название-автор"
        $title_part=$parts[2];

        if (!empty($title_part)) {
            $new_url = home_url('/' . $title_part . '/');
            wp_redirect($new_url, 301);
            exit;
        }
        // Если не удалось распарсить - просто запрещаем доступ
        wp_die('Прямой доступ запрещён', '403', array('response' => 403));
    }
}

add_action('init', 'redirect_mp3_attachments');
function redirect_mp3_attachments() {
    // Проверяем, что это запрос к странице вложения
    if (is_attachment()) {
        $post = get_queried_object();
        
        // Проверяем, что это аудиофайл
        if ($post && strpos($post->post_mime_type, 'audio/') === 0) {
            
            // Получаем красивое название (slug)
            $slug = $post->post_name;
            
            // Формируем URL для редиректа
            $new_url = home_url('/' . $slug . '/');
            
            // Делаем 301 редирект
            wp_redirect($new_url, 301);
            exit;
        }
    }
}

// Дополнительно: запрещаем прямой доступ к MP3-файлам
add_action('init', 'block_direct_mp3_access');
function block_direct_mp3_access() {
    // Проверяем, что запрос идёт напрямую к MP3 в папке uploads
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/store/audio/mp3/') !== false 
        && preg_match('/\.mp3$/i', $_SERVER['REQUEST_URI'])) {
        
        // Извлекаем название файла
        $filename = basename($_SERVER['REQUEST_URI']);
        
        // Пытаемся найти соответствующий пост-вложение
        $attachment = get_page_by_path(pathinfo($filename, PATHINFO_FILENAME), OBJECT, 'attachment');
        
        if ($attachment) {
            // Перенаправляем на красивый URL
            wp_redirect(get_permalink($attachment), 301);
            exit;
        } else {
            // Если не нашли - 404
            wp_die('Прямой доступ к файлам запрещён', '404', array('response' => 404));
        }
    }
}


// Interaction
/**
 * AJAX обработчики взаимодействий
 */
add_action('wp_ajax_bm_toggle_interaction', 'bm_toggle_interaction');
add_action('wp_ajax_nopriv_bm_toggle_interaction', 'bm_toggle_interaction');

function bm_toggle_interaction() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $type = sanitize_text_field($_POST['type']);
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'Необходимо авторизоваться']);
    }
    
    $interaction_service = new BM\Services\InteractionService();
    $result = $interaction_service->add_interaction($track_id, $user_id, $type);
    
    if ($result) {
        $stats = $interaction_service->get_track_stats($track_id);
        wp_send_json_success([
            'message' => 'success',
            'stats' => $stats
        ]);
    } else {
        wp_send_json_error(['message' => 'Ошибка']);
    }
}

add_action('wp_ajax_bm_record_play', 'bm_record_play');
add_action('wp_ajax_nopriv_bm_record_play', 'bm_record_play');

function bm_record_play() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $user_id = get_current_user_id();
    
    $interaction_service = new BM\Services\InteractionService();
    $interaction_service->record_play($track_id, $user_id);
    
    wp_send_json_success();
}

add_action('wp_ajax_bm_get_track_stats', 'bm_get_track_stats');
add_action('wp_ajax_nopriv_bm_get_track_stats', 'bm_get_track_stats');

function bm_get_track_stats() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    
    $interaction_service = new BM\Services\InteractionService();
    $stats = $interaction_service->get_track_stats($track_id);
    
    wp_send_json_success($stats);
}




// Добавляем правило для single-track
add_action('init', function() {
    add_rewrite_rule(
        '^track/([0-9]+)/?$',
        'index.php?pagename=single-track&track_id=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function($vars) {
    $vars[] = 'track_id';
    return $vars;
});

add_filter('template_include', function($template) {
    if (get_query_var('track_id')) {
        return get_template_directory() . '/single-track.php';
    }
    return $template;
});


/**
 * Получение данных стихотворения для модального окна
 */
add_action('wp_ajax_bm_get_poem_data', 'bm_get_poem_data');
add_action('wp_ajax_nopriv_bm_get_poem_data', 'bm_get_poem_data');

function bm_get_poem_data() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $poem_id = intval($_POST['poem_id']);
    
    // Пробуем получить через трек
    if ($track_id && !$poem_id) {
        global $wpdb;
        $track = $wpdb->get_row($wpdb->prepare(
            "SELECT poem_id, poet_id FROM " . BM_TE_TABLE_TRACK . " WHERE id = %d",
            $track_id
        ));
        $poem_id = $track ? $track->poem_id : 0;
    }
    
    if (!$poem_id) {
        wp_send_json_error(['message' => 'Стихотворение не найдено']);
    }
    
    // Получаем стихотворение
    $poem_repo = new BM\Repositories\PoemRepository();
    $poem = $poem_repo->find($poem_id);
    
    if (!$poem) {
        wp_send_json_error(['message' => 'Стихотворение не найдено']);
    }
    
    // Получаем поэта
    $poet_name = '';
    if ($poem->poet_id) {
        $poet_repo = new BM\Repositories\PoetRepository();
        $poet = $poet_repo->find($poem->poet_id);
        $poet_name = $poet ? $poet->short_name : '';
    }
    
    wp_send_json_success([
        'title' => $poem->name,
        'author' => $poet_name,
        'text' => $poem->poem_text,
        'link' => home_url('/poem/' . $poem->poem_slug)
    ]);
}

