<?php
class TrackInteraction {
    
    private $current_user_id;
    private $table_track = 'bm_ctbl000_track';
    private $table_interactions = 'bm_ctbl000_interaction';

    public function __construct() {
        $this->current_user_id = get_current_user_id();
        $this->initPDO();
    }

    private function initPDO() {
        global $pdo;
        $this->pdo = $pdo;
    }	


    public function has_interaction($track_id, $type) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM {$this->table_interactions} 
             WHERE track_id = :track_id AND user_id = :user_id AND type = :type"
        );
        
        $stmt->execute([
            ':track_id' => $track_id,
            ':user_id' => $this->current_user_id,
            ':type' => $type
        ]);
        
        $result = $stmt->fetch();
        return (int) $result['count'] > 0;
    }

    public function add_interaction($track_id, $type, $ip = null, $play_duration = null) {
        // Проверяем уникальность для like и bookmark
        if (in_array($type, ['like', 'bookmark']) && $this->has_interaction($track_id, $type)) {
            return ['success' => false, 'message' => 'Already exists'];
        }

        // Для audio проверяем владельца трека
        if ($type === 'audio') {
            $stmt = $this->pdo->prepare(
                "SELECT user_id FROM {$this->table_track} WHERE id = :track_id"
            );
            $stmt->execute([':track_id' => $track_id]);
            $track = $stmt->fetch();

            if ($track && $track['user_id'] == $this->current_user_id) {
                return ['success' => false, 'message' => 'Cannot track own plays'];
            }
        }

        $sql = "INSERT INTO {$this->table_interactions} 
                (track_id, user_id, type, ip, play_duration, created_at) 
                VALUES (:track_id, :user_id, :type, :ip, :play_duration, NOW())";

        $stmt = $this->pdo->prepare($sql);
        
        $data = [
            ':track_id' => $track_id,
            ':user_id' => $this->current_user_id,
            ':type' => $type,
            ':ip' => $ip,
            ':play_duration' => $play_duration
        ];

        try {
            $stmt->execute($data);
            return ['success' => true, 'message' => 'Added successfully'];
        } catch (PDOException $e) {
            error_log('PDO Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function remove_interaction($track_id, $type) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table_interactions} 
             WHERE track_id = :track_id AND user_id = :user_id AND type = :type"
        );

        try {
            $stmt->execute([
                ':track_id' => $track_id,
                ':user_id' => $this->current_user_id,
                ':type' => $type
            ]);
            
            return ['success' => $stmt->rowCount() > 0, 'message' => 'Removed successfully'];
        } catch (PDOException $e) {
            error_log('PDO Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function get_track_stats($track_id) {
        $stats = [
            'likes' => 0,
            'bookmarks' => 0,
            'plays' => 0,
            'user_has_liked' => false,
            'user_has_bookmarked' => false,
            'total_listening_time' => 0
        ];

        // Получаем общую статистику
        $stmt = $this->pdo->prepare(
            "SELECT type, COUNT(*) as count, COALESCE(SUM(play_duration), 0) as total_duration 
             FROM {$this->table_interactions} 
             WHERE track_id = :track_id 
             GROUP BY type"
        );
        
        $stmt->execute([':track_id' => $track_id]);
        $results = $stmt->fetchAll();

        foreach ($results as $row) {
            switch ($row['type']) {
                case 'like':
                    $stats['likes'] = (int) $row['count'];
                    break;
                case 'bookmark':
                    $stats['bookmarks'] = (int) $row['count'];
                    break;
                case 'audio':
                    $stats['plays'] = (int) $row['count'];
                    $stats['total_listening_time'] = (int) $row['total_duration'];
                    break;
            }
        }

        // Проверяем пользовательские взаимодействия
        if ($this->current_user_id) {
            $stats['user_has_liked'] = $this->has_interaction($track_id, 'like');
            $stats['user_has_bookmarked'] = $this->has_interaction($track_id, 'bookmark');
        }
        return $stats;
    }

    public function get_track_info($track_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table_track} WHERE id = :track_id"
        );
        
        $stmt->execute([':track_id' => $track_id]);
        return $stmt->fetch();
    }


    public function create_thambnail($track_id) {
   //
        $info_track = get_track_info($track_id);
        if ($current_user_id=$info_track['user_id']) {
        
        }

   }


}

