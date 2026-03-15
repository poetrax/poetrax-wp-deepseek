<?php
namespace BM\Repositories;

interface RepositoryInterface {
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function getAll($limit = 100, $offset = 0);
}