<?php
// inc/custom-functions.php
// Вспомогательные функции, работа с БД, аудио, редиректы и т.д.
//use BM\Cache\AdvancedPropertiesCache;
//use BM\Cache\PropertiesConfig;
//use BM\Cache\PropertiesCacheManager;
//use BM\Cache\AjaxPropertiesHandler;
//use BM\Cache\CacheInterface;
use BM\Database\Connection;


/**
 * Глобальные переменные
 */
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
    
    $phone_fields = ['phone', 'billing_phone', '_billing_phone'];
    foreach ($phone_fields as $field) {
        $phone = get_user_meta($current_user->ID, $field, true);
        if (!empty($phone)) {
            $user_data_global['user_phone'] = $phone;
            break;
        }
    }
}

$config = [
    'host' => 'poetrax_deepseek_mysql',
    'database' => 'u3436142_poetrax_deepseek_db',
    'username' => 'u3436142_poetrax_deepseek_user',
    'password' => 'CI57bdR7m6F9Xem7',
];


/**
 * Фильтр для wp_nav_menu_args
 */
add_filter('wp_nav_menu_args', function($args) {
    if(is_admin()) {
        @ini_set('max_input_vars', 5000);
    }
    return $args;
});

/**
 * Глобальные переменные для маршрутизации
 */
$url = $_SERVER['REQUEST_URI'];
$path = parse_url($url, PHP_URL_PATH);
global $slug;
$slug = '';
if($path) {
    $slug = trim($path, '/');
}

/**
 * PDO инициализация
 */
global $pdo;
use BM\Core\Database\Connection as DbConnection;
$connection = DbConnection::getInstance($config);
$pdo = $connection->getPdo();

/**
 * BotDetector
 */
require_once __DIR__ . '/../bot-detector.php';
global $searchBot;
global $showToBot;
$detector = new BotDetector();

$searchBot = false;
$showToBot = false;

if ($detector->isBot()) {
    if ($detector->verifySearchEngineBot()) {
        $searchBot = true;
    }
    $showToBot = true;
}

/**
 * Редирект для неавторизованных пользователей
 */
if(!empty($slug)) {
    if(($slug === 'lichnyj-zal' || $slug === 'zakaz-treka') && is_user_logged_in() != 1) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: /enter", true, 301);
        die();
    }
}

/**
 * Кэш свойства
 */
//$config = new PropertiesConfig();
//$cacheManager = new PropertiesCacheManager($cache, $config, $pdo);
//$ajaxHandler = new AjaxPropertiesHandler($cacheManager);

/**
 * Функции для работы с кэшем свойств
 */
 /*
function get_properties($type) {
    global $cacheManager;
    return $cacheManager->getProperties($type);
}

function refresh_properties_cache($type = null) {
    global $cacheManager;
    return $cacheManager->invalidateCache($type);
}

function warmup_all_caches() {
    global $cacheManager, $config;
    foreach ($config->getTypes() as $type) {
        $cacheManager->warmupCache($type);
    }
}

function clean_expired_cache() {
    global $cache;
    return $cache->clean_expired();
}

function get_cache_stats() {
    global $cache;
    return $cache->get_stats();
}

if (!wp_next_scheduled('clean_properties_cache')) {
    wp_schedule_event(time(), 'hourly', 'clean_properties_cache');
}
add_action('clean_properties_cache', 'clean_expired_cache');
*/

/**
 * MusicSelectorsSystem и UniversalCascadeSystem
 */
require_once  __DIR__ . '/../musicSelectorsSystem.php';
$musicSelectors = new MusicSelectorsSystem($pdo);
$musicSelectors->register_shortcodes();

require_once  __DIR__ . '/../universalCascadeSystem.php';
$universal_cascade_system = new UniversalCascadeSystem($pdo);
$universal_cascade_system->register_shortcodes();

/**
 * Регистрация таксономии wp_pattern_category
 */
