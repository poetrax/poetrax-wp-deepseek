<?php
namespace BM\Core\Repository;

interface RepositoryInterface
{
    public function update($id, array $data);
    public function delete($id);
    public function findAll($limit = 100, $offset = 0);
    public function count(array $conditions = []);
    public function exists(array $conditions);
    public function findBy(array $conditions, $limit = null, $orderBy = null);
    public function findOneBy(array $conditions);
}

class PoemRepository implements RepositoryInterface
{
    private $cache;

    public function __construct()
    {
        
    }

    public function update($id, array $data)
    {
        $this->cache->set("poem_$id", $data);
        return 1; // или просто void
    }

    // Добавьте эти методы:
    public function create(array $data)
    {
        // Если интерфейс не требует типа возврата
        return $this->connection->insert($this->getTableName(), $data);
    }

    public function find($id)
    {
        return $this->connection->fetchOne(
            "SELECT * FROM {$this->getTableName()} WHERE id = ?",
            [$id]
        );
    }

    public function delete($id)
    {
        //TODO
        // реализация
        return 1;
    }

    public function findAll($limit = 100, $offset = 0)
    {
        //TODO
        // реализация с учётом $limit и $offset
        return [];
    }

    public function count(array $conditions = [])
    {
        //TODO
        return 0;
    }

    public function exists(array $conditions)
    {
        return true;
    }

    public function findBy(array $conditions, $limit = null, $orderBy = null)
    {
        //TODO
        return [];
    }

    public function findOneBy(array $conditions)
    {
        //TODO
        return null;
    }
}