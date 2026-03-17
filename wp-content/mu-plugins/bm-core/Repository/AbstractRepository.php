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
        $this->table = $this->connection->table($this->getTableName());
    }

    abstract protected function getTableName(): string;

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->connection->fetchOne($sql, ['id' => $id]);
    }

    public function findAll($limit = 100, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        return $this->connection->fetchAll($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function findBy(array $conditions, $limit = null, $orderBy = null)
    {
        $where = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }
        
        return $this->connection->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        return $this->connection->insert($this->getTableName(), $data);
    }

    public function update($id, array $data)
    {
        return $this->connection->update(
            $this->getTableName(),
            $data,
            [$this->primaryKey => $id]
        );
    }

    public function delete($id)
    {
        return $this->connection->delete(
            $this->getTableName(),
            [$this->primaryKey => $id]
        );
    }

    public function count(array $conditions = [])
    {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            return $this->connection->fetchOne($sql)->total;
        }
        
        $where = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE " . implode(' AND ', $where);
        
        return $this->connection->fetchOne($sql, $params)->total;
    }
}
