<?php
 namespace BM\Hierarchy;

use BM\Database\Connection;

class PoetHierarchyAPI {
    public function __construct() {
      
    }
    
    /**
     * Получить полное дерево иерархии
     */
    public function get_full_tree() {
        $tree = [];
        
        // Получаем все века
        $result = $this->mysqli->query("
            SELECT * FROM bm_ctbl000_poet_centuries 
            ORDER BY display_order, start_year
        ");
        
        while ($century = $result->fetch_assoc()) {
            $century['movements'] = [];
            $century['url'] = "/poet/{$century['slug']}/";
            
            // Получаем направления века (level 2)
            $stmt = $this->mysqli->prepare("
                SELECT * FROM bm_ctbl000_poet_movements 
                WHERE century_id = ? AND level = 2
                ORDER BY display_order, name
            ");
            $stmt->bind_param('i', $century['id']);
            $stmt->execute();
            $movements_result = $stmt->get_result();
            
            while ($movement = $movements_result->fetch_assoc()) {
                $movement['movements'] = [];
                $movement['url'] = "/poet/{$century['slug']}/{$movement['slug']}/";
                
                // Получаем поднаправления (level 3)
                $sub_stmt = $this->mysqli->prepare("
                    SELECT * FROM bm_ctbl000_poet_movements 
                    WHERE parent_id = ?
                    ORDER BY display_order, name
                ");
                $sub_stmt->bind_param('i', $movement['id']);
                $sub_stmt->execute();
                $subs_result = $sub_stmt->get_result();
                
                while ($sub = $subs_result->fetch_assoc()) {
                    $sub['url'] = "/poet/{$century['slug']}/{$movement['slug']}/{$sub['slug']}/";
                    $movement['submovements'][] = $sub;
                }
                
                // Получаем поэтов этого направления
                $poet_stmt = $this->mysqli->prepare("
                    SELECT * FROM bm_ctbl000_poet 
                    WHERE movement_id = ? OR movement_id IN (
                        SELECT id FROM bm_ctbl000_poet_movements WHERE parent_id = ?
                    )
                    ORDER BY name
                ");
                $poet_stmt->bind_param('ii', $movement['id'], $movement['id']);
                $poet_stmt->execute();
                $bm_ctbl000_poet_result = $poet_stmt->get_result();
                
                $movement['bm_ctbl000_poet'] = [];
                while ($poet = $bm_ctbl000_poet_result->fetch_assoc()) {
                    $poet['url'] = $this->get_poet_url($poet);
                    $movement['bm_ctbl000_poet'][] = $poet;
                }
                
                $century['movements'][] = $movement;
            }
            
            $tree[] = $century;
        }
        
        return $tree;
    }
    
    /**
     * Получить URL поэта
     */
    public function get_poet_url($poet) {
        // Используем сохраненный full_slug
        if (!empty($poet['full_slug'])) {
            return "/poet/{$poet['full_slug']}/";
        }
        
        // Fallback на старую логику
        $century_slug = '';
        $movement_slug = '';
        
        if (!empty($poet['century_id'])) {
            $result = $this->mysqli->query("
                SELECT slug FROM bm_ctbl000_poet_centuries WHERE id = {$poet['century_id']}
            ");
            $century = $result->fetch_assoc();
            $century_slug = $century['slug'] . '/';
        }
        
        if (!empty($poet['movement_id'])) {
            $result = $this->mysqli->query("
                SELECT get_movement_path({$poet['movement_id']}) as path
            ");
            $path = $result->fetch_assoc();
            $movement_slug = $path['path'] . '/';
        }
        
        return "/poet/{$century_slug}{$movement_slug}{$poet['slug']}/";
    }
    
    /**
     * Получить хлебные крошки
     */
    public function get_breadcrumbs($poet_slug) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                c.name as century_name,
                c.slug as century_slug,
                m.name as movement_name,
                m.slug as movement_slug,
                p.name as poet_name,
                p.slug as poet_slug,
                p.full_slug
            FROM bm_ctbl000_poet p
            LEFT JOIN bm_ctbl000_poet_movements m ON p.movement_id = m.id
            LEFT JOIN bm_ctbl000_poet_centuries c ON p.century_id = c.id
            WHERE p.slug = ?
        ");
        $stmt->bind_param('s', $poet_slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data) return [];
        
        $crumbs = [
            ['name' => $data['century_name'], 'url' => "/poet/{$data['century_slug']}/"]
        ];
        
        // Добавляем направления по пути
        if (!empty($data['movement_slug'])) {
            $movement_parts = explode('/', $data['movement_slug']);
            $path = '';
            
            foreach ($movement_parts as $part) {
                $path .= $part . '/';
                $movement_info = $this->mysqli->query("
                    SELECT name FROM bm_ctbl000_poet_movements WHERE slug = '$part'
                ")->fetch_assoc();
                
                $crumbs[] = [
                    'name' => $movement_info['name'],
                    'url' => "/poet/{$data['century_slug']}/$path"
                ];
            }
        }
        
        $crumbs[] = [
            'name' => $data['poet_name'],
            'url' => "/poet/{$data['full_slug']}/"
        ];
        
        return $crumbs;
    }
    
    /**
     * Поиск по иерархии
     */
    public function search_hierarchy($query) {
        $search = "%{$query}%";
        
        $stmt = $this->mysqli->prepare("
            (SELECT 'poet' as type, id, name, slug, full_slug, 
                    MATCH(name, bio) AGAINST(?) as relevance
             FROM bm_ctbl000_poet 
             WHERE MATCH(name, bio) AGAINST(?)
             LIMIT 10)
            UNION ALL
            (SELECT 'movement' as type, id, name, slug, 
                    CONCAT(century_slug, '/', slug) as full_slug,
                    MATCH(name, description) AGAINST(?) as relevance
             FROM bm_ctbl000_poet_movements 
             LEFT JOIN bm_ctbl000_poet_centuries ON bm_ctbl000_poet_movements.century_id = bm_ctbl000_poet_centuries.id
             WHERE MATCH(name, description) AGAINST(?)
             LIMIT 10)
            UNION ALL
            (SELECT 'century' as type, id, name, slug, slug as full_slug,
                    MATCH(name, description) AGAINST(?) as relevance
             FROM bm_ctbl000_poet_centuries 
             WHERE MATCH(name, description) AGAINST(?)
             LIMIT 10)
            ORDER BY relevance DESC
            LIMIT 20
        ");
        
        $stmt->bind_param('ssssss', $search, $search, $search, $search, $search, $search);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function __destruct() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }
}