<?php
namespace BM\Repositories;

use BM\Database\Connection;
use BM\Database\Cache;

class CommentRepository {
    
    /**
     * Получить комментарии к треку
     */
    public function getByTrack($track_id, $approved_only = true, $limit = 50) {
        $cache_key = ['comments', 'track', $track_id, $approved_only, $limit];
        $comments = Cache::get($cache_key);
        
        if (!$comments) {
            $sql = "SELECT * FROM " . Connection::table('comments') . " 
                    WHERE track_id = %d";
            
            if ($approved_only) {
                $sql .= " AND is_approved = 1";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT %d";
            
            $comments = Connection::query($sql, [$track_id, $limit]);
            Cache::set($cache_key, $comments, 300);
        }
        
        return $comments;
    }
    
    /**
     * Добавить комментарий
     */
    public function add($data) {
        $defaults = [
            'created_at' => current_time('mysql'),
            'created_at_gmt' => current_time('mysql', 1),
            'is_approved' => 0,
            'karma' => 0,
            'type' => 'comment',
            'parent_id' => 0,
            'user_id' => get_current_user_id(),
            'author_IP' => $_SERVER['REMOTE_ADDR'],
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $id = Connection::insert('comments', $data);
        
        if ($id) {
            // Очищаем кэш
            if (!empty($data['track_id'])) {
                Cache::delete(['comments', 'track', $data['track_id']]);
            }
            
            do_action('bm_comment_added', $id, $data);
        }
        
        return $id;
    }
    
    /**
     * Одобрить комментарий
     */
    public function approve($id) {
        $result = Connection::update('comments', ['is_approved' => 1], ['id' => $id]);
        
        if ($result) {
            $comment = $this->find($id);
            if ($comment && $comment->track_id) {
                Cache::delete(['comments', 'track', $comment->track_id]);
            }
        }
        
        return $result;
    }
    
    /**
     * Удалить комментарий
     */
    public function delete($id) {
        $comment = $this->find($id);
        $result = Connection::delete('comments', ['id' => $id]);
        
        if ($result && $comment && $comment->track_id) {
            Cache::delete(['comments', 'track', $comment->track_id]);
        }
        
        return $result;
    }
    
    /**
     * Найти комментарий по ID
     */
    public function find($id) {
        return Connection::get_row(
            "SELECT * FROM " . Connection::table('comments') . " WHERE id = %d",
            [$id]
        );
    }
    
    /**
     * Получить последние комментарии
     */
    public function getRecent($limit = 10) {
        $cache_key = ['comments', 'recent', $limit];
        $comments = Cache::get($cache_key);
        
        if (!$comments) {
            $comments = Connection::query(
                "SELECT c.*, t.track_name 
                 FROM " . Connection::table('comments') . " c
                 LEFT JOIN " . Connection::table('track') . " t ON c.track_id = t.id
                 WHERE c.is_approved = 1
                 ORDER BY c.created_at DESC
                 LIMIT %d",
                [$limit]
            );
            Cache::set($cache_key, $comments, 300);
        }
        
        return $comments;
    }
}