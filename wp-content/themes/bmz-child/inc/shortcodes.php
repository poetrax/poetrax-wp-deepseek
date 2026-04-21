<?php
// inc/shortcodes.php
// Все шорткоды темы
use BM\Database\Connection;
use BM\Cache\AdvancedPropertiesCache;
use BM\Cache\PropertiesConfig;
use BM\Cache\PropertiesCacheManager;
use BM\Cache\AjaxPropertiesHandler;
use BM\Cache\CacheInterface;
use BM\Core\Config;

$config = Config::getInstance();

/**
 * Шорткод для инициализации JS конфигурации
 */
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



/**
 * Шорткод для отображения документов из папки docs
 */
function render_doc_shortcode_cached($atts) {
    $atts = shortcode_atts(['file' => ''], $atts, 'doc');
    
    if (empty($atts['file'])) {
        return '<p class="doc-error">Ошибка: не указан файл</p>';
    }
    
    $filename = sanitize_file_name($atts['file']);
    $docs_dir = trailingslashit(WP_CONTENT_DIR) . 'docs/';
    $file_path = $docs_dir . $filename;
    
    $real_docs_path = realpath($docs_dir);
    $real_file_path = realpath($file_path);
    
    if ($real_file_path === false || !$real_docs_path || strpos($real_file_path, $real_docs_path) !== 0 || !is_file($real_file_path)) {
        return '<p class="doc-error">Ошибка доступа к файлу</p>';
    }
    
    $file_ext = strtolower(pathinfo($real_file_path, PATHINFO_EXTENSION));
    if (!in_array($file_ext, ['html', 'htm'], true)) {
        return '<p class="doc-error">Недопустимый тип файла</p>';
    }
    
    $cache_key = 'doc_' . md5($real_file_path . '_' . filemtime($real_file_path));
    $content = get_transient($cache_key);
    
    if ($content === false) {
        $content = file_get_contents($real_file_path);
        if ($content === false) {
            return '<p class="doc-error">Не удалось прочитать файл</p>';
        }
        set_transient($cache_key, $content, HOUR_IN_SECONDS);
    }
    
    return do_shortcode($content);
}
add_shortcode('doc', 'render_doc_shortcode_cached');

/**
 * Автоматическая регистрация doc-шорткодов из папки docs
 */
add_action('init', function() {
    $html_files = glob(trailingslashit(WP_CONTENT_DIR) . 'docs/*.html') ?: [];
    foreach ($html_files as $file_path) {
        $file_name = basename($file_path, '.html');
        $shortcode = 'doc_' . $file_name;
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, function() use ($file_name) {
                return do_shortcode('[doc file="' . esc_attr($file_name . '.html') . '"]');
            });
        }
    }
});

/**
 * Шорткод draw_links
 */
add_shortcode('draw_links', [DrawLinks::class, 'shortcodeHandler']);
add_shortcode('site_links', ['DrawLinks', 'shortcodeHandler']);

/**
 * Шорткод для инструментов
 */
function ta_instruments_code() {
    return ta_music_selector_code('instrument');
}
add_shortcode('ta_instruments', 'ta_instruments_code');

/**
 * Шорткод для стилей
 */
function ta_styles_code() {
    return ta_music_selector_code('style');
}
add_shortcode('ta_styles', 'ta_styles_code');

/**
 * Шорткод для кнопки стихотворения
 */
function text_file_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'file' => '',
        'name' => '',
    ), $atts);
    
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

/**
 * Шорткод для пожертвований
 */
