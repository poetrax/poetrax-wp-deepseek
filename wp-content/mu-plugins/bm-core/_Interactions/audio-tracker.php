<?php
if (!defined('ABSPATH')) exit;

// Получение статистики прослушивания
function get_audio_listening_stats($track_id = null, $user_id = null) {

    $table_name = 'bm_ctbl000_interaction';
    
    $where = ["type = 'play'"];
    
    if ($track_id) {
        $where[] = $wpdb->prepare("track_id = %d", $track_id);
    }
    
    if ($user_id) {
        $where[] = $wpdb->prepare("user_id = %d", $user_id);
    }
    
    $where_clause = implode(' AND ', $where);
    
    return $wpdb->get_results("
        SELECT 
            track_id,
            user_id,
            SUM(play_duration) as total_duration,
            COUNT(*) as play_count,
            MAX(created_at) as last_played
        FROM $table_name 
        WHERE $where_clause
        GROUP BY track_id, user_id
        ORDER BY last_played DESC
    ");
}

// Получение топ треков
function get_top_audio_tracks($limit = 10) {
  
    $table_name = $wpdb->prefix . 'ctbl000_interaction';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT 
            track_id,
            SUM(play_duration) as total_duration,
            COUNT(DISTINCT user_id) as unique_listeners,
            COUNT(*) as total_plays
        FROM $table_name 
        WHERE type = 'play'
        GROUP BY track_id
        ORDER BY total_duration DESC
        LIMIT %d
    ", $limit));
}




function check_user_has_liked($track_id, $user_id) {
    if (!$user_id) return false;
    
 
    $table_name = $wpdb->prefix . 'track_interactions';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE track_id = %d AND user_id = %d AND interaction_type = 'like'",
        $track_id, $user_id
    ));
    
    return $result > 0;
}

function check_user_has_bookmarked($track_id, $user_id) {
    if (!$user_id) return false;
    
  
    $table_name = $wpdb->prefix . 'track_interactions';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE track_id = %d AND user_id = %d AND interaction_type = 'bookmark'",
        $track_id, $user_id
    ));
    
    return $result > 0;
}



class AudioTracker {
    public function __construct() {
        // Register custom post type for audio tracks
        add_action('init', array($this, 'register_audio_post_type'));
        
        // Add shortcode for audio player
        add_shortcode('audio_player', array($this, 'audio_player_shortcode'));
        
        // Register API endpoints
        add_action('rest_api_init', array($this, 'register_api_routes'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function register_audio_post_type() {
        register_post_type('audio_track',
            array(
                'labels' => array(
                    'name' => __('Audio Tracks'),
                    'singular_name' => __('Audio Track')
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'thumbnail'),
                'show_in_rest' => true,
            )
        );
    }
    
    public function enqueue_assets() {
        wp_enqueue_script('audio-tracker', plugin_dir_url(__FILE__) . 'js/audio-tracker.js', array(), '1.0', true);
        wp_enqueue_style('audio-tracker', plugin_dir_url(__FILE__) . 'css/audio-tracker.css');
        
        wp_localize_script('audio-tracker', 'audioTracker', array(
            'api_url' => rest_url('audio-tracker/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    public function audio_player_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'url' => '',
            'title' => ''
        ), $atts);
        
        if (empty($atts['url'])) return '';
        
        // Insert or update track in database
        $track_id = $this->get_or_create_track($atts);
        
        ob_start();
        ?>
        <div class="audio-player-container" data-track-id="<?php echo $track_id; ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <audio controls class="audio-track">
                <source src="<?php echo esc_url($atts['url']); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            <div class="audio-controls">
                <button class="like-button">Like</button>
                <span class="like-count">0</span> likes
            </div>
            <div class="play-stats">
                <span class="play-count">0</span> plays
            </div>
            <div class="visualization">
                <canvas class="waveform" width="400" height="100"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_or_create_track($data) {
      
        
        // Check if track exists
        //HACK audio_url
        $track_id = $wpdb->get_var($wpdb->prepare(
            "SELECT track_id FROM bm_ctbl000_track WHERE track_path = %s",
            $data['url']
        ));
        
        if ($track_id) return $track_id;
        
        // Create new track
        $wpdb->insert("bm_ctbl000_track", array(
            'post_id' => $data['id'],
            'track_path' => $data['track_path'],
            'track_name' => $data['track_name']
        ));
        
        return $wpdb->insert_id;
    }
    
    public function register_api_routes() {
        register_rest_route('audio-tracker/v1', '/track-play', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_play'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('audio-tracker/v1', '/track-like', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_like'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('audio-tracker/v1', '/get-stats/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function track_play(WP_REST_Request $request) {
        $track_id = $request->get_param('track_id');
        $duration = $request->get_param('track_duration');
        
       
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $wpdb->insert("bm_ctbl000_interaction", array(
            'track_id' => $track_id,
            'user_id' => $user_id,
            'ip' => $ip_address,
            'type'=>'play',
            'play_duration' => $duration
        ));
        
        return new WP_REST_Response(array('success' => true), 200);
    }
    
    public function track_like(WP_REST_Request $request) {
        $track_id = $request->get_param('track_id');
        
      
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Check if user already liked this track
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT like_id FROM bm_ctbl000_interaction 
            WHERE track_id = %d AND type='like' (user_id = %d OR ip = %s)",
            $track_id,
            $user_id,
            $ip_address
        ));
        
        if ($existing) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Already liked'), 400);
        }
        
        $wpdb->insert("bm_ctbl000_interaction", array(
            'track_id' => $track_id,
            'user_id' => $user_id,
            'type'=>'like',
            'ip' => $ip_address
        ));
        
        return new WP_REST_Response(array('success' => true), 200);
    }
    
    public function get_stats(WP_REST_Request $request) {
        $track_id = $request->get_param('id');
        
       
        
        $play_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type='play' AND track_id = %d",
            $track_id
        ));
        
        $like_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type='like' AND  track_id = %d",
            $track_id
        ));

        $view_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type='view' AND  track_id = %d",
            $track_id
        ));

        $bookmark_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type='bookmark' AND  track_id = %d",
            $track_id
        ));


        
        return new WP_REST_Response(array(
            'count_play' => $play_count,
            'count_like' => $like_count,
            'count_view' => $view_count,
            'count_bookmark' => $bookmark_count
        ), 200);
    }
}

new AudioTracker();
