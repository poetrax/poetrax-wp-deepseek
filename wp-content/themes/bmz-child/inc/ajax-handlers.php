<?php
// inc/ajax-handlers.php
// Все AJAX обработчики

/**
 * Получение аудио треков через AJAX
 */
function get_audio_tracks_ajax() {
    check_ajax_referer('audio_tracks_nonce', 'security');
    $tracks = get_audio_tracks_from_db_wp(10, 'common');
    wp_send_json($tracks);
}
add_action('wp_ajax_get_audio_tracks', 'get_audio_tracks_ajax');
add_action('wp_ajax_nopriv_get_audio_tracks', 'get_audio_tracks_ajax');

/**
 * Загрузка текстового файла для попапа
 */
function load_text_file_content() {
    try {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'text_file_nonce')) {
            throw new Exception('Security check failed');
        }
        
        $file_path = sanitize_text_field($_POST['file_path'] ?? '');
        if (empty($file_path)) {
            throw new Exception('File path is empty');
        }
        
        $upload_dir = wp_upload_dir();
        $full_path = path_join($upload_dir['basedir'], $file_path);
        
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
        $content = esc_html($content);
        
        wp_send_json_success(['content' => $content, 'file_size' => strlen($content)], 200);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
    wp_die();
}
add_action('wp_ajax_load_text_file', 'load_text_file_content');
add_action('wp_ajax_nopriv_load_text_file', 'load_text_file_content');

/**
 * Переключение взаимодействия (лайк/закладка)
 */
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
        wp_send_json_success(['message' => 'success', 'stats' => $stats]);
    } else {
        wp_send_json_error(['message' => 'Ошибка']);
    }
}
add_action('wp_ajax_bm_toggle_interaction', 'bm_toggle_interaction');
add_action('wp_ajax_nopriv_bm_toggle_interaction', 'bm_toggle_interaction');

/**
 * Запись прослушивания трека
 */
function bm_record_play() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $user_id = get_current_user_id();
    
    $interaction_service = new BM\Services\InteractionService();
    $interaction_service->record_play($track_id, $user_id);
    
    wp_send_json_success();
}
add_action('wp_ajax_bm_record_play', 'bm_record_play');
add_action('wp_ajax_nopriv_bm_record_play', 'bm_record_play');

/**
 * Получение статистики трека
 */
function bm_get_track_stats() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    
    $interaction_service = new BM\Services\InteractionService();
    $stats = $interaction_service->get_track_stats($track_id);
    
    wp_send_json_success($stats);
}
add_action('wp_ajax_bm_get_track_stats', 'bm_get_track_stats');
add_action('wp_ajax_nopriv_bm_get_track_stats', 'bm_get_track_stats');

/**
 * Получение данных стихотворения для модального окна
 */
function bm_get_poem_data() {
    check_ajax_referer('bm_ajax_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $poem_id = intval($_POST['poem_id']);
    
    if ($track_id && !$poem_id) {
   
        $track = $wpdb->get_row($wpdb->prepare(
            "SELECT poem_id, poet_id FROM " . BM_TE_TABLE_TRACK . " WHERE id = %d",
            $track_id
        ));
        $poem_id = $track ? $track->poem_id : 0;
    }
    
    if (!$poem_id) {
        wp_send_json_error(['message' => 'Стихотворение не найдено']);
    }
    
    $poem_repo = new BM\Core\Repository\PoemRepository();
    $poem = $poem_repo->find($poem_id);
    
    if (!$poem) {
        wp_send_json_error(['message' => 'Стихотворение не найдено']);
    }
    
    $poet_name = '';
    if ($poem->poet_id) {
        $poet_repo = new BM\Core\Repository\PoemRepository();
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
add_action('wp_ajax_bm_get_poem_data', 'bm_get_poem_data');
add_action('wp_ajax_nopriv_bm_get_poem_data', 'bm_get_poem_data');

/**
 * Получение треков поэта
 */
function get_poet_tracks_callback() {
    while (ob_get_level()) {
        ob_end_clean();
    }


    
    error_log('AJAX запрос get_poet_tracks получен');
    error_log('POST данные: ' . print_r($_POST, true));
    
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
    
    $track_table = 'bm_ctbl000_track';
    $poem_table = 'bm_ctbl000_poem';
    
    error_log("Используемые таблицы: {$track_table}, {$poem_table}");
    
    $track_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$track_table}'") === $track_table;
    $poem_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$poem_table}'") === $poem_table;
    
    error_log("Таблица треков существует: " . ($track_table_exists ? 'да' : 'нет'));
    error_log("Таблица стихов существует: " . ($poem_table_exists ? 'да' : 'нет'));
    
    if (!$track_table_exists || !$poem_table_exists) {
        error_log('Одна или обе таблицы не существуют');
        wp_send_json_error('Tables not found');
    }
    
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
        
        if (empty($poet_name)) {
            wp_send_json_error('Поэт не найден');
        }
        
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
add_action('wp_ajax_get_poet_tracks', 'get_poet_tracks_callback');
add_action('wp_ajax_nopriv_get_poet_tracks', 'get_poet_tracks_callback');