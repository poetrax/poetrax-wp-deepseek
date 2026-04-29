<?php
/**
 * Работа с поэтами (использует иерархию веков и направлений)
 */
  namespace BM\Hierarchy;
class PoetHierarchy extends BaseHierarchy {
    
    protected $table = 'bm_ctbl000_poet';
    protected $taxonomy = 'poet';
    
    private $centuryHierarchy;
    private $movementHierarchy;
    
    public function __construct() {
        parent::__construct();
        $this->centuryHierarchy = new CenturyHierarchy();
        $this->movementHierarchy = new MovementHierarchy();
    }
    
    /**
     * Получить URL поэта (умная ссылка)
     */
    public function getUrl($poet) {
        if (is_numeric($poet)) {
            $poet = $this->getById($poet);
        }
        
        if (!$poet) {
            return '#';
        }
        
        // Используем full_slug если есть
        if (!empty($poet['full_slug'])) {
            return '/poet/' . $poet['full_slug'] . '/';
        }
        
        // Иначе строим вручную
        $path = [];
        
        if ($poet['century_id']) {
            $century = $this->centuryHierarchy->getById($poet['century_id']);
            if ($century) {
                $path[] = $century['slug'];
            }
        }
        
        if ($poet['movement_id']) {
            $movementPath = $this->movementHierarchy->getPath($poet['movement_id']);
            if ($movementPath) {
                $path[] = $movementPath;
            }
        }
        
        $path[] = $poet['slug'];
        
        return '/poet/' . implode('/', $path) . '/';
    }
    
    /**
     * Получить поэта со всеми связями
     */
    public function getFull($slug) {
        $key = 'poet_full_' . md5($slug);
        
        $cached = $this->db->getVar($key);
        if ($cached !== null) {
            return unserialize($cached);
        }
        
        $poet = $this->db->selectRow(
            "SELECT p.*, 
                    c.name as century_name, 
                    c.slug as century_slug,
                    m.name as movement_name,
                    m.slug as movement_slug,
                    m.level as movement_level
             FROM bm_ctbl000_poet p
             LEFT JOIN bm_ctbl000_poet_centuries c ON p.century_id = c.id
             LEFT JOIN bm_ctbl000_poet_movements m ON p.movement_id = m.id
             WHERE p.slug = ?s
             LIMIT 1",
            $slug
        );
        
        if ($poet) {
            // Добавляем URL
            $poet['url'] = $this->getUrl($poet);
            
            // Добавляем хлебные крошки
            $poet['breadcrumbs'] = $this->getBreadcrumbs($poet);
            
            $this->db->setVar($key, serialize($poet));
        }
        
        return $poet;
    }
    
    /**
     * Получить хлебные крошки для поэта
     */
    public function getBreadcrumbs($poet) {
        if (is_numeric($poet)) {
            $poet = $this->getById($poet);
        }
        
        if (!$poet) {
            return [];
        }
        
        $crumbs = [];
        
        // Век
        if ($poet['century_id']) {
            $century = $this->centuryHierarchy->getById($poet['century_id']);
            if ($century) {
                $crumbs[] = [
                    'name' => $century['name'],
                    'url' => $this->centuryHierarchy->getUrl($century)
                ];
            }
        }
        
        // Направления (весь путь)
        if ($poet['movement_id']) {
            $movementCrumbs = $this->movementHierarchy->getBreadcrumbs($poet['movement_id']);
            $crumbs = array_merge($crumbs, $movementCrumbs);
        }
        
        // Сам поэт
        $crumbs[] = [
            'name' => $poet['name'],
            'url' => $this->getUrl($poet)
        ];
        
        return $crumbs;
    }
    
    /**
     * Получить произведения поэта
     */
    public function getWorks($poetId, $type = null, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $where = "poet_id = ?i";
        $params = [$poetId];
        
        if ($type) {
            $where .= " AND type = ?s";
            $params[] = $type;
        }
        
        $works = $this->db->select(
            "SELECT * FROM works 
             WHERE $where 
             ORDER BY published_at DESC, created_at DESC
             LIMIT ?i, ?i",
            array_merge($params, [$offset, $perPage])
        );
        
        $total = $this->db->selectCell(
            "SELECT COUNT(*) FROM works WHERE $where",
            $params
        );
        
        return [
            'items' => $works,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Поиск по поэтам с учетом иерархии
     */
    public function search($query, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($query)) {
            $where[] = "(name LIKE ?s OR bio LIKE ?s)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        if (!empty($filters['century_id'])) {
            $where[] = "century_id = ?i";
            $params[] = $filters['century_id'];
        }
        
        if (!empty($filters['movement_id'])) {
            $where[] = "movement_id = ?i";
            $params[] = $filters['movement_id'];
        }
        
        if (!empty($filters['birth_year_from'])) {
            $where[] = "birth_year >= ?i";
            $params[] = $filters['birth_year_from'];
        }
        
        if (!empty($filters['birth_year_to'])) {
            $where[] = "birth_year <= ?i";
            $params[] = $filters['birth_year_to'];
        }
        
        $whereClause = implode(" AND ", $where);
        
        return $this->db->select(
            "SELECT p.*, 
                    c.name as century_name,
                    m.name as movement_name
             FROM bm_ctbl000_poet p
             LEFT JOIN bm_ctbl000_poet_centuries c ON p.century_id = c.id
             LEFT JOIN bm_ctbl000_poet_movements m ON p.movement_id = m.id
             WHERE $whereClause
             ORDER BY p.name
             LIMIT 50",
            $params
        );
    }
    
    /**
     * Получить статистику по векам
     */
    public function getCenturyStats() {
        return $this->db->select(
            "SELECT c.id, c.name, c.slug, COUNT(p.id) as poet_count
             FROM bm_ctbl000_poet_centuries c
             LEFT JOIN bm_ctbl000_poet p ON c.id = p.century_id
             GROUP BY c.id, c.name, c.slug
             ORDER BY c.display_order, c.start_year"
        );
    }
    
    /**
     * Получить статистику по направлениям
     */
    public function getbm_ctbl000_poet_movementstats($centuryId = null) {
        $where = "";
        $params = [];
        
        if ($centuryId) {
            $where = "WHERE m.century_id = ?i";
            $params[] = $centuryId;
        }
        
        return $this->db->select(
            "SELECT m.id, m.name, m.slug, m.level, COUNT(p.id) as poet_count
             FROM bm_ctbl000_poet_movements m
             LEFT JOIN bm_ctbl000_poet p ON m.id = p.movement_id
             $where
             GROUP BY m.id, m.name, m.slug, m.level
             ORDER BY m.level, m.display_order, m.name",
            $params
        );
    }
}