function donate_shortcode() {
    global $slug;
    if ($slug !== 'lichnyj-zal') {
		global $config;
		$minDonate = $config('donate.min');
		$maxDonate = $config('donate.max');

	   $donate_sum = $minDonate;
        
        global $user_data_global;
        $current_user_id = $user_data_global['user_id'];
        $user_email = empty($user_data_global['user_email']) ? '' : $user_data_global['user_email'];
        $user_phone = empty($user_data_global['user_phone']) ? '' : $user_data_global['user_phone'];
        $user_fio = $user_data_global['first_name'] . ' ' . $user_data_global['last_name'];
        $user_fio = empty($user_fio) ? '' : $user_fio;
        
        $d_e = '<div class="donate-block">';
        $d_e .= '<div class="donate-text">';
        $d_e .= '<p>Здесь и сейчас рождается и растёт сервис переложения стихов русских поэтов «забытых времен» на современную музыку и публикации созданных треков повсеместно в мире.<br>Хотите вписать себя в скрижали благородного почина?<br>Пожалуйста, приглашаем вас.</p>';
        $d_e .= '<div id="do-invite" class="menu-wrapper"><b><a href="#" class="popmake-1351 pum-trigger" style="cursor: pointer;">О проекте</a> <a href="#" class="popmake-1352 pum-trigger" style="cursor: pointer;">Жертвователям</a> <a href="#" class="popmake-1353 pum-trigger" style="cursor: pointer;">Спонсорам</a>';
        $d_e .= ' <a href="#" class="popmake-1478 pum-trigger" style="cursor: pointer;">О пользе</a> <a href="#" class="popmake-1476 pum-trigger" style="cursor: pointer;">Конкурс</a> <a href="#" class="popmake-1501 pum-trigger">Тонкости ИИ музыки</a> <a href="#" class="popmake-5903 pum-trigger" style="cursor: pointer;">Поддержать проект</a>';
        if ($slug !== 'zakaz-treka') {
            $d_e .= ' <a href="/zakaz-treka" target="_blank">Заказ трека</a>';
        }
        $d_e .= '</b></div></div>';
        $d_e .= '<br><p><b id="donate-title">На поддержание и развитие сайта по продвижению русского языка и культуры</b></p>';
        $d_e .= '<link rel="stylesheet" href="https://yookassa.ru/integration/simplepay/css/yookassa_construct_form.css?v=1.27.0">';
        $d_e .= '<form target="_blank" class="yoomoney-payment-form" action="https://yookassa.ru/integration/simplepay/payment" method="post" accept-charset="utf-8">';
        $d_e .= '<div class="max-donate-text">Разовая сумма - не более ' . $maxDonate . '&nbsp;₽</div>';
        $d_e .= '<div class="ym-products ym-display-none">';
        $d_e .= '<div class="ym-block-title ym-products-title">Товары</div>';
        $d_e .= '<div class="ym-product">';
        $d_e .= '<div class="ym-product-line">';
        $d_e .= '<span class="ym-product-description"><span class="ym-product-count">1×</span>Дарение на поддержку и развитие сайта poetrax.ru по продвижению русского языка и культуры</span>';
        $d_e .= '<span class="ym-product-price" data-price="' . $donate_sum . '" data-id="83" data-count="1">' . $donate_sum . '&nbsp;₽</span>';
        $d_e .= '</div>';
        $d_e .= '<input disabled="" type="hidden" name="text" value="Дарение на поддержку и развитие сайта poetrax.ru по продвижению русского языка и культуры">';
        $d_e .= '<input disabled="" type="hidden" name="price" value="' . $donate_sum . '">';
        $d_e .= '<input disabled="" type="hidden" name="quantity" value="1">';
        $d_e .= '<input disabled="" type="hidden" name="paymentSubjectType" value="commodity">';
        $d_e .= '<input disabled="" type="hidden" name="paymentMethodType" value="full_prepayment">';
        $d_e .= '<input disabled="" type="hidden" name="tax" value="1">';
        $d_e .= '</div></div>';
        $d_e .= '<input value="" type="hidden" name="ym_merchant_receipt">';
        $d_e .= '<div class="ym-customer-info">';
        $d_e .= '<div class="ym-block-title ym-display-none">О покупателе</div>';
        $d_e .= '<input name="cps_email" class="ym-input" placeholder="Email" type="text" value="' . $user_email . '">';
        $d_e .= '<input name="cps_phone" class="ym-input" placeholder="Телефон" type="text" value="' . $user_phone . '">';
        $d_e .= '<input name="custName" class="ym-input" placeholder="ФИО" type="text" value="' . $user_fio . '">';
        $d_e .= '<textarea class="ym-textarea " name="orderDetails" placeholder="Комментарий" value=""></textarea>';
        $d_e .= '</div>';
        $d_e .= '<div class="ym-hidden-inputs">';
        $d_e .= '<input name="shopSuccessURL" type="hidden" value="https://poetrax.ru/uspeshnaya-oplata">';
        $d_e .= '<input name="shopFailURL" type="hidden" value="https://poetrax.ru/oshibka-oplaty">';
        $d_e .= '</div>';
        $d_e .= '<input name="customerNumber" type="hidden" value="Дарение на развитие сайта poetrax.ru по продвижению русского языка и культуры">';
        $d_e .= '<div class="d_agreed"><input type="checkbox" id="chb_agreed" name="chb_agreed" value=""><label for="chb_agreed"> Я принимаю </label> <a href="/dogovor-pozhertvovanija" target="_blank">Публичную оферту о заключении договора пожертвования</a></div>';
        $d_e .= '<div class="ym-payment-btn-block ym-before-line ym-align-space-between">';
        $d_e .= '<div class="ym-input-icon-rub ym-display-none">';
        $d_e .= '<input name="sum" placeholder="0.00" class="ym-input ym-sum-input ym-required-input" type="number" step="any" value="' . $donate_sum . '.00">';
        $d_e .= '</div>';
        $d_e .= '<button data-text="Пожертвовать" class="ym-btn-pay ym-result-price"><span class="ym-text-crop">Пожертвовать</span>';
        $d_e .= '<span class="ym-price-output">' . $donate_sum . '&nbsp;₽</span></button><img src="https://yookassa.ru/integration/simplepay/img/iokassa-gray.svg?v=1.27.0" class="ym-logo" width="114" height="27" alt="ЮKassa">';
        $d_e .= '</div>';
        $d_e .= '<input name="shopId" type="hidden" value="1142597">';
        $d_e .= '</form>';
        $d_e .= '<script src="https://yookassa.ru/integration/simplepay/js/yookassa_construct_form.js?v=1.27.0"></script>';
        $d_e .= '<br><br></div>';
        return $d_e;
    }
    return '';
}
add_shortcode('donate', 'donate_shortcode');

