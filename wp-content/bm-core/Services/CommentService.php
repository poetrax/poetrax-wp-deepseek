<?php
namespace BM\Services;

use BM\Repositories\CommentRepository;

class CommentService {
    
    public $comment_repo;
    
    public function __construct() {
        $this->comment_repo = new CommentRepository();
    }
    
    /**
     * Добавить комментарий к треку
     */
    public function addToTrack($track_id, $author, $content, $email = '', $parent_id = 0) {
        $data = [
            'track_id' => $track_id,
            'author' => $author,
            'author_email' => $email,
            'content' => $content,
            'parent_id' => $parent_id,
            'user_id' => get_current_user_id(),
        ];
        
        return $this->comment_repo->add($data);
    }
    
    /**
     * Получить древовидную структуру комментариев
     */
    public function getTree($track_id) {
        $comments = $this->comment_repo->getByTrack($track_id);
        
        // Строим дерево
        $tree = [];
        $by_id = [];
        
        foreach ($comments as $comment) {
            $by_id[$comment->id] = $comment;
            $comment->children = [];
        }
        
        foreach ($by_id as $id => $comment) {
            if ($comment->parent_id && isset($by_id[$comment->parent_id])) {
                $by_id[$comment->parent_id]->children[] = $comment;
            } else {
                $tree[] = $comment;
            }
        }
        
        return $tree;
    }
    
    /**
     * Получить статистику комментариев
     */
    public function getStats($track_id = null) {
        global $wpdb;
        
        if ($track_id) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(is_approved = 1) as approved,
                    MIN(created_at) as first,
                    MAX(created_at) as last
                FROM " . Connection::table('comments') . "
                WHERE track_id = %d",
                $track_id
            ));
        }
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(is_approved = 1) as approved
            FROM " . Connection::table('comments')
        );
    }
}