// AJAX обработчики
add_action('wp_ajax_track_interaction', 'handle_ajax_track_interaction');
add_action('wp_ajax_nopriv_track_interaction', 'handle_ajax_track_interaction');
add_action('wp_ajax_get_track_stats', 'handle_ajax_get_track_stats');
add_action('wp_ajax_nopriv_get_track_stats', 'handle_ajax_get_track_stats');

function handle_ajax_track_interaction() {
    check_ajax_referer('track_interactions_nonce', 'nonce');
    
    $track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $type = isset($_POST['interaction_type']) ? sanitize_text_field($_POST['interaction_type']) : '';
    $play_duration = isset($_POST['play_duration']) ? intval($_POST['play_duration']) : null;

    if (!$track_id || !in_array($type, ['like', 'bookmark', 'audio'])) {
        wp_send_json_error('Invalid parameters');
        return;
    }

    $interactions = new TrackInteraction();
    $response = null;

    if ($action === 'add') {
        $ip = $type === 'audio' ? $_SERVER['REMOTE_ADDR'] : null;
        $response = $interactions->add_interaction($track_id, $type, $ip, $play_duration);
    } elseif ($action === 'remove' && in_array($type, ['like', 'bookmark'])) {
        $response = $interactions->remove_interaction($track_id, $type);
    } else {
        wp_send_json_error('Invalid action');
        return;
    }

    if ($response['success']) {
        $stats = $interactions->get_track_stats($track_id);
        wp_send_json_success([
            'message' => $response['message'],
            'stats' => $stats
        ]);
    } else {
        wp_send_json_error($response['message']);
    }
}

function handle_ajax_get_track_stats() {
    $track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
    
    if (!$track_id) {
        wp_send_json_error('Invalid track ID');
        return;
    }

    $interactions = new TrackInteraction();
    $stats = $interactions->get_track_stats($track_id);
    
    wp_send_json_success($stats);
}

 
/*
/* Track interaction trace */
/*
// AJAX обработчики
add_action('wp_ajax_get_track_stats', 'handle_get_track_stats');
add_action('wp_ajax_nopriv_get_track_stats', 'handle_get_track_stats');

add_action('wp_ajax_track_interaction', 'handle_track_interaction');
add_action('wp_ajax_nopriv_track_interaction', 'handle_track_interaction_nopriv');

function handle_get_track_stats() {
    check_ajax_referer('track_interactions_nonce', 'nonce');
    
    $track_id = intval($_POST['track_id']);
    $user_id = get_current_user_id();
    
    if (!$track_id) {
        wp_send_json_error('Invalid track ID');
        return;
    }
    
    $stats = array(
        'likes' => get_track_likes_count($track_id),
        'bookmarks' => get_track_bookmarks_count($track_id),
        'plays' => get_track_plays_count($track_id),
        'total_listening_time' => get_track_listening_time($track_id),
        'user_has_liked' => check_user_has_liked($track_id, $user_id),
        'user_has_bookmarked' => check_user_has_bookmarked($track_id, $user_id)
    );
    
    wp_send_json_success($stats);
}

function handle_track_interaction() {
    check_ajax_referer('track_interactions_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $track_id = intval($_POST['track_id']);
    $action_type = sanitize_text_field($_POST['action_type']);
    $interaction_type = sanitize_text_field($_POST['interaction_type']);
    $play_duration = isset($_POST['play_duration']) ? intval($_POST['play_duration']) : null;
    
    // Обработка взаимодействия...
    // (ваша существующая логика обработки взаимодействий)
}


//TODO check it work
add_action('wp_enqueue_scripts', 'track_interactions_scripts');
function track_interactions_scripts() {
    // Основной скрипт
    wp_enqueue_script(
        'track-interactions', 
        get_template_directory_uri() . '/js/track-interactions.js', 
        [], 
        '1.0', 
        true
    );
    
    // Локализация для WordPress
    wp_localize_script('track-interactions', 'trackInteractions', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('track_interactions_nonce')
    ]);
    
    // Добавляем inline стили для обратной связи //TODO Убрать это в style.css
    wp_add_inline_style('your-theme-style', '
        .interaction-feedback {
            color: #28a745;
            margin-left: 8px;
            font-size: 12px;
        }
        .interaction-error {
            color: #dc3545;
            margin-left: 8px;
            font-size: 12px;
        }
        .interaction-btn.active {
            background-color: #ff4757;
            color: white;
            border-color: #ff4757;
        }
    ');
}
//TODO check it work
*/
/* Track interaction trace */

