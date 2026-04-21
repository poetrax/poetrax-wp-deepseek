<?php
// inc/enqueue.php
// Подключение скриптов и стилей
/**
 * Подключение стилей дочерней темы
 */
function enqueue_child_theme_styles()
{
    if (!wp_style_is('parent-style', 'registered')) {
        wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    }
    if (!wp_style_is('child-style', 'registered')) {
        wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_styles');

/**
 * Регистрация jQuery с Google CDN
 */
function true_jquery_register() {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js', false, null, true);
        wp_enqueue_script('jquery');
    }
}
add_action('init', 'true_jquery_register');

/**
 * Подключение cascade.js
 */
function enqueue_cascade_assets() {
    wp_enqueue_script('cascade-manager', CHILD_JS_DIR . 'cascade-manager.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_cascade_assets');

/**
 * Подключение audio-control.js
 */
function my_custom_music_band_scripts()
{
    if (!wp_script_is('custom-audio-control', 'registered')) {
        wp_enqueue_script('custom-audio-control', CHILD_JS_DIR . 'audio-control.js', array(), '0.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'my_custom_music_band_scripts');

/**
 * Подключение audio-playlist.js
 */
function enqueue_audio_controller() {
    if (!wp_script_is('audio-controller', 'registered')) {
        wp_enqueue_script('audio-controller', CHILD_JS_DIR . 'audio-playlist.js', array(), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_audio_controller');

/**
 * Подключение social-share.js и save-like-bookmark.js
 */
function social_share_assets() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');
    wp_enqueue_script('social-share', CHILD_JS_DIR . 'social-share.js', array(), '1.0', true);
    wp_enqueue_script('save-like-bookmark', CHILD_JS_DIR . 'save-like-bookmark.js', array(), '1.0', true);
}
add_action('wp_enqueue_scripts', 'social_share_assets');

/**
 * Подключение text-file-popup.js
 */
function text_file_popup_scripts() {
    if (function_exists('pum')) {
        wp_enqueue_script('text-file-popup', CHILD_JS_DIR . 'text-file-popup.js', array('jquery'), '1.0', true);
        wp_localize_script('text-file-popup', 'textFileAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('text_file_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'text_file_popup_scripts');

/**
 * Подключение poet-tracks.js
 */
function bmz_enqueue_poet_tracks_scripts() {
    wp_register_script('bmz-poet-tracks', CHILD_JS_DIR . 'poet-tracks.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('bmz-poet-tracks');
    wp_localize_script('bmz-poet-tracks', 'poetTracks', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bmz_poet_tracks_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'bmz_enqueue_poet_tracks_scripts');

/**
 * Отключение проблемных скриптов
 */
add_action('wp_enqueue_scripts', 'remove_problematic_scripts', 999);
function remove_problematic_scripts() {
    wp_dequeue_script('ipapi-script');
    wp_dequeue_script('onesignal-sdk');
}

/**
 * Исправление URL для HTTPS
 */
add_action('wp_enqueue_scripts', 'fix_https_urls', 9999);
function fix_https_urls() {
    wp_deregister_style('font-awesome');
    wp_deregister_style('search-filter-style');
    if (is_ssl()) {
        $upload_dir = wp_upload_dir();
        $site_url = site_url();
        if (strpos($site_url, 'http://') === 0) {
            $site_url = str_replace('http://', 'https://', $site_url);
        }
    }
}

/**
 * Отключение ipapi.co скриптов
 */
add_action('wp_enqueue_scripts', 'remove_ipapi_scripts', 9999);
function remove_ipapi_scripts() {
    wp_dequeue_script('jquery');
    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), '3.6.0', true);
    
    global $wp_scripts;
    foreach ($wp_scripts->queue as $handle) {
        $script = $wp_scripts->registered[$handle];
        if (strpos($script->src, 'ipapi.co') !== false) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}

/**
 * Блокировка геолокационных сервисов
 */
add_action('wp_head', 'remove_geolocation_scripts', 1);
function remove_geolocation_scripts() {
    ?>
    <script type="text/javascript">
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
    if (window.jQuery) {
        jQuery.ajaxPrefilter(function(options) {
            if (options.url && options.url.includes('ipapi.co')) {
                console.log('Blocked jQuery ipapi.co request');
                options.url = '';
            }
        });
    }
    </script>
    <?php
}