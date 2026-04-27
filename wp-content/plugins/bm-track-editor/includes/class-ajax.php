<?php
use BM\Database\Connection;
use BM\Core\Database\TableMapper;

/**
 * AJAX обработчики
 */
class BM_TE_Ajax {

    public static function init() {
        $actions = [
            'bm_save_track',
            'bm_get_track',
            'bm_delete_track',
            'bm_upload_audio',
            'bm_search_poems',
            'bm_get_poets',
            'bm_get_stats'
        ];
        
        foreach ($actions as $action) {
            add_action("wp_ajax_{$action}", [__CLASS__, $action]);
            add_action("wp_ajax_nopriv_{$action}", [__CLASS__, $action]);
        }
    }
    
    /**
     * Сохранение трека
     */
    public static function bm_save_track() {
        self::verify_nonce();
        
        $data = $_POST;
        $track_id = isset($data['track_id']) ? intval($data['track_id']) : 0;
        
        // Базовые поля трека
        $track_data = [
            'track_name' => sanitize_text_field($data['track_name'] ?? ''),
            'poet_id' => intval($data['poet_id'] ?? 0),
            'poem_id' => intval($data['poem_id'] ?? 0),
            'mood_id' => intval($data['mood_id'] ?? 0),
            'theme_id' => intval($data['theme_id'] ?? 0),
            'genre_id' => intval($data['genre_id'] ?? 0),
            'track_duration' => intval($data['track_duration'] ?? 0),
            'track_file_path' => esc_url_raw($data['track_file_path'] ?? ''),
            'updated_at' => current_time('mysql'),
        ];
        
        if ($track_id) {
            // Обновление
            $result = Connection::update('track', $track_data, ['id' => $track_id]);
            $message = 'Трек обновлен';
        } else {
            // Создание
            $track_data['created_at'] = current_time('mysql');
            $track_data['user_id'] = get_current_user_id();
            $track_id = Connection::insert('track', $track_data);
            $message = 'Трек создан';
        }
        
        if ($track_id) {
            wp_send_json_success([
                'id' => $track_id,
                'message' => $message
            ]);
        } else {
            wp_send_json_error(['message' => 'Ошибка сохранения']);
        }
    }
    
    /**
     * Получение данных трека
     */
    public static function bm_get_track() {
        self::verify_nonce();
        
        $track_id = intval($_POST['track_id'] ?? 0);
        
        if (!$track_id) {
            wp_send_json_error(['message' => 'ID трека не указан']);
        }
        
        $track = Connection::row(
            "SELECT * FROM " . TableMapper::getInstance()->get('track') . " WHERE id = %d",
            [$track_id]
        );
        
        if ($track) {
            // Получаем музыкальные детали
            $details = Connection::row(
                "SELECT * FROM " . TableMapper::getInstance()->get('music_detail') . " WHERE track_id = %d",
                [$track_id]
            );
            
            wp_send_json_success([
                'track' => $track,
                'details' => $details
            ]);
        } else {
            wp_send_json_error(['message' => 'Трек не найден']);
        }
    }
    
    /**
     * Удаление трека
     */
    public static function bm_delete_track() {
        self::verify_nonce();
        
        $track_id = intval($_POST['track_id'] ?? 0);
        
        if (!$track_id) {
            wp_send_json_error(['message' => 'ID трека не указан']);
        }
        
        // Удаляем музыкальные детали
        Connection::delete('music_detail', ['track_id' => $track_id]);
        
        // Удаляем трек
        $result = Connection::delete('track', ['id' => $track_id]);
        
        if ($result) {
            wp_send_json_success(['message' => 'Трек удален']);
        } else {
            wp_send_json_error(['message' => 'Ошибка удаления']);
        }
    }
    
    /**
     * Загрузка аудиофайла
     */
    public static function bm_upload_audio() {
        self::verify_nonce();
        
        if (!isset($_FILES['audio_file'])) {
            wp_send_json_error(['message' => 'Файл не загружен']);
        }
        
        $file = $_FILES['audio_file'];
        $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'Неверный формат файла']);
        }
        
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name($file['name']);
        $upload_path = $upload_dir['path'] . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            wp_send_json_success([
                'url' => $upload_dir['url'] . '/' . $filename,
                'path' => $upload_path,
                'name' => $filename,
                'size' => $file['size']
            ]);
        } else {
            wp_send_json_error(['message' => 'Ошибка загрузки файла']);
        }
    }
    
    /**
     * Поиск стихов
     */
    public static function bm_search_poems() {
        self::verify_nonce();
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $poet_id = intval($_POST['poet_id'] ?? 0);
        
        if (strlen($query) < 3) {
            wp_send_json_error(['message' => 'Слишком короткий запрос']);
        }
        
        $sql = "SELECT p.*, pt.short_name as poet_name 
                FROM " . TableMapper::getInstance()->get('poem') . " p
                LEFT JOIN " . TableMapper::getInstance()->get('poet') . " pt ON p.poet_id = pt.id
                WHERE p.name LIKE %s";
        
        $params = ['%' . $wpdb->esc_like($query) . '%'];
        
        if ($poet_id) {
            $sql .= " AND p.poet_id = %d";
            $params[] = $poet_id;
        }
        
        $sql .= " LIMIT 20";
        
        $poems = Connection::query($sql, $params);
        
        wp_send_json_success($poems);
    }
    
    /**
     * Получение списка поэтов
     */
    public static function bm_get_poets() {
        self::verify_nonce();
        
        $poets = Connection::query(
            "SELECT id, short_name, last_name, first_name 
             FROM " . TableMapper::getInstance()->get('poet') . " 
             WHERE is_active = 1 
             ORDER BY last_name"
        );
        
        wp_send_json_success($poets);
    }
    
    /**
     * Получение статистики
     */
    public static function bm_get_stats() {
        self::verify_nonce();
        
        global $wpdb;
        
        $stats = [
            'total_tracks' => Connection::var("SELECT COUNT(*) FROM " . TableMapper::getInstance()->get('track')),
            'total_poems' => Connection::var("SELECT COUNT(*) FROM " . TableMapper::getInstance()->get('poem')),
            'total_poets' => Connection::var("SELECT COUNT(*) FROM " . TableMapper::getInstance()->get('poet')),
            'total_plays' => Connection::var("SELECT COUNT(*) FROM " . TableMapper::getInstance()->get('interaction') . " WHERE type = 'play'"),
            'recent_tracks' => Connection::query(
                "SELECT t.*, p.short_name as poet_name 
                 FROM " . TableMapper::getInstance()->get('track') . " t
                 LEFT JOIN " . TableMapper::getInstance()->get('poet') . " p ON t.poet_id = p.id
                 ORDER BY t.created_at DESC
                 LIMIT 5"
            )
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Проверка nonce
     */
    private static function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bm_te_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности']);
        }
    }



    /**
 * AJAX обработчики комментариев
 */