add_action('init', function() {
    if (!taxonomy_exists('wp_pattern_category')) {
        register_taxonomy('wp_pattern_category', 'wp_block', [
            'public' => true,
            'show_in_rest' => true,
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
    return rest_ensure_response([]);
}

/**
 * Функции для работы с текстовыми файлами
 */
function sure_and_remove_BOM($content) {
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    return $content;
}

function encoding_content($content) {
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1251'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    return $content;
}

/**
 * Функция для музыкального селектора
 */
function ta_music_selector_code($type) {
    try {
        global $pdo;
        $valid_types = ['instrument', 'style'];
        if (!in_array($type, $valid_types)) {
            throw new InvalidArgumentException('Invalid type specified');
        }
        
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
        
        $items = Connection::query($query);
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

/**
 * Генерация токена для аудио
 */
function generate_audio_token() {
    $secret = 'qazwsx12345!';
    return md5($secret . date('Y-m-d-H'));
}

/**
 * Функции для работы с треками из БД
 */
function get_audio_tracks_from_db($limit, $type) {
    $current_user_id = get_current_user_id();
    $current_user_id = 1; // HACK
    
    global $pdo;
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
        (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM bm_ctbl000_interaction WHERE track_id = t.id AND user_id = ? AND type = 'bookmark') as is_bookmarked
        FROM bm_ctbl000_track t
        INNER JOIN bm_ctbl000_mood m ON t.mood_id = m.id
        INNER JOIN bm_ctbl000_track_music_detail md ON t.id = md.track_id
        INNER JOIN bm_ctbl000_voice_register r ON r.id = md.voice_register_id
        INNER JOIN bm_ctbl000_user u ON t.user_id = u.id
        INNER JOIN bm_ctbl000_music_style s ON md.style_id = s.id 
        INNER JOIN bm_ctbl000_music_genre g ON md.genre_id = g.id 
        INNER JOIN bm_ctbl000_theme th ON t.theme_id = th.id 
        INNER JOIN bm_ctbl000_voice_character c ON t.voice_character_id = c.id 	 
        LEFT JOIN bm_ctbl000_track_self_text st ON t.id = st.track_id
        WHERE t.status <> 'cancelled'
        AND t.is_approved = 1 
        AND t.is_active = 1 
        AND t.performance_type = 'song'
        ORDER BY t.updated_at DESC";
        
    if($limit != 0) {
        $query .= " LIMIT {$limit}";
    }
    
    $results = Connection::query($query, [$current_user_id]);
    return $results;
}

function get_audio_tracks_from_db_wp($limit, $type) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $current_user_id = 1; // HACK
    
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
        (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM bm_ctbl000_interaction WHERE track_id = p.id AND user_id = {$current_user_id} AND type = 'bookmark') as is_bookmarked,
        pm.poem_text
        FROM bm_posts p 
        INNER JOIN bm_term_relationships tr ON p.id = tr.object_id
        INNER JOIN bm_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'category'
        INNER JOIN bm_terms t ON t.term_id = tt.term_id 
        INNER JOIN bm_ctbl000_track trk ON p.id = trk.wp_id
        INNER JOIN bm_ctbl000_poem pm ON pm.id = trk.poem_id
        WHERE p.post_content LIKE '%src=\"%.mp3%' 
        AND (p.post_title IS NOT NULL AND p.post_title != '') 
        AND (tt.description IS NOT NULL AND tt.description != '') 
        AND trk.is_active = 1 
        AND p.post_status = 'publish'
        ORDER BY p.post_date DESC";
        
    if($limit != 0) {
        $query .= " LIMIT {$limit}";
    }
    
    $results = $wpdb->get_results($query);
    return $results;
}

function drow_audio_tracks_shortcode($tracks, $type) {
    if(count($tracks) !== 0) {
        $classDisplayOrNot = 'display-audio-list';
    } else {
        $classDisplayOrNot = '';
    }
    
    $output = '<div class="audio-playlist" id="audio-playlist">';
    
    switch ($type) {
        case 'my':
            $output .= '<h2 class="' . $classDisplayOrNot . '" id="id-here-for-my-tracks">Мои треки</h2>';
            break;
        case 'bookmark':
            $output .= '<h2 class="' . $classDisplayOrNot . '" id="id-here-for-bookmark-tracks">Треки в закладках</h2>';
            break;
        case 'like':
            $output .= '<h2 class="' . $classDisplayOrNot . '" id="id-here-for-like-tracks">Лайк треки</h2>';
            break;
        case 'common':
            $output .= '<h2 class="' . $classDisplayOrNot . '" id="id-here-for-common-tracks">Новые поступления</h2>';
            if(count($tracks) !== 0) {
                $output .= '<p style="display:none" class="new-instruction">Здесь можно прослушать новые треки по выбору или в цикле. Переходите на конкретный трек или автора</p>';
            }
            break;
    }

    if (count($tracks) == 0) {
        $output .= '<p>Таких треков нет</p>';
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
        default:
            $output .= '<div style="display:none" id="id-audio-playlist-common">';
            break;
    }

    foreach ($tracks as $index => $track) {
        $track_file_path = $track->file_path;
        $track_name = $track->track_name;
        $poet_id = $track->poet_id;
        $poem_id = $track->poem_id;
        $img_name = $track->img_name;
        $img_name = str_replace('audio/mp3', 'img/jpeg', $img_name);
        $img_name = str_replace('.mp3', '-50x50.jpeg', $img_name);
        $bookmark_count = $track->bookmark_count;
        $like_count = $track->like_count;
        $poem_text = get_current_poem_text($poem_id);

        $output .= '
        <div class="audio-track" data-track-id="' . esc_attr($index) . '">
            <div class="title-author"><h3><a href="' . esc_html($track->guid_track) . '">' . esc_html($track_name) . '</a></h3>
                <p class="author">
                    <a href="/category/' . $track->slug_author . '/">' . esc_html($track->author_name) . '</a>';
        if($type !== 'like' && $type !== 'my') {
            $output .= '<i class="far fa-thumbs-up like-btn" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
        }
        if($like_count != 0) {
            $output .= '<span title="Лайки" class="like-count">' . esc_html($like_count) . '</span>';
        }
        $output .= '<i class="far fa-bookmark bookmark-btn" aria-hidden="true" data-track-id="' . esc_html($like_count) . '"></i>';
        if($bookmark_count != 0) {
            $output .= '<span title="Закладки" class="bookmark-count">' . esc_html($bookmark_count) . '</span>';
        }
        $output .= '<i class="fa fa-window-close-o" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
        
        $feather = sprintf(
            '<i class="fas fa-feather-alt text-file-trigger" aria-hidden="true" title="Стихотворение" data-text-file="%s" data-name-poem="%s" data-popup-id="1247" role="button" style="cursor: pointer"></i>',
            $poem_text, ' ', $track_name
        );
        $output .= $feather;
        $output .= '<a href="' . esc_url($track_file_path) . '"><i class="fa-regular fa-download" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i></a>';
        $output .= '</p>
            </div>
            <img src="' . $img_name . '" alt="" class="image-simple" decoding="async">
            <audio controls="" controlslist="nodownload noplaybackrate" onplay="window.handleAudioPlay(this)" data-track-id="' . esc_attr($index) . '">
                <source src="' . esc_url($track_file_path) . '" type="audio/mpeg">
                Ваш браузер не поддерживает элемент audio.
            </audio>
        </div>';
    }
    $output .= '</div></div>';
    return $output;
}

function get_current_poem_text($poem_id) {
    $query = "SELECT poem_text, poem_slug FROM bm_ctbl000_poem WHERE is_active = 1 AND is_approved = 1 AND id = ?";
    $row = Pdo::row($query, [$poem_id]);
    if ($row['poem_text']) {
        return $row['poem_text'];
    }
}

function get_track_html($track_id, $track_data) {
    $user_id = get_current_user_id();
    $user_has_liked = check_user_has_liked($track_id, $user_id);
    $user_has_bookmarked = check_user_has_bookmarked($track_id, $user_id);
    
    ob_start();
    ?>
    <div class="track-item" 
         data-track-id="<?php echo esc_attr($track_id); ?>" 
         data-user-id="<?php echo esc_attr($user_id); ?>"
         data-user-has-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
         data-user-has-bookmarked="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>">
        
        <img width="50" height="50" src="<?php echo esc_url($track_data['img_name']); ?>" class="" alt="" decoding="async">

        <div class="track-header">
            <h3 class="track-title"><?php echo esc_html($track_data['track_name']); ?></h3>
            <p class="track-artist"><?php echo esc_html($track_data['author_name']); ?></p>
        </div>
        
        <div class="track-interactions">
            <button class="interaction-btn like-btn" data-action="like" data-active="<?php echo $user_has_liked ? 'true' : 'false'; ?>" aria-label="Like this track">
                <span class="icon">♥</span>
                <span class="counter" data-counter="likes">0</span>
            </button>
            <button class="interaction-btn bookmark-btn" data-action="bookmark" data-active="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>" aria-label="Bookmark this track">
                <span class="icon">⭐</span>
                <span class="counter" data-counter="bookmarks">0</span>
            </button>
            <span class="interaction-counter">
                <span class="icon">👂</span>
                <span class="counter" data-counter="plays">0</span>
            </span>
            <span class="interaction-counter">
                <span class="icon">⏱️</span>
                <span class="counter" data-counter="listening-time">0:00</span>
            </span>
        </div>
        
        <div class="track-player">
            <audio controls preload="metadata" data-track-id="<?php echo esc_attr($track_id); ?>">
                <source src="<?php echo esc_url($track_data['track_path']); ?>" type="audio/mpeg">
            </audio>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Редиректы и защита MP3 файлов
 */
add_action('template_redirect', function() {
    if (strpos($_SERVER['REQUEST_URI'], '.mp3') !== false) {
        if (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], WP_SITEURL) === false) {
            wp_die('Доступ запрещен', 403);
        }
    }
});

add_action('init', 'smart_mp3_redirect');
function smart_mp3_redirect() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/store/audio/mp3/') !== false && preg_match('/\.mp3$/i', $_SERVER['REQUEST_URI'])) {
        $filename = basename($_SERVER['REQUEST_URI']);
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('-', $name_without_ext);
        $title_part = $parts[2] ?? '';
        if (!empty($title_part)) {
            $new_url = home_url('/' . $title_part . '/');
            wp_redirect($new_url, 301);
            exit;
        }
        wp_die('Прямой доступ запрещён', '403', array('response' => 403));
    }
}

add_action('init', 'redirect_mp3_attachments');
function redirect_mp3_attachments() {
    if (is_attachment()) {
        $post = get_queried_object();
        if ($post && strpos($post->post_mime_type, 'audio/') === 0) {
            $slug = $post->post_name;
            $new_url = home_url('/' . $slug . '/');
            wp_redirect($new_url, 301);
            exit;
        }
    }
}

add_action('init', 'block_direct_mp3_access');
function block_direct_mp3_access() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/store/audio/mp3/') !== false && preg_match('/\.mp3$/i', $_SERVER['REQUEST_URI'])) {
        $filename = basename($_SERVER['REQUEST_URI']);
        $attachment = get_page_by_path(pathinfo($filename, PATHINFO_FILENAME), OBJECT, 'attachment');
        if ($attachment) {
            wp_redirect(get_permalink($attachment), 301);
            exit;
        } else {
            wp_die('Прямой доступ к файлам запрещён', '404', array('response' => 404));
        }
    }
}

/**
 * Правила перезаписи для single-track
 */
add_action('init', function() {
    add_rewrite_rule('^track/([0-9]+)/?$', 'index.php?pagename=single-track&track_id=$matches[1]', 'top');
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