<?php
add_action('rest_api_init', function() {
    register_rest_route('bestmz/v1', '/track/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_track_data_api',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
    
    register_rest_route('bestmz/v1', '/track/batch', [
        'methods' => 'POST',
        'callback' => 'get_batch_tracks_api',
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('bestmz/v1', '/track/view', [
        'methods' => 'POST',
        'callback' => 'track_view_api',
        'permission_callback' => '__return_true',
    ]);
});

function get_track_data_api(WP_REST_Request $request) {
    $track_id = $request->get_param('id');
    $minimal = $request->get_param('minimal') === 'true';
    
    try {
        $handler = new UltraMinimalUrlHandler(get_pdo_Connection());
        $data = $handler->getFullTrackData($track_id);
        
        if (!$data) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Track not found'
            ], 404);
        }
        
        // Минимальный ответ для некоторых случаев
        if ($minimal) {
            $data = [
                'id' => $data['id'],
                'name' => $data['track_name'],
                'url' => $handler->generateUrl($data['id']),
                'duration' => $data['duration_formatted'],
                'image' => $data['image_url'] ?? null
            ];
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'cache' => 'hit' // или 'miss'
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

function get_batch_tracks_api(WP_REST_Request $request) {
    $track_ids = $request->get_param('ids') ?: [];
    $track_ids = array_map('absint', explode(',', $track_ids));
    
    if (empty($track_ids)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'No track IDs provided'
        ], 400);
    }
    
    // Ограничиваем количество
    $track_ids = array_slice($track_ids, 0, 50);
    
    try {
        $handler = new UltraMinimalUrlHandler(get_pdo_Connection());
        $data = $handler->getMultipleTracksData($track_ids);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

function track_view_api(WP_REST_Request $request) {
    $track_id = $request->get_param('track_id');
    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $referrer = $request->get_param('referrer') ?: $_SERVER['HTTP_REFERER'] ?? '';
    
    // Логируем просмотр
    global $wpdb;
    $table = $wpdb->'bm_ctbl000_interaction';
    
    $wpdb->insert($table, [
        'track_id' => $track_id,
        'user_id' => $user_id,
        'ip' => $ip,
        'referrer' => substr($referrer, 0, 500),
        'created_at' => current_time('mysql'),
        'type' => 'view',
    ]);
    
    // Обновляем счетчик
    $wpdb->query($wpdb->prepare(
        "UPDATE bm_ctbl000_track 
         SET count_view = count_view + 1 
         WHERE id = %d",
        $track_id
    ));
    
    return new WP_REST_Response(['success' => true], 200);
}