public static function init_comment_handlers() {
    add_action('wp_ajax_bm_submit_comment', [__CLASS__, 'submit_comment']);
    add_action('wp_ajax_nopriv_bm_submit_comment', [__CLASS__, 'submit_comment']);
    
    add_action('wp_ajax_bm_load_more_comments', [__CLASS__, 'load_more_comments']);
    add_action('wp_ajax_nopriv_bm_load_more_comments', [__CLASS__, 'load_more_comments']);
    
    add_action('wp_ajax_bm_like_comment', [__CLASS__, 'like_comment']);
    add_action('wp_ajax_nopriv_bm_like_comment', [__CLASS__, 'like_comment']);
}

/**
 * Отправка комментария
 */
public static function submit_comment() {
    self::verify_nonce();
    
    $track_id = intval($_POST['track_id']);
    $parent_id = intval($_POST['parent_id'] ?? 0);
    
    // Базовая валидация
    if (empty($_POST['author'])) {
        wp_send_json_error(['message' => 'Введите имя']);
    }
    
    if (empty($_POST['content'])) {
        wp_send_json_error(['message' => 'Введите текст комментария']);
    }
    
    if (!empty($_POST['email']) && !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Некорректный email']);
    }
    
    // Подготовка данных
    $comment_data = [
        'track_id' => $track_id,
        'parent_id' => $parent_id,
        'author' => sanitize_text_field($_POST['author']),
        'author_email' => sanitize_email($_POST['email'] ?? ''),
        'author_url' => esc_url_raw($_POST['url'] ?? ''),
        'content' => wp_kses_post($_POST['content']),
        'user_id' => get_current_user_id(),
        'is_approved' => self::is_comment_auto_approved() ? 1 : 0,
    ];
    
    $comment_service = new \BM\Services\CommentService();
    $comment_id = $comment_service->addComment($track_id, $comment_data);
    
    if ($comment_id) {
        // Получаем обновлённое дерево
        $tree_html = $comment_service->renderTree($track_id);
        $count = $comment_service->comment_repo->getCount($track_id);
        
        wp_send_json_success([
            'comment_id' => $comment_id,
            'html' => $tree_html,
            'count' => $count,
            'message' => 'Комментарий добавлен' . 
                ($comment_data['is_approved'] ? '' : ' и ожидает модерации')
        ]);
    }
    
    wp_send_json_error(['message' => 'Ошибка сохранения']);
}

/**
 * Загрузка ещё комментариев
 */
public static function load_more_comments() {
    self::verify_nonce();
    
    $track_id = intval($_POST['track_id']);
    $page = intval($_POST['page'] ?? 1);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $comment_repo = new \BM\Repositories\CommentRepository();
    $comments = $comment_repo->getFlat($track_id, true, $per_page, $offset);
    $total = $comment_repo->getCount($track_id);
    
    ob_start();
    foreach ($comments as $comment) {
        include BM_TE_PLUGIN_DIR . 'templates/comments/comment.php';
    }
    $html = ob_get_clean();
    
    wp_send_json_success([
        'html' => $html,
        'has_more' => ($offset + $per_page) < $total,
        'page' => $page
    ]);
}

/**
 * Лайк комментария
 */
public static function like_comment() {
    self::verify_nonce();
    
    $comment_id = intval($_POST['comment_id']);
    
    // Получаем текущий счётчик лайков
    $likes = (int) get_comment_meta($comment_id, 'likes', true);
    $likes++;
    
    update_comment_meta($comment_id, 'likes', $likes);
    
    wp_send_json_success([
        'likes' => $likes
    ]);
}

/**
 * Проверка авто-одобрения
 */
private static function is_comment_auto_approved() {
    // Админы всегда одобрены
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Проверяем по настройкам
    return get_option('comment_moderation') ? false : true;
}


}