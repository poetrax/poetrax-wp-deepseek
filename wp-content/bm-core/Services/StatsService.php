<?php
namespace BM\Services;

use BM\Database\Connection;
use BM\Database\Cache;






class StatsService {
    
    /**
     * Общая статистика по сайту
     */
    public function getGlobalStats() {
        $cache_key = ['stats', 'global'];
        $stats = Cache::get($cache_key);
        
        if (!$stats) {
            $stats = [
                'tracks' => $this->getTotalCount('track'),
                'poems' => $this->getTotalCount('poem'),
                'poets' => $this->getTotalCount('poet'),
                'images' => $this->getTotalCount('img'),
                'docs' => $this->getTotalCount('doc'),
                'users' => $this->getTotalCount('user'),
                'plays_today' => $this->getPlaysToday(),
                'popular_today' => $this->getPopularToday(),
            ];
            
            Cache::set($cache_key, $stats, 3600);
        }
        
        return $stats;
    }
    
    /**
     * Статистика по поэту
     */
    public function getPoetStats($poet_id) {
        $cache_key = ['stats', 'poet', $poet_id];
        $stats = Cache::get($cache_key);
        
        if (!$stats) {
            global $wpdb;
            
            $stats = (object)[
                'poems_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM " . Connection::table('poem') . " WHERE poet_id = %d AND is_active = 1",
                    $poet_id
                )),
                'tracks_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM " . Connection::table('track') . " WHERE poet_id = %d AND is_active = 1 AND is_approved = 1",
                    $poet_id
                )),
                'total_plays' => $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM " . Connection::table('interaction') . " i
                    JOIN " . Connection::table('track') . " t ON i.track_id = t.id
                    WHERE t.poet_id = %d AND i.type = 'play'
                ", $poet_id)),
                'popular_track' => $wpdb->get_row($wpdb->prepare("
                    SELECT t.*, COUNT(i.id) as plays
                    FROM " . Connection::table('track') . " t
                    JOIN " . Connection::table('interaction') . " i ON t.id = i.track_id
                    WHERE t.poet_id = %d AND i.type = 'play'
                    GROUP BY t.id
                    ORDER BY plays DESC
                    LIMIT 1
                ", $poet_id))
            ];
            
            Cache::set($cache_key, $stats, 1800);
        }
        
        return $stats;
    }
    
    /**
     * Топ треков за период
     */
    public function getTopTracks($period = 'week', $limit = 10) {
        $cache_key = ['stats', 'top_tracks', $period, $limit];
        $tracks = Cache::get($cache_key);
        
        if (!$tracks) {
            $date_condition = $this->getPeriodCondition($period);
            
            global $wpdb;
            $tracks = $wpdb->get_results($wpdb->prepare("
                SELECT t.*, COUNT(i.id) as interaction_count
                FROM " . Connection::table('track') . " t
                JOIN " . Connection::table('interaction') . " i ON t.id = i.track_id
                WHERE i.type = 'play' AND $date_condition
                GROUP BY t.id
                ORDER BY interaction_count DESC
                LIMIT %d
            ", $limit));
            
            Cache::set($cache_key, $tracks, 900); // 15 минут
        }
        
        return $tracks;
    }
    
    public function getPeriodCondition($period) {
        switch ($period) {
            case 'day':
                return "i.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case 'week':
                return "i.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'month':
                return "i.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            default:
                return "1=1";
        }
    }
    
    public function getTotalCount($table) {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM " . Connection::table($table));
    }
    
    public function getPlaysToday() {
        global $wpdb;
        return (int)$wpdb->get_var("
            SELECT COUNT(*) 
            FROM " . Connection::table('interaction') . "
            WHERE type = 'play' 
                AND DATE(created_at) = CURDATE()
        ");
    }
    
    public function getPopularToday() {
        global $wpdb;
        return $wpdb->get_results("
            SELECT t.track_name, COUNT(i.id) as plays
            FROM " . Connection::table('interaction') . " i
            JOIN " . Connection::table('track') . " t ON i.track_id = t.id
            WHERE i.type = 'play' AND DATE(i.created_at) = CURDATE()
            GROUP BY t.id
            ORDER BY plays DESC
            LIMIT 5
        ");
    }


 /**
     * Получить общую статистику
     */
    public static function get_global_stats() {
        global $wpdb;
        
        return [
            'total_tracks' => Connection::var("SELECT COUNT(*) FROM " . BM_TE_TABLE_TRACK),
            'total_poems' => Connection::var("SELECT COUNT(*) FROM " . BM_TE_TABLE_POEM),
            'total_poets' => Connection::var("SELECT COUNT(*) FROM " . BM_TE_TABLE_POET),
            'total_plays' => Connection::var("SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type = 'play'"),
            'total_likes' => Connection::var("SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type = 'like'"),
            'total_bookmarks' => Connection::var("SELECT COUNT(*) FROM bm_ctbl000_interaction WHERE type = 'bookmark'"),
            'plays_today' => Connection::var("
                SELECT COUNT(*) FROM bm_ctbl000_interaction 
                WHERE type = 'play' AND DATE(created_at) = CURDATE()
            "),
        ];
    }
    

    /**
     * Получить статистику по треку
     */
    public static function get_track_stats($track_id) {
        return Connection::row("
            SELECT 
                COUNT(CASE WHEN type = 'play' THEN 1 END) as plays,
                COUNT(CASE WHEN type = 'like' THEN 1 END) as likes,
                COUNT(CASE WHEN type = 'bookmark' THEN 1 END) as bookmarks
            FROM bm_ctbl000_interaction
            WHERE track_id = %d
        ", [$track_id]);
    }
    
    /**
     * Получить статистику по поэту
     */
    public static function get_poet_stats($poet_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as poems_count,
                COUNT(DISTINCT t.id) as tracks_count,
                COUNT(i.id) as total_plays
            FROM " . BM_TE_TABLE_POET . " pt
            LEFT JOIN " . BM_TE_TABLE_POEM . " p ON pt.id = p.poet_id
            LEFT JOIN " . BM_TE_TABLE_TRACK . " t ON pt.id = t.poet_id
            LEFT JOIN bm_ctbl000_interaction i ON t.id = i.track_id AND i.type = 'play'
            WHERE pt.id = %d
        ", $poet_id));
    }
    
    /**
     * Записать взаимодействие
     */
    public static function record_interaction($track_id, $type, $user_id = 0, $ip = null) {
        $data = [
            'track_id' => $track_id,
            'user_id' => $user_id,
            'type' => $type,
            'ip' => $ip ?: $_SERVER['REMOTE_ADDR'],
            'created_at' => current_time('mysql'),
        ];
        
        return Connection::insert('interaction', $data);
    }
    
   

    /**
     * Получить популярные треки за период
     */
    public static function get_popular_tracks($period = 'week', $limit = 10) {
        global $wpdb;
        
        $date_condition = match($period) {
            'day' => "AND i.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)",
            'week' => "AND i.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "AND i.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "",
        };
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT t.*, COUNT(i.id) as play_count
            FROM " . BM_TE_TABLE_TRACK . " t
            JOIN bm_ctbl000_interaction i ON t.id = i.track_id AND i.type = 'play'
            WHERE 1=1 $date_condition
            GROUP BY t.id
            ORDER BY play_count DESC
            LIMIT %d
        ", $limit));
    }


}