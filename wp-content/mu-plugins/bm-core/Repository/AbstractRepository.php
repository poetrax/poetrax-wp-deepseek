<?php
namespace BM\Core\Repository;

use BM\Core\Database\Connection;

abstract class AbstractRepository
{
    protected $connection;
    protected $tableName;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
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
}
