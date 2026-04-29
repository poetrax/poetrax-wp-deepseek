<?php
/**
 * Работа с направлениями (поддерживает вложенность)
 */
  namespace BM\Hierarchy;
class MovementHierarchy extends BaseHierarchy {
    
    protected $table = 'bm_ctbl000_poet_movements';
    protected $taxonomy = 'movement';
    
    private $tree = null;
    private $paths = [];
    
    /**
     * Получить URL направления
     */
    public function getUrl($movement) {
        if (is_numeric($movement)) {
            $movement = $this->getById($movement);
        }
        
        if (!$movement) {
            return '#';
        }
        
        // Получаем полный путь
        $path = $this->getPath($movement['id']);
        
        return '/poet/' . $path . '/';
    }
    
    /**
     * Получить полный путь (slug1/slug2/slug3)
     */
    public function getPath($movementId) {
        // Проверяем кэш
        if (isset($this->paths[$movementId])) {
            return $this->paths[$movementId];
        }
        
        $path = $this->db->selectCell(
            "SELECT get_movement_path(?i)",
            $movementId
        );
        
        $this->paths[$movementId] = $path;
        
        return $path;
    }
    
    /**
     * Получить дерево иерархии
     */
    public function getTree($centuryId = null) {
        $key = 'movement_tree_' . ($centuryId ?: 'all');
        
        $cached = $this->db->getVar($key);
        if ($cached !== null) {
            return unserialize($cached);
        }
        
        $where = $centuryId ? "WHERE century_id = ?i" : "";
        $params = $centuryId ? [$centuryId] : [];
        
        $movements = $this->db->select(
            "SELECT * FROM ?n 
             $where 
             ORDER BY level, display_order, name",
            array_merge([$this->table], $params)
        );
        
        $tree = $this->buildTree($movements);
        
        $this->db->setVar($key, serialize($tree));
        
        return $tree;
    }
    
    /**
     * Построить дерево из плоского списка
     */
    private function buildTree(&$movements, $parentId = null) {
        $tree = [];
        
        foreach ($movements as $movement) {
            if ($movement['parent_id'] == $parentId) {
                $children = $this->buildTree($movements, $movement['id']);
                if (!empty($children)) {
                    $movement['children'] = $children;
                }
                $tree[] = $movement;
            }
        }
        
        return $tree;
    }
    
    /**
     * Получить направления по веку
     */
    public function getByCentury($centuryId, $level = null) {
        $where = "century_id = ?i";
        $params = [$centuryId];
        
        if ($level !== null) {
            $where .= " AND level = ?i";
            $params[] = $level;
        }
        
        return $this->db->select(
            "SELECT * FROM ?n 
             WHERE $where 
             ORDER BY display_order, name",
            array_merge([$this->table], $params)
        );
    }
    
    /**
     * Получить поднаправления
     */
    public function getChildren($parentId) {
        return $this->db->select(
            "SELECT * FROM ?n 
             WHERE parent_id = ?i 
             ORDER BY display_order, name",
            $this->table,
            $parentId
        );
    }
    
    /**
     * Получить родительский путь (хлебные крошки)
     */
    public function getBreadcrumbs($movementId) {
        $crumbs = [];
        $currentId = $movementId;
        
        while ($currentId) {
            $movement = $this->getById($currentId);
            if (!$movement) break;
            
            array_unshift($crumbs, [
                'id' => $movement['id'],
                'name' => $movement['name'],
                'slug' => $movement['slug'],
                'url' => $this->getUrl($movement)
            ]);
            
            $currentId = $movement['parent_id'];
        }
        
        return $crumbs;
    }
    
    /**
     * Получить статистику по уровням
     */
    public function getLevelStats() {
        return $this->db->select(
            "SELECT level, COUNT(*) as count 
             FROM ?n 
             GROUP BY level 
             ORDER BY level",
            $this->table
        );
    }
}