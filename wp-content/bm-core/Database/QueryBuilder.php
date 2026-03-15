<?php
namespace BM\Database;

class QueryBuilder {
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $where = [];
    private $group_by = [];
    private $order_by = [];
    private $limit = null;
    private $offset = 0;
    private $params = [];
    
    /**
     * Создать запрос к таблице
     */
    public static function table($table_key) {
        $instance = new self();
        $instance->table = Connection::table($table_key);
        return $instance;
    }
    
    /**
     * Выбрать поля
     */
    public function select($fields) {
        $this->select = is_array($fields) ? $fields : func_get_args();
        return $this;
    }
    
    /**
     * Присоединить таблицу
     */
    public function join($table_key, $condition, $type = 'INNER') {
        $table_name = Connection::table($table_key);
        $this->joins[] = "$type JOIN $table_name ON $condition";
        return $this;
    }
    
    /**
     * WHERE условие
     */
    public function where($field, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = is_numeric($value) ? '%d' : '%s';
        $this->where[] = "$field $operator $placeholder";
        $this->params[] = $value;
        
        return $this;
    }
    
    /**
     * WHERE IN
     */
    public function whereIn($field, $values) {
        $placeholders = implode(',', array_fill(0, count($values), is_numeric($values[0]) ? '%d' : '%s'));
        $this->where[] = "$field IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }
    
    /**
     * WHERE LIKE
     */
    public function whereLike($field, $value) {
        $this->where[] = "$field LIKE '%%%s%%'";
        $this->params[] = Connection::escape($value);
        return $this;
    }
    
    /**
     * WHERE NULL
     */
    public function whereNull($field) {
        $this->where[] = "$field IS NULL";
        return $this;
    }
    
    /**
     * WHERE NOT NULL
     */
    public function whereNotNull($field) {
        $this->where[] = "$field IS NOT NULL";
        return $this;
    }
    
    /**
     * GROUP BY
     */
    public function groupBy($field) {
        $this->group_by[] = $field;
        return $this;
    }
    
    /**
     * ORDER BY
     */
    public function orderBy($field, $direction = 'ASC') {
        $this->order_by[] = "$field $direction";
        return $this;
    }
    
    /**
     * LIMIT
     */
    public function limit($limit, $offset = 0) {
        $this->limit = (int)$limit;
        $this->offset = (int)$offset;
        return $this;
    }
    
    /**
     * Получить результаты
     */
    public function get() {
        $sql = $this->buildSelect();
        return Connection::query($sql, $this->params);
    }
    
    /**
     * Получить первый результат
     */
    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    /**
     * Получить количество
     */
    public function count() {
        $original_select = $this->select;
        $this->select = ['COUNT(*) as total'];
        
        $sql = $this->buildSelect();
        $result = Connection::row($sql, $this->params);
        
        $this->select = $original_select;
        
        return $result ? (int)$result->total : 0;
    }
    
    /**
     * Построить SQL запрос
     */
    private function buildSelect() {
        $sql = "SELECT " . implode(', ', $this->select);
        $sql .= " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        if (!empty($this->group_by)) {
            $sql .= " GROUP BY " . implode(', ', $this->group_by);
        }
        
        if (!empty($this->order_by)) {
            $sql .= " ORDER BY " . implode(', ', $this->order_by);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->offset}, {$this->limit}";
        }
        
        return $sql;
    }
}