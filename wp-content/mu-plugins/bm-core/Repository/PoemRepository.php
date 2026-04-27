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
        $this->cache = new Cache(); // создаём экземпляр
    }

    public function update($id, array $data)
    {
        $this->cache->set("poem_$id", $data);
        return 1; // или просто void
    }

    public function delete($id)
    {
        // реализация
        return 1;
    }

    public function findAll($limit = 100, $offset = 0)
    {
        // реализация с учётом $limit и $offset
        return [];
    }

    public function count(array $conditions = [])
    {
        return 0;
    }

    public function exists(array $conditions)
    {
        return true;
    }

    public function findBy(array $conditions, $limit = null, $orderBy = null)
    {
        return [];
    }

    public function findOneBy(array $conditions)
    {
        return null;
    }
}