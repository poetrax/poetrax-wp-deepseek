<?php
namespace BM\Core\Repository;

use BM\Core\Database\Connection;
use BM\Core\Exceptions\DatabaseException;

abstract class AbstractRepository
{
    protected $connection;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * Получить название таблицы (должен определить дочерний класс)
     */
    abstract protected function getTableName(): string;

    /**
     * Найти по ID
     */
    public function find($id)
    {
        $table = $this->connection->table($this->getTableName());
        $sql = "SELECT * FROM {$table} WHERE {$this->primaryKey} = :id LIMIT 1";
        
        return $this->connection->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Найти все записи
     */
    public function findAll($limit = 100, $offset = 0)
    {
        $table = $this->connection->table($this->getTableName());
        $sql = "SELECT * FROM {$table} LIMIT :limit OFFSET :offset";
        
        return $this->connection->fetchAll($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Найти по условию
     */
    public function findBy($conditions, $limit = null, $orderBy = null)
    {
        $table = $this->connection->table($this->getTableName());
        
        $where = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }
        
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Найти одну запись по условию
     */
    public function findOneBy($conditions)
    {
        $table = $this->connection->table($this->getTableName());
        
        $where = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " LIMIT 1";
        
        return $this->connection->fetchOne($sql, $params);
    }

    /**
     * Создать запись
     */
    public function create(array $data)
    {
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Обновить запись
     */
    public function update($id, array $data)
    {
        return $this->connection->update(
            $this->getTableName(),
            $data,
            [$this->primaryKey => $id]
        );
    }

    /**
     * Удалить запись
     */
    public function delete($id)
    {
        return $this->connection->delete(
            $this->getTableName(),
            [$this->primaryKey => $id]
        );
    }

    /**
     * Подсчитать количество записей
     */
    public function count($conditions = [])
    {
        $table = $this->connection->table($this->getTableName());
        
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as total FROM {$table}";
            return $this->connection->fetchOne($sql)->total;
        }
        
        $where = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE " . implode(' AND ', $where);
        
        return $this->connection->fetchOne($sql, $params)->total;
    }

    /**
     * Проверить существование записи
     */
    public function exists($conditions)
    {
        return $this->count($conditions) > 0;
    }
}
