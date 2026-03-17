<?php
namespace BM\Core\Repository;

interface RepositoryInterface
{
    public function find($id);
    public function findAll($limit = 100, $offset = 0);
    public function findBy(array $conditions, $limit = null, $orderBy = null);
    public function findOneBy(array $conditions);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function count(array $conditions = []);
    public function exists(array $conditions);
}
