<?php
namespace BM\Core\Repository;
use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Core\Database\QueryBuilder;

interface RepositoryInterface
{
    public function find($id)
    {
        $cache_key = ['image', $id];

        $image = $this->cache->get($cache_key);
        if (!$image) {
             $image = $this->connection->fetchOne(
                "SELECT * FROM img WHERE id = ?",
                [$id]
            );
            if ($image) {
                $this->cache->set($cache_key, $image, 3600);
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
        $this->querybuilder($this->connection)->table('img');

        foreach ($conditions as $field => $value) {
            $this->querybuilder($this->connection)->where($field, $value);
        }

        if ($orderBy) {
            $this->querybuilder($this->connection)->orderBy($orderBy);
        }

        if ($limit) {
            $this->querybuilder($this->connection)->limit($limit);
        }

        return $this->querybuilder($this->connection)->get();
    }

    public function findOneBy(array $conditions)
    {
        $result = $this->findBy($conditions, 1);
        return $result ? $result[0] : null;
    }

    public function count(array $conditions = [])
    {
        $this->querybuilder($this->connection)->table('img')->select('COUNT(*) as total');

        foreach ($conditions as $field => $value) {
            $this->querybuilder($this->connection)->where($field, $value);
        }

        $result = $this->querybuilder($this->connection)->first();
        return $result ? $result->total : 0;
    }

    public function exists(array $conditions)
    {
        return $this->count($conditions) > 0;
    }
}
