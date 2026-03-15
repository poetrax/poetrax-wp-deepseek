<?php
/**
 * Service для работы с взаимодействиями (лайки, закладки, просмотры)
 */

namespace BM\Services;

use BM\Database\Connection;
use BM\Database\Cache;

class InteractionService {
    
    /**
     * Добавить взаимодействие
     */
    public function add_interaction($track_id, $user_id, $type, $ip = null) {
        global $wpdb;
        
        // Проверяем, есть ли уже такое взаимодействие
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . Connection::table('interaction') . "
            WHERE track_id = %d AND user_id = %d AND type = %s",
            $track_id,
            $user_id,
            $type
        ));
        
        if ($exists) {
            // Удаляем (toggle)
            return $this->remove_interaction($track_id, $user_id, $type);
        } else {
            // Добавляем
            $data = [
                'track_id' => $track_id,
                'user_id' => $user_id,
                'type' => $type,
                'ip' => $ip ?: $_SERVER['REMOTE_ADDR'],
                'created_at' => current_time('mysql'),
            ];
            
            $result = Connection::insert('interaction', $data);
            
            if ($result) {
                $this->clear_cache($track_id);
                do_action('bm_interaction_added', $track_id, $user_id, $type);
            }
            
            return $result;
        }
    }
    
    /**
     * Удалить взаимодействие
     */
    public function remove_interaction($track_id, $user_id, $type) {
        global $wpdb;
        
        $result = $wpdb->delete(
            Connection::table('interaction'),
            [
                'track_id' => $track_id,
                'user_id' => $user_id,
                'type' => $type
            ],
            ['%d', '%d', '%s']
        );
        
        if ($result) {
            $this->clear_cache($track_id);
            do_action('bm_interaction_removed', $track_id, $user_id, $type);
        }
        
        return $result;
    }
    
    /**
     * Проверить наличие взаимодействия
     */
    public function has_interaction($track_id, $user_id, $type) {
        global $wpdb;
        
        $cache_key = ['interaction', 'check', $track_id, $user_id, $type];
        $result = Cache::get($cache_key);
        
        if ($result === null) {
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . Connection::table('interaction') . "
                WHERE track_id = %d AND user_id = %d AND type = %s",
                $track_id,
                $user_id,
                $type
            )) > 0;
            
            Cache::set($cache_key, $result, 300);
        }
        
        return $result;
    }
    
    /**
     * Получить статистику трека
     */
    public function get_track_stats($track_id) {
        $cache_key = ['track', 'stats', $track_id];
        $stats = Cache::get($cache_key);
        
        if (!$stats) {
            global $wpdb;
            
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(CASE WHEN type = 'like' THEN 1 END) as likes,
                    COUNT(CASE WHEN type = 'bookmark' THEN 1 END) as bookmarks,
                    COUNT(CASE WHEN type = 'play' THEN 1 END) as plays
                FROM " . Connection::table('interaction') . "
                WHERE track_id = %d",
                $track_id
            ));
            
            if (!$stats) {
                $stats = (object)['likes' => 0, 'bookmarks' => 0, 'plays' => 0];
            }
            
            Cache::set($cache_key, $stats, 300);
        }
        
        return $stats;
    }
    
    /**
     * Записать прослушивание
     */
    public function record_play($track_id, $user_id = 0, $ip = null) {
        $data = [
            'track_id' => $track_id,
            'user_id' => $user_id,
            'type' => 'play',
            'ip' => $ip ?: $_SERVER['REMOTE_ADDR'],
            'created_at' => current_time('mysql'),
        ];
        
        $result = Connection::insert('interaction', $data);
        
        if ($result) {
            $this->clear_cache($track_id);
            
            // Обновляем счётчик прослушиваний в реальном времени
            wp_cache_increment('track_plays_' . $track_id, 'bm_tracks');
            
            do_action('bm_track_played', $track_id, $user_id);
        }
        
        return $result;
    }
    
    /**
     * Получить ID треков, с которыми взаимодействовал пользователь
     */
    public function get_user_tracks($user_id, $type = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $sql = "SELECT track_id FROM " . Connection::table('interaction') . "
                WHERE user_id = %d";
        
        $params = [$user_id];
        
        if ($type) {
            $sql .= " AND type = %s";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_col($wpdb->prepare($sql, $params));
    }
    
    /**
     * Очистить кэш трека
     */
    private function clear_cache($track_id) {
        Cache::delete(['track', 'stats', $track_id]);
        Cache::delete(['interaction', 'check', $track_id]);
    }
}