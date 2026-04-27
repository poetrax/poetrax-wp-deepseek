<?php
namespace BM\Core\Repository;
use BM\Core\Database\Connection;
use BM\Core\Database\CacheManager;
use BM\Core\Database\Cache;

abstract class AbstractRepository
{
    protected $connection;
    protected $tableName;
    protected CacheManager $cacheManager;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
        $cache = Cache::getInstance();
        $this->cacheManager = new CacheManager($cache, $this->connection);
    }

    abstract protected function getTableName(): string;

    public function find($id)
    {
        $table = $this->getTableName();
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        return $this->connection->fetchOne($sql, [$id]);
    }

    public function findAll()
    {
        $table = $this->getTableName();
        $sql = "SELECT * FROM {$table}";
        return $this->connection->fetchAll($sql);
    }

    public function findBy($field, $value)
    {
        $table = $this->getTableName();
        $sql = "SELECT * FROM {$table} WHERE {$field} = ?";
        return $this->connection->fetchAll($sql, [$value]);
    }

    public function create($data)
    {
        $table = $this->getTableName();
        return $this->connection->insert($table, $data);
    }

    public function update($id, $data)
    {
        $table = $this->getTableName();
        $where = "id = {$id}";
        return $this->connection->update($table, $data, $where);
    }

    public function delete($id)
    {
        $table = $this->getTableName();
        $where = "id = {$id}";
        return $this->connection->delete($table, $where);

    }

    /**
     * Найти одну запись по условию
     * 
     * @param array $conditions Ассоциативный массив [поле => значение]
     * @return array|null
     */
    public function findOneBy(array $conditions): ?array
    {
        $table = $this->getTableName();
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = ?";
            $params[] = $value;
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$table} WHERE {$whereClause} LIMIT 1";

        $result = $this->connection->fetchOne($sql, $params);
        return $result ?: null;
    }


     



}