/**
 * Шорткод для плейлиста треков
 */
function audio_tracks_shortcode() {
    $tracks = get_audio_tracks_from_db_wp(10, 'common');
    return drow_audio_tracks_shortcode($tracks, 'common');
}
add_shortcode('audio_playlist', 'audio_tracks_shortcode');

function audio_tracks_my_shortcode() {
    $tracks = get_audio_tracks_from_db_wp(10, 'common');
    return drow_audio_tracks_shortcode($tracks, 'my');
}
add_shortcode('audio_my_playlist', 'audio_tracks_my_shortcode');

function audio_tracks_bookmark_shortcode() {
    $tracks = get_audio_tracks_from_db_wp(10, 'common');
    return drow_audio_tracks_shortcode($tracks, 'bookmark');
}
add_shortcode('audio_bookmark_playlist', 'audio_tracks_bookmark_shortcode');

function audio_tracks_like_shortcode() {
    $tracks = get_audio_tracks_from_db_wp(10, 'common');
    return drow_audio_tracks_shortcode($tracks, 'like');
}
add_shortcode('audio_like_playlist', 'audio_tracks_like_shortcode');

add_shortcode('audio_top_short_code', 'drow_audio_tracks_shortcode');

/**
 * Шорткод для отображения текста стихотворения
 */
function poem_text_shortcode() {
    $poem_text = get_current_poem_text(1, 4);
    $poem_text = str_replace('\n', '<br>', $poem_text);
    return $poem_text;
}
add_shortcode('poem_text', 'poem_text_shortcode');

/**
 * Шорткод для отображения плеера трека
 */
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

/**
 * Шорткод для статистики кэша (только для админов)
 */
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
 * Шорткод для социальных ссылок
 */
function social_share_shortcode() {
    return '<div style="text-align:center"><div class="social-share-container" id="socialShareContainer"></div><div id=""></div></div>';
}
add_shortcode('social_share', 'social_share_shortcode');