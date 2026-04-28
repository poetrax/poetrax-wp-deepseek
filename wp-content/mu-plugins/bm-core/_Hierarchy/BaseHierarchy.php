<?php
/**
 * Базовый класс для всех иерархических структур
 * Использует ядро для работы с БД
 */
 namespace BM\Hierarchy;


abstract class BaseHierarchy {
    
    protected $db;
    protected $table;
    protected $taxonomy;
    
    /**
     * Конструктор
     */
    public function __construct() {
        // Получаем экземпляр ядра
        $this->db = core::getInstance();
    }
    
    /**
     * Получить все элементы с поддержкой кэширования
     */
    public function getAll($force = false) {
        $key = $this->taxonomy . '_all_' . md5($this->table);
        
        // Пробуем получить из кэша через ядро
        $result = $this->db->getVar($key);
        if ($result !== null && !$force) {
            return unserialize($result);
        }
        
        // Загружаем из БД
        $data = $this->db->select(
            "SELECT * FROM ?n ORDER BY display_order, name",
            $this->table
        );
        
        // Сохраняем в кэш
        $this->db->setVar($key, serialize($data));
        
        return $data;
    }
    
    /**
     * Получить элемент по slug
     */
    public function getBySlug($slug) {
        $key = $this->taxonomy . '_slug_' . md5($slug);
        
        $result = $this->db->getVar($key);
        if ($result !== null) {
            return unserialize($result);
        }
        
        $data = $this->db->selectRow(
            "SELECT * FROM ?n WHERE slug = ?s LIMIT 1",
            $this->table,
            $slug
        );
        
        if ($data) {
            $this->db->setVar($key, serialize($data));
        }
        
        return $data;
    }
    
    /**
     * Получить по ID
     */
    public function getById($id) {
        $key = $this->taxonomy . '_id_' . $id;
        
        $result = $this->db->getVar($key);
        if ($result !== null) {
            return unserialize($result);
        }
        
        $data = $this->db->selectRow(
            "SELECT * FROM ?n WHERE id = ?i LIMIT 1",
            $this->table,
            $id
        );
        
        if ($data) {
            $this->db->setVar($key, serialize($data));
        }
        
        return $data;
    }
    
    /**
     * Очистить кэш
     */
    public function clearCache() {
        $this->db->delVars($this->taxonomy . '*');
    }
    
    /**
     * Получить полный URL для элемента
     */
    abstract public function getUrl($item);
}