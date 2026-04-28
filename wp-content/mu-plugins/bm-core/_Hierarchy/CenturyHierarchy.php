<?php
/**
 * Работа с веками
 */
  namespace BM\Hierarchy;
class CenturyHierarchy extends BaseHierarchy {
    
    protected $table = 'bm_ctbl000_poet_centuries';
    protected $taxonomy = 'century';
    
    /**
     * Получить URL века
     */
    public function getUrl($century) {
        if (is_numeric($century)) {
            $century = $this->getById($century);
        }
        
        if (!$century) {
            return '#';
        }
        
        return '/poet/' . $century['slug'] . '/';
    }
    
    /**
     * Получить века с пагинацией (стиль core::pagination)
     */
    public function getWithPagination($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $centuries = $this->db->select(
            "SELECT * FROM ?n 
             ORDER BY display_order, start_year 
             LIMIT ?i, ?i",
            $this->table,
            $offset,
            $perPage
        );
        
        $total = $this->db->selectCell(
            "SELECT COUNT(*) FROM ?n",
            $this->table
        );
        
        return [
            'items' => $centuries,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Получить направления века
     */
    public function getbm_ctbl000_poet_movements($centuryId, $level = 2) {
        $movementHierarchy = new MovementHierarchy();
        return $movementHierarchy->getByCentury($centuryId, $level);
    }
    
    /**
     * Получить всех поэтов века
     */
    public function getbm_ctbl000_poet($centuryId, $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        
        $bm_ctbl000_poet = $this->db->select(
            "SELECT p.*, m.name as movement_name, m.slug as movement_slug
             FROM bm_ctbl000_poet p
             LEFT JOIN bm_ctbl000_poet_movements m ON p.movement_id = m.id
             WHERE p.century_id = ?i
             ORDER BY p.name
             LIMIT ?i, ?i",
            $centuryId,
            $offset,
            $perPage
        );
        
        $total = $this->db->selectCell(
            "SELECT COUNT(*) FROM bm_ctbl000_poet WHERE century_id = ?i",
            $centuryId
        );
        
        return [
            'items' => $bm_ctbl000_poet,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage)
        ];
    }
}