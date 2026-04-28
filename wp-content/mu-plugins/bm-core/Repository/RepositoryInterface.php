<?php
namespace BM\Core\Repository;
use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Core\Database\QueryBuilder;

interface RepositoryInterface
{
    public function find($id)
    {
        $cache = new Cache();
        $cache_key = ['image', $id];

        $image = $cache->get($cache_key);
        if (!$image) {
            $connection = Connection::getInstance();
            $image = $connection->fetchOne(
                "SELECT * FROM img WHERE id = ?",
                [$id]
            );
            if ($image) {
                $cache->set($cache_key, $image, 3600);
            }
        }
        return $image;
    }

    public function findAll($limit = 100, $offset = 0)
    {
        return $this->getAll($limit, $offset);
    }

    public function findBy(array $conditions, $limit = null, $orderBy = null)
    {
        $qb = new QueryBuilder(Connection::getInstance());
        $qb->table('img');

        foreach ($conditions as $field => $value) {
            $qb->where($field, $value);
        }

        if ($orderBy) {
            $qb->orderBy($orderBy);
        }

        if ($limit) {
            $qb->limit($limit);
        }

        return $qb->get();
    }

    public function findOneBy(array $conditions)
    {
        $result = $this->findBy($conditions, 1);
        return $result ? $result[0] : null;
    }

    public function count(array $conditions = [])
    {
        $qb = new QueryBuilder(Connection::getInstance());
        $qb->table('img')->select('COUNT(*) as total');

        foreach ($conditions as $field => $value) {
            $qb->where($field, $value);
        }

        $result = $qb->first();
        return $result ? $result->total : 0;
    }

    public function exists(array $conditions)
    {
        return $this->count($conditions) > 0;
    }
